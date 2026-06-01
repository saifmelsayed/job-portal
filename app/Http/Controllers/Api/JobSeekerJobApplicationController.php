<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Enums\JobPostingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobApplicationRequest;
use App\Http\Resources\JobApplicationResource;
use App\Http\Responses\ApiResponse;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobSeekerJobApplicationController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): JsonResponse
    {
        $applications = $request->user()
            ->jobApplications()
            ->with(JobApplication::applicantProfileWith())
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

        if (
            $jobPosting->user === null
            || $jobPosting->user->status !== 'active'
            || $jobPosting->status !== JobPostingStatus::Active
        ) {
            return ApiResponse::message('Job posting not found.', 404);
        }

        $user = $request->user();

        $application = DB::transaction(static function () use ($jobPosting, $user): JobApplication {
            return JobApplication::query()->create([
                'job_posting_id' => $jobPosting->id,
                'user_id' => $user->id,
                'status' => ApplicationStatus::Pending,
            ]);
        });

        $application->load(JobApplication::applicantProfileWith());

        return ApiResponse::data(
            (new JobApplicationResource($application))->resolve($request),
            201,
        );
    }
}
