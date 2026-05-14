<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobPostingRequest;
use App\Http\Requests\UpdateJobApplicationStatusRequest;
use App\Http\Requests\UpdateJobPostingRequest;
use App\Http\Resources\CompanyJobApplicationResource;
use App\Http\Resources\JobPostingResource;
use App\Http\Responses\ApiResponse;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyJobPostingController extends Controller
{
    private const int APPLICATIONS_PER_PAGE = 15;

    /** All applicants across every job belonging to this company. */
    public function allApplications(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', self::APPLICATIONS_PER_PAGE), 1), 100);

        $companyOwnerId = $request->user()->id;

        $applications = JobApplication::query()
            ->whereHas('jobPosting', static function ($query) use ($companyOwnerId): void {
                $query->where('user_id', $companyOwnerId);
            })
            ->with(array_merge([
                'jobPosting' => static function ($q): void {
                    $q->select('id', 'user_id', 'title');
                },
            ], JobApplication::applicantProfileWith()))
            ->latest()
            ->paginate($perPage);

        $data = $applications->through(
            fn (JobApplication $application): array => (new CompanyJobApplicationResource($application))->resolve($request),
        );

        return ApiResponse::data($data);
    }

    public function index(Request $request): JsonResponse
    {
        $postings = $request->user()
            ->jobPostings()
            ->with([
                'user' => static function ($q): void {
                    $q->select('id', 'email', 'profile_photo_path');
                },
                'user.companyProfile',
            ])
            ->latest()
            ->get();

        $data = $postings
            ->map(fn (JobPosting $posting) => (new JobPostingResource($posting))->toArray($request))
            ->values()
            ->all();

        return ApiResponse::data($data);
    }

    public function store(StoreJobPostingRequest $request): JsonResponse
    {
        $posting = $request->user()->jobPostings()->create($request->validated());

        $posting->load([
            'user' => static function ($q): void {
                $q->select('id', 'email', 'profile_photo_path');
            },
            'user.companyProfile',
        ]);

        return ApiResponse::data(
            (new JobPostingResource($posting))->toArray($request),
            201,
        );
    }

    public function show(Request $request, JobPosting $jobPosting): JsonResponse
    {
        $this->ensureOwnedByUser($request, $jobPosting);

        $jobPosting->loadMissing([
            'user' => static function ($q): void {
                $q->select('id', 'email', 'profile_photo_path');
            },
            'user.companyProfile',
        ]);

        return ApiResponse::data(
            (new JobPostingResource($jobPosting))->toArray($request),
        );
    }

    public function applications(Request $request, JobPosting $jobPosting): JsonResponse
    {
        $this->ensureOwnedByUser($request, $jobPosting);

        $applications = $jobPosting->applications()
            ->with(JobApplication::applicantProfileWith())
            ->latest()
            ->get();

        $data = $applications
            ->map(fn (JobApplication $application) => (new CompanyJobApplicationResource($application))->resolve($request))
            ->values()
            ->all();

        return ApiResponse::data($data);
    }

    public function updateApplicationStatus(
        UpdateJobApplicationStatusRequest $request,
        JobPosting $jobPosting,
        JobApplication $jobApplication,
    ): JsonResponse {
        $this->ensureOwnedByUser($request, $jobPosting);
        $this->ensureApplicationBelongsToPosting($jobPosting, $jobApplication);

        $jobApplication->update($request->validated());

        return ApiResponse::data(
            (new CompanyJobApplicationResource(
                $jobApplication->fresh(JobApplication::applicantProfileWith())
            ))->resolve($request),
        );
    }

    public function update(UpdateJobPostingRequest $request, JobPosting $jobPosting): JsonResponse
    {
        $this->ensureOwnedByUser($request, $jobPosting);

        $jobPosting->update($request->validated());

        $jobPosting = $jobPosting->fresh()->load([
            'user' => static function ($q): void {
                $q->select('id', 'email', 'profile_photo_path');
            },
            'user.companyProfile',
        ]);

        return ApiResponse::data(
            (new JobPostingResource($jobPosting))->toArray($request),
        );
    }

    public function destroy(Request $request, JobPosting $jobPosting): JsonResponse
    {
        $this->ensureOwnedByUser($request, $jobPosting);

        $jobPosting->delete();

        return ApiResponse::message('Job posting deleted successfully');
    }

    private function ensureOwnedByUser(Request $request, JobPosting $jobPosting): void
    {
        abort_unless(
            $jobPosting->user_id === $request->user()->id,
            404
        );
    }

    private function ensureApplicationBelongsToPosting(JobPosting $jobPosting, JobApplication $jobApplication): void
    {
        abort_unless(
            $jobApplication->job_posting_id === $jobPosting->id,
            404
        );
    }
}
