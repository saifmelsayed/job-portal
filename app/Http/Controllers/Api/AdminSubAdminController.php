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
            ->with('admin')
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
            'first_name' => $validated['first_name'] ?? 'Staff',
            'last_name' => $validated['last_name'] ?? 'Admin',
            'phone' => null,
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $admin->admin()->create([
            'is_super_admin' => false,
        ]);

        return ApiResponse::data(
            (new AdminAccountResource($admin->loadMissing('admin')))->toArray($request),
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
            (new AdminAccountResource($user->loadMissing('admin')))->toArray($request),
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
            (new AdminAccountResource($user->fresh()->loadMissing('admin')))->toArray($request),
        );
    }

    /**
     * Deactivate an admin: set status inactive and revoke all tokens.
     */
    public function deactivate(Request $request, User $user): JsonResponse
    {
        if (! $user->isAdmin()) {
            return ApiResponse::message('Not found.', 404);
        }

        if ($this->isOnlyActiveSuperAdmin($user)) {
            return ApiResponse::message('You cannot deactivate the only active super admin.', 422);
        }

        if ($user->status === 'inactive') {
            return ApiResponse::message('Admin is already inactive.', 422);
        }

        $user->status = 'inactive';
        $user->save();
        $user->tokens()->delete();

        return ApiResponse::data(
            (new AdminAccountResource($user->fresh()->loadMissing('admin')))->toArray($request),
        );
    }

    /**
     * Reactivate an admin (does not restore old tokens; user must log in again).
     */
    public function activate(Request $request, User $user): JsonResponse
    {
        if (! $user->isAdmin()) {
            return ApiResponse::message('Not found.', 404);
        }

        if ($user->status === 'active') {
            return ApiResponse::message('Admin is already active.', 422);
        }

        $user->status = 'active';
        $user->save();

        return ApiResponse::data(
            (new AdminAccountResource($user->fresh()->loadMissing('admin')))->toArray($request),
        );
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
            ->where('status', 'active')
            ->whereHas('admin', static function ($q): void {
                $q->where('is_super_admin', true);
            })
            ->count();

        return $activeSupers === 1;
    }
}
