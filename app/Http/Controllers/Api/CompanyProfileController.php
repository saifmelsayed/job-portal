<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompanyProfileRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\CompanyProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CompanyProfileController extends Controller
{
    private const string PHOTO_DISK = 'public';

    /** @var list<string> */
    private const USER_FIELDS = ['phone', 'street', 'city'];

    /** @var list<string> */
    private const PROFILE_FIELDS = [
        'company_name',
        'industry',
        'company_size',
        'disability_support_policy',
        'overview',
        'facebook_url',
        'x_url',
        'linkedin_url',
        'instagram_url',
    ];

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['companyProfile']);

        return ApiResponse::data(
            (new UserResource($user))->resolve($request),
        );
    }

    public function update(UpdateCompanyProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        unset($validated['profile_photo'], $validated['clear_profile_photo']);

        $previousPhoto = $user->profile_photo_path;
        $storedNewPhotoPath = null;

        try {
            DB::transaction(function () use ($request, $validated, &$storedNewPhotoPath): void {
                $user = $request->user();

                /** @var CompanyProfile $profile */
                $profile = $user->companyProfile()->firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'company_name' => null,
                        'industry' => null,
                        'company_size' => null,
                        'disability_support_policy' => null,
                        'overview' => null,
                        'facebook_url' => null,
                        'x_url' => null,
                        'linkedin_url' => null,
                        'instagram_url' => null,
                    ],
                );

                foreach (self::USER_FIELDS as $field) {
                    if (Arr::has($validated, $field)) {
                        $user->{$field} = $validated[$field];
                    }
                }

                foreach (self::PROFILE_FIELDS as $field) {
                    if (Arr::has($validated, $field)) {
                        $profile->{$field} = $validated[$field];
                    }
                }

                if ($request->boolean('clear_profile_photo')) {
                    $user->profile_photo_path = null;
                }

                if ($request->hasFile('profile_photo')) {
                    $storedNewPhotoPath = $request->file('profile_photo')->store(
                        'profile-photos/'.$user->id,
                        self::PHOTO_DISK,
                    );
                    $user->profile_photo_path = $storedNewPhotoPath;
                }

                $profile->save();
                $user->save();
            });
        } catch (Throwable $e) {
            if (is_string($storedNewPhotoPath) && $storedNewPhotoPath !== '') {
                Storage::disk(self::PHOTO_DISK)->delete($storedNewPhotoPath);
            }

            throw $e;
        }

        $user = $request->user()->fresh();

        if (
            $previousPhoto !== null
            && $previousPhoto !== ''
            && $user->profile_photo_path !== $previousPhoto
        ) {
            Storage::disk(self::PHOTO_DISK)->delete($previousPhoto);
        }

        $user->loadMissing(['companyProfile']);

        return ApiResponse::data(
            (new UserResource($user))->resolve($request),
        );
    }
}
