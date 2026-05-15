<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobApplicationRequest;
use App\Models\JobSeekerProfile;
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
            ->with(array_merge([
                'jobPosting.user' => static function ($q): void {
                    $q->select('id', 'email', 'profile_photo_path');
                },
                'jobPosting.user.companyProfile',
            ], JobApplication::applicantProfileWith()))
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

        $user = $request->user();
        $disk = Storage::disk(self::APPLICATION_CV_DISK);

        if ($request->applyFromProfile()) {
            $user->loadMissing('jobSeekerProfile');
            /** @var JobSeekerProfile|null $profile */
            $profile = $user->jobSeekerProfile;
            $sourceCv = $profile?->cv_path;
            $baseDir = "cvs/jobs/{$jobPosting->id}/users/{$user->id}";
            $ext = is_string($sourceCv) ? pathinfo($sourceCv, PATHINFO_EXTENSION) : '';
            $ext = is_string($ext) && $ext !== '' ? '.'.$ext : '';
            $destRelative = $baseDir.'/profile-'.uniqid('', true).$ext;
            $path = null;

            try {
                if (! is_string($sourceCv) || $sourceCv === '' || ! $disk->exists($sourceCv)) {
                    return ApiResponse::message('Profile CV missing or inaccessible.', 422);
                }
                $disk->makeDirectory(dirname($destRelative));

                if (! $disk->copy($sourceCv, $destRelative)) {
                    throw new \RuntimeException('Unable to snapshot profile CV.');
                }

                $path = $destRelative;
                $extras = $request->safe()->only(['linkedin']);
                $linkedin = $extras['linkedin'] ?? null;
                $validated = [
                    'name' => StoreJobApplicationRequest::applicantDisplayNameFromUser($user, $profile),
                    'email' => strtolower((string) $user->email),
                    'phone' => trim((string) ($user->phone ?? '')),
                    'linkedin' => $linkedin,
                ];
            } catch (Throwable $e) {
                if (is_string($path) && $path !== '' && $disk->exists($path)) {
                    $disk->delete($path);
                }

                throw $e;
            }
        } else {
            $validated = $request->safe()->only(['name', 'email', 'phone', 'linkedin']);
            $path = null;
            try {
                $path = $request->file('cv')->store(
                    "cvs/jobs/{$jobPosting->id}/users/{$user->id}",
                    self::APPLICATION_CV_DISK,
                );
            } catch (Throwable $e) {
                if (is_string($path) && $path !== '') {
                    Storage::disk(self::APPLICATION_CV_DISK)->delete($path);
                }

                throw $e;
            }
        }

        try {
            $application = DB::transaction(function () use ($jobPosting, $validated, $path, $user) {
                return JobApplication::query()->create([
                    'job_posting_id' => $jobPosting->id,
                    'user_id' => $user->id,
                    'status' => ApplicationStatus::Pending,
                    'job_title' => $jobPosting->title,
                    'job_description' => $jobPosting->description,
                    'job_requirements' => $jobPosting->requirements,
                    'job_qualification' => $jobPosting->qualification,
                    'job_location' => $jobPosting->location,
                    'job_type' => $jobPosting->type->value,
                    'job_approved_disability' => array_values($jobPosting->approved_disability ?? []),
                    'job_skills' => array_values($jobPosting->skills ?? []),
                    'job_category' => $jobPosting->category,
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

        $application->load(array_merge([
            'jobPosting.user' => static function ($q): void {
                $q->select('id', 'email', 'profile_photo_path');
            },
            'jobPosting.user.companyProfile',
        ], JobApplication::applicantProfileWith()));

        return ApiResponse::data(
            (new JobApplicationResource($application))->resolve($request),
            201,
        );
    }
}
