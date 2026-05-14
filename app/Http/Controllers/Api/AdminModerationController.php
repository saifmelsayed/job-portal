<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAdminAccountStatusRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Mutating admin actions (moderation) for job seekers, companies, and job postings.
 */
class AdminModerationController extends Controller
{
    public function updateJobSeekerStatus(
        UpdateAdminAccountStatusRequest $request,
        User $user,
    ): JsonResponse {
        if (! $user->isJobSeeker()) {
            return ApiResponse::message(
                'Use a job seeker account id (users.id with role job_seeker) from GET /admin/users?role=job_seeker.',
                404,
            );
        }

        $user->update($request->validated());

        $user = $user->fresh();
        $user->loadMissing([
            'jobSeekerProfile',
            'skills',
            'educations',
            'experiences',
            'certificates',
        ]);

        return ApiResponse::data(
            (new UserResource($user))->resolve($request),
        );
    }

    public function updateCompanyStatus(
        UpdateAdminAccountStatusRequest $request,
        User $company,
    ): JsonResponse {
        if (! $company->isCompany()) {
            return ApiResponse::message(
                'Use a company account id (users.id with role company) from GET /admin/companies or /admin/users?role=company.',
                404,
            );
        }

        $company->update($request->validated());

        $company = $company->fresh();
        $company->loadMissing(['companyProfile']);

        return ApiResponse::data(
            (new UserResource($company))->resolve($request),
        );
    }

    public function destroyJobPosting(JobPosting $jobPosting): JsonResponse
    {
        $jobPosting->delete();

        return ApiResponse::message('Job posting deleted successfully.');
    }
}
