<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobPostingResource;
use App\Http\Responses\ApiResponse;
use App\Models\JobPosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobSeekerJobPostingController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): JsonResponse
    {
        $postings = JobPosting::query()
            ->visibleToPublic()
            ->with([
                'user:id,company_name',
            ])
            ->latest()
            ->paginate(self::PER_PAGE);

        $data = $postings->through(
            fn (JobPosting $posting) => (new JobPostingResource($posting, includeOwnerId: false))->resolve($request),
        );

        return ApiResponse::data($data);
    }

    public function show(Request $request, JobPosting $jobPosting): JsonResponse
    {
        $isPublic = JobPosting::query()
            ->whereKey($jobPosting->getKey())
            ->visibleToPublic()
            ->exists();

        if (! $isPublic) {
            return ApiResponse::message('Job posting not found.', 404);
        }

        $jobPosting->loadMissing([
            'user:id,company_name',
        ]);

        return ApiResponse::data(
            (new JobPostingResource($jobPosting, includeOwnerId: false))->toArray($request),
        );
    }
}
