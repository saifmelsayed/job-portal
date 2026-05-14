<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Look up a job seeker by {@see User::id} when they have applied to this company's postings.
 */
class CompanyUserLookupController extends Controller
{
    public function show(Request $request, User $user): JsonResponse
    {
        if (! $user->isJobSeeker()) {
            return ApiResponse::message('User not found.', 404);
        }

        $companyId = (int) $request->user()->id;

        $linked = JobApplication::query()
            ->where('user_id', $user->id)
            ->whereHas('jobPosting', static function ($q) use ($companyId): void {
                $q->where('user_id', $companyId);
            })
            ->exists();

        if (! $linked) {
            return ApiResponse::message('User not found.', 404);
        }

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
}
