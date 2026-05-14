<?php

namespace App\Http\Controllers\Api;

use App\Enums\JobWorkType;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexPublicJobPostingsRequest;
use App\Http\Resources\JobPostingResource;
use App\Http\Responses\ApiResponse;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobSeekerJobPostingController extends Controller
{
    private const PER_PAGE = 15;

    public function index(IndexPublicJobPostingsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        /** @var array<string, mixed>|null $search */
        $search = $validated['search'] ?? null;

        $query = JobPosting::query()->visibleToPublic();

        if (is_array($search)) {
            self::applySearch($query, $search);
        }

        $postings = $query
            ->with([
                'user' => static function ($q): void {
                    $q->select('id', 'email', 'profile_photo_path');
                },
                'user.companyProfile',
            ])
            ->latest()
            ->paginate(self::PER_PAGE);

        $data = $postings->through(
            fn (JobPosting $posting) => (new JobPostingResource($posting, includeOwnerId: false))->resolve($request),
        );

        return ApiResponse::data($data);
    }

    /**
     * @param  array<string, mixed>  $search
     */
    private static function applySearch(Builder $query, array $search): void
    {
        if (isset($search['_general'])) {
            $general = trim((string) $search['_general']);
            if ($general !== '') {
                self::applyGeneralKeywordSearch($query, $general);
            }
        }

        $title = isset($search['job_title']) ? trim((string) $search['job_title']) : '';
        if ($title !== '') {
            $like = '%'.addcslashes($title, '\\%_').'%';
            $query->where('title', 'like', $like);
        }

        if (isset($search['job_type']) && $search['job_type'] !== null && $search['job_type'] !== '') {
            $type = $search['job_type'];
            $value = $type instanceof JobWorkType ? $type->value : strtolower(trim((string) $type));
            $column = $query->qualifyColumn('type');
            $query->where($column, '=', $value);
        }

        $disability = isset($search['disability_type']) ? trim((string) $search['disability_type']) : '';
        if ($disability !== '') {
            self::applyDisabilityFilter($query, $disability);
        }

        $location = isset($search['location']) ? trim((string) $search['location']) : '';
        if ($location !== '') {
            $like = '%'.addcslashes($location, '\\%_').'%';
            $query->where('location', 'like', $like);
        }

        $industry = isset($search['company_industry']) ? trim((string) $search['company_industry']) : '';
        if ($industry !== '') {
            $like = '%'.addcslashes($industry, '\\%_').'%';
            $query->whereHas('user', static function (Builder $userQuery) use ($like): void {
                $userQuery->whereHas('companyProfile', static function (Builder $profileQuery) use ($like): void {
                    $profileQuery->where('industry', 'like', $like);
                });
            });
        }
    }

    private static function applyGeneralKeywordSearch(Builder $query, string $keyword): void
    {
        $like = '%'.addcslashes(mb_strtolower($keyword), '\\%_').'%';

        $query->where(function (Builder $inner) use ($like): void {
            $inner->whereRaw('LOWER('.$inner->qualifyColumn('title').') LIKE ?', [$like])
                ->orWhereRaw('LOWER('.$inner->qualifyColumn('location').') LIKE ?', [$like])
                ->orWhereRaw('LOWER('.$inner->qualifyColumn('type').') LIKE ?', [$like])
                ->orWhereHas('user.companyProfile', static function (Builder $profileQuery) use ($like): void {
                    $profileQuery->whereRaw('LOWER(industry) LIKE ?', [$like]);
                });

            $driver = self::connectionDriver($inner);
            $cast = $driver === 'sqlite' ? 'TEXT' : 'CHAR';
            $inner->orWhereRaw(
                'LOWER(CAST(approved_disability AS '.$cast.')) LIKE ?',
                [$like],
            );
        });
    }

    private static function applyDisabilityFilter(Builder $query, string $needle): void
    {
        $like = '%'.addcslashes(mb_strtolower($needle), '\\%_').'%';

        $query->where(function (Builder $inner) use ($needle, $like): void {
            $inner->whereJsonContains('approved_disability', $needle);

            $driver = self::connectionDriver($inner);
            $cast = $driver === 'sqlite' ? 'TEXT' : 'CHAR';
            $inner->orWhereRaw(
                'LOWER(CAST(approved_disability AS '.$cast.')) LIKE ?',
                [$like],
            );
        });
    }

    private static function connectionDriver(Builder $query): string
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $query->getConnection();

        return $connection->getDriverName();
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
            'user' => static function ($q): void {
                $q->select('id', 'email', 'profile_photo_path');
            },
            'user.companyProfile',
        ]);

        return ApiResponse::data(
            (new JobPostingResource($jobPosting, includeOwnerId: false))->toArray($request),
        );
    }
}
