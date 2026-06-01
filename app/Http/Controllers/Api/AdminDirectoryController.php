<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\JobApplicationResource;
use App\Http\Resources\JobPostingResource;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-only lists for the admin dashboard (super + staff admins).
 */
class AdminDirectoryController extends Controller
{
    private function perPage(Request $request): int
    {
        return min((int) $request->integer('per_page', 15), 100);
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @param  list<array<string, mixed>>  $items
     * @return array{items: list<array<string, mixed>>, pagination: array{total: int, per_page: int, current_page: int, last_page: int}}
     */
    private function wrapPaginated(LengthAwarePaginator $paginator, array $items): array
    {
        return [
            'items' => $items,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    /**
     * All users. Optional query: role=job_seeker|company|admin
     */
    public function users(Request $request): JsonResponse
    {
        $role = $request->query('role');
        if ($role !== null && $role !== '') {
            $allowed = [
                UserRole::JobSeeker->value,
                UserRole::Company->value,
                UserRole::Admin->value,
            ];
            if (! is_string($role) || ! in_array($role, $allowed, true)) {
                return ApiResponse::message('Invalid role. Use job_seeker, company, or admin.', 422);
            }
        }

        $with = [
            'jobSeekerProfile',
            'companyProfile',
            'admin',
        ];

        $loadSeekerNested = $role === null || $role === '' || $role === UserRole::JobSeeker->value;
        if ($loadSeekerNested) {
            $with = array_merge($with, [
                'skills',
                'educations',
                'experiences',
                'certificates',
            ]);
        }

        $query = User::query()
            ->with($with)
            ->orderByDesc('id');

        if ($role !== null && $role !== '') {
            $query->where('role', $role);
        }

        $paginator = $query->paginate($this->perPage($request));

        $items = collect($paginator->items())->map(function (User $user) use ($request) {
            return (new UserResource($user))->resolve($request);
        })->values()->all();

        return ApiResponse::data($this->wrapPaginated($paginator, $items));
    }

    /**
     * All company accounts (same as users?role=company, kept for a clear URL).
     */
    public function companies(Request $request): JsonResponse
    {
        $paginator = User::query()
            ->where('role', UserRole::Company)
            ->with('companyProfile')
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        $items = collect($paginator->items())->map(function (User $user) use ($request) {
            return (new UserResource($user))->resolve($request);
        })->values()->all();

        return ApiResponse::data($this->wrapPaginated($paginator, $items));
    }

    /**
     * All job postings everywhere.
     */
    public function jobPostings(Request $request): JsonResponse
    {
        $paginator = JobPosting::query()
            ->with([
                'user' => static function ($q): void {
                    $q->select('id', 'email', 'profile_photo_path');
                },
                'user.companyProfile',
            ])
            ->withCount('applications')
            ->latest()
            ->paginate($this->perPage($request));

        $items = collect($paginator->items())->map(function (JobPosting $posting) use ($request) {
            $row = (new JobPostingResource($posting, includeOwnerId: true))->toArray($request);
            $row['applications_count'] = $posting->applications_count;

            return $row;
        })->values()->all();

        return ApiResponse::data($this->wrapPaginated($paginator, $items));
    }

    /**
     * Every application (all companies).
     */
    public function applications(Request $request): JsonResponse
    {
        $paginator = JobApplication::query()
            ->with(JobApplication::applicantProfileWith())
            ->latest()
            ->paginate($this->perPage($request));

        $items = collect($paginator->items())->map(function (JobApplication $application) use ($request) {
            return (new JobApplicationResource($application))->resolve($request);
        })->values()->all();

        return ApiResponse::data($this->wrapPaginated($paginator, $items));
    }

    /**
     * One job seeker: every row in job_applications where user_id = this seeker's users.id.
     *
     * URL {job_seeker} is route-model-bound to User (their primary key users.id — not job_applications.id).
     */
    public function seekerApplications(Request $request, User $job_seeker): JsonResponse
    {
        if (! $job_seeker->isJobSeeker()) {
            return ApiResponse::message(
                'Use a job seeker account id (users.id with role job_seeker) from GET /admin/users?role=job_seeker.',
                404,
            );
        }

        $paginator = JobApplication::query()
            ->where('user_id', $job_seeker->id)
            ->with(JobApplication::applicantProfileWith())
            ->latest()
            ->paginate($this->perPage($request));

        $items = collect($paginator->items())->map(function (JobApplication $application) use ($request) {
            return (new JobApplicationResource($application))->resolve($request);
        })->values()->all();

        return ApiResponse::data(array_merge(
            [
                'seeker_users_id' => $job_seeker->id,
            ],
            $this->wrapPaginated($paginator, $items),
        ));
    }

    /**
     * One company owner: job postings owned by users.id — each row includes applications_count.
     *
     * URL {company} is route-model-bound to User (the company row's users.id — not job_postings.id).
     */
    public function companyJobPostings(Request $request, User $company): JsonResponse
    {
        if (! $company->isCompany()) {
            return ApiResponse::message(
                'Use a company account id (users.id with role company) from GET /admin/companies or /admin/users?role=company.',
                404,
            );
        }

        $paginator = $company->jobPostings()
            ->with([
                'user' => static function ($q): void {
                    $q->select('id', 'email', 'profile_photo_path');
                },
                'user.companyProfile',
            ])
            ->withCount('applications')
            ->latest()
            ->paginate($this->perPage($request));

        $items = collect($paginator->items())->map(function (JobPosting $posting) use ($request) {
            $row = (new JobPostingResource($posting, includeOwnerId: true))->toArray($request);
            $row['applications_count'] = $posting->applications_count;

            return $row;
        })->values()->all();

        return ApiResponse::data(array_merge(
            [
                'company_owner_users_id' => $company->id,
            ],
            $this->wrapPaginated($paginator, $items),
        ));
    }

    /**
     * Simple totals for dashboard cards.
     */
    public function summary(): JsonResponse
    {
        return ApiResponse::data([
            'users_total' => User::query()->count(),
            'job_seekers_total' => User::query()->where('role', UserRole::JobSeeker)->count(),
            'companies_total' => User::query()->where('role', UserRole::Company)->count(),
            'admins_total' => User::query()->where('role', UserRole::Admin)->count(),
            'job_postings_total' => JobPosting::query()->count(),
            'job_applications_total' => JobApplication::query()->count(),
        ]);
    }
}