<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubAdminRequest;
use App\Http\Requests\UpdateSubAdminRequest;
use App\Http\Resources\AdminAccountResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSubAdminController extends Controller
{
    /**
     * List every admin account (super + staff).
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = User::query()
            ->where('role', UserRole::Admin)
            ->orderBy('id')
            ->paginate(perPage: min((int) $request->integer('per_page', 15), 100));

        $items = collect($paginator->items())->map(function (User $user) use ($request) {
            return (new AdminAccountResource($user))->toArray($request);
        })->values()->all();

        return ApiResponse::data([
            'items' => $items,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Create a staff admin (never a super admin).
     */
    public function store(StoreSubAdminRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $admin = User::query()->create([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => UserRole::Admin,
            'is_super_admin' => false,
            'first_name' => $validated['first_name'] ?? 'Staff',
            'last_name' => $validated['last_name'] ?? 'Admin',
            'full_name' => null,
            'phone' => null,
            'cv_path' => null,
            'company_name' => null,
            'industry' => null,
            'company_size' => null,
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        return ApiResponse::data(
            (new AdminAccountResource($admin))->toArray($request),
            201,
        );
    }

    /**
     * Show one admin account.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        if (! $user->isAdmin()) {
            return ApiResponse::message('Not found.', 404);
        }

        return ApiResponse::data(
            (new AdminAccountResource($user))->toArray($request),
        );
    }

    /**
     * Update a staff admin, or change password / email.
     * Will not remove the last active super admin.
     */
    public function update(UpdateSubAdminRequest $request, User $user): JsonResponse
    {
        if (! $user->isAdmin()) {
            return ApiResponse::message('Not found.', 404);
        }

        $validated = $request->validated();

        if (array_key_exists('status', $validated) && $validated['status'] === 'inactive') {
            if ($this->isOnlyActiveSuperAdmin($user)) {
                return ApiResponse::message('You cannot deactivate the only active super admin.', 422);
            }
        }

        $user->fill(array_intersect_key($validated, array_flip([
            'email',
            'password',
            'first_name',
            'last_name',
            'status',
        ])));

        $user->save();

        if ($user->wasChanged('status') && $user->status === 'inactive') {
            $user->tokens()->delete();
        }

        return ApiResponse::data(
            (new AdminAccountResource($user->fresh()))->toArray($request),
        );
    }

    /**
     * Soft “delete”: set inactive and revoke all tokens (same safety as update).
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if (! $user->isAdmin()) {
            return ApiResponse::message('Not found.', 404);
        }

        if ($this->isOnlyActiveSuperAdmin($user)) {
            return ApiResponse::message('You cannot deactivate the only active super admin.', 422);
        }

        $user->status = 'inactive';
        $user->save();
        $user->tokens()->delete();

        return ApiResponse::message('Admin deactivated and logged out everywhere.');
    }

    /**
     * True when this user is the only super admin that is still active.
     */
    private function isOnlyActiveSuperAdmin(User $user): bool
    {
        if (! $user->isSuperAdmin() || $user->status !== 'active') {
            return false;
        }

        $activeSupers = User::query()
            ->where('role', UserRole::Admin)
            ->where('is_super_admin', true)
            ->where('status', 'active')
            ->count();

        return $activeSupers === 1;
    }
}
