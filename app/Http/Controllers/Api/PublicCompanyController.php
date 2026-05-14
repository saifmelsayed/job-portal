<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicCompanyResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicCompanyController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', self::PER_PAGE), 1), 100);

        $companies = User::query()
            ->where('role', UserRole::Company)
            ->where('status', 'active')
            ->with('companyProfile')
            ->withCount([
                'jobPostings as job_postings_count' => static function (Builder $query): void {
                    $query->visibleToPublic();
                },
            ])
            ->orderByDesc('id')
            ->paginate($perPage);

        $data = $companies->through(
            fn (User $user) => (new PublicCompanyResource($user))->resolve($request),
        );

        return ApiResponse::data($data);
    }

    public function show(Request $request, User $company): JsonResponse
    {
        if (! $company->isCompany() || $company->status !== 'active') {
            return ApiResponse::message('Company not found.', 404);
        }

        $company->loadMissing('companyProfile');
        $company->loadCount([
            'jobPostings as job_postings_count' => static function (Builder $query): void {
                $query->visibleToPublic();
            },
        ]);

        return ApiResponse::data(
            (new PublicCompanyResource($company))->resolve($request),
        );
    }
}
