<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobApplicationRequest;
use App\Http\Resources\JobApplicationResource;
use App\Http\Responses\ApiResponse;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class JobSeekerJobApplicationController extends Controller
{
    private const PER_PAGE = 15;

    private const string APPLICATION_CV_DISK = 'public';

    public function index(Request $request): JsonResponse
    {
        $applications = $request->user()
            ->jobApplications()
            ->with([
                'jobPosting.user:id,company_name',
            ])
            ->latest()
            ->paginate(self::PER_PAGE);

        $data = $applications->through(
            fn (JobApplication $application) => (new JobApplicationResource($application))->resolve($request),
        );

        return ApiResponse::data($data);
    }

    public function store(StoreJobApplicationRequest $request, JobPosting $jobPosting): JsonResponse
    {
        $jobPosting->loadMissing('user');

        if ($jobPosting->user === null || $jobPosting->user->status !== 'active') {
            return ApiResponse::message('Job posting not found.', 404);
        }

        $validated = $request->safe()->only(['name', 'email', 'phone', 'linkedin']);

        $path = null;

        try {
            $path = $request->file('cv')->store(
                "cvs/jobs/{$jobPosting->id}/users/".$request->user()->id,
                self::APPLICATION_CV_DISK,
            );

            $application = DB::transaction(function () use ($request, $jobPosting, $validated, $path) {
                return JobApplication::query()->create([
                    'job_posting_id' => $jobPosting->id,
                    'user_id' => $request->user()->id,
                    'status' => ApplicationStatus::Pending,
                    'job_title' => $jobPosting->title,
                    'job_description' => $jobPosting->description,
                    'job_requirements' => $jobPosting->requirements,
                    'job_qualification' => $jobPosting->qualification,
                    'job_location' => $jobPosting->location,
                    'job_type' => $jobPosting->type->value,
                    'applicant_name' => $validated['name'],
                    'applicant_email' => $validated['email'],
                    'applicant_phone' => $validated['phone'],
                    'applicant_linkedin' => $validated['linkedin'] ?? null,
                    'cv_path' => $path,
                ]);
            });
        } catch (Throwable $e) {
            if (is_string($path) && $path !== '') {
                Storage::disk(self::APPLICATION_CV_DISK)->delete($path);
            }

            throw $e;
        }

        $application->load([
            'jobPosting.user:id,company_name',
        ]);

        return ApiResponse::data(
            (new JobApplicationResource($application))->toArray($request),
            201,
        );
    }
}
