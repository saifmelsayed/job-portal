<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\JobSeekerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class JobSeekerProfileController extends Controller
{
    private const string PROFILE_PHOTO_DISK = 'public';

    private const string CV_DISK = 'public';

    /** @var list<string> */
    private const SCALAR_USER_KEYS = ['full_name', 'phone', 'city', 'street'];

    /** @var list<string> */
    private const SCALAR_PROFILE_KEYS = ['gender', 'disability_type', 'linkedin_url'];

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        unset($validated['cv'], $validated['profile_photo'], $validated['clear_profile_photo']);

        $previousCvPath = $user->jobSeekerProfile?->cv_path;
        $storedCvRelativePath = null;
        $previousProfilePhotoPath = $user->profile_photo_path;
        $storedProfilePhotoPath = null;

        try {
            DB::transaction(function () use ($request, $validated, &$storedCvRelativePath, &$storedProfilePhotoPath): void {
                $user = $request->user();

                /** @var JobSeekerProfile $profile */
                $profile = $user->jobSeekerProfile()->firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'cv_path' => null,
                        'gender' => null,
                        'disability_type' => null,
                    ],
                );

                foreach (self::SCALAR_USER_KEYS as $field) {
                    if (Arr::exists($validated, $field)) {
                        $user->{$field} = $validated[$field];
                    }
                }

                foreach (self::SCALAR_PROFILE_KEYS as $field) {
                    if (Arr::exists($validated, $field)) {
                        $profile->{$field} = $validated[$field];
                    }
                }

                if (\array_key_exists('skills', $validated)) {
                    $user->syncSkillsFromNames(
                        is_array($validated['skills']) ? $validated['skills'] : [],
                    );
                }

                if (\array_key_exists('educations', $validated)) {
                    $user->educations()->delete();
                    $rows = is_array($validated['educations']) ? $validated['educations'] : [];
                    foreach ($rows as $row) {
                        if (! is_array($row)) {
                            continue;
                        }
                        $user->educations()->create([
                            'institution' => $row['institution'],
                            'degree' => $row['degree'] ?? null,
                            'field_of_study' => $row['field_of_study'] ?? null,
                            'starts_at' => $row['starts_at'] ?? null,
                            'ends_at' => $row['ends_at'] ?? null,
                            'details' => $row['details'] ?? null,
                        ]);
                    }
                }

                if (\array_key_exists('experiences', $validated)) {
                    $user->experiences()->delete();
                    $rows = is_array($validated['experiences']) ? $validated['experiences'] : [];
                    foreach ($rows as $row) {
                        if (! is_array($row)) {
                            continue;
                        }
                        $user->experiences()->create([
                            'company_name' => $row['company_name'],
                            'title' => $row['title'],
                            'starts_at' => $row['starts_at'] ?? null,
                            'ends_at' => $row['ends_at'] ?? null,
                            'description' => $row['description'] ?? null,
                        ]);
                    }
                }

                if (\array_key_exists('certificates', $validated)) {
                    $user->certificates()->delete();
                    $rows = is_array($validated['certificates']) ? $validated['certificates'] : [];
                    foreach ($rows as $row) {
                        if (! is_array($row)) {
                            continue;
                        }
                        $user->certificates()->create([
                            'name' => $row['name'],
                            'issuer' => $row['issuer'] ?? null,
                            'issued_at' => $row['issued_at'] ?? null,
                            'credential_url' => $row['credential_url'] ?? null,
                        ]);
                    }
                }

                if ($request->boolean('clear_profile_photo')) {
                    $user->profile_photo_path = null;
                }

                if ($request->hasFile('profile_photo')) {
                    $storedProfilePhotoPath = $request->file('profile_photo')->store(
                        'profile-photos/'.$user->id,
                        self::PROFILE_PHOTO_DISK,
                    );
                    $user->profile_photo_path = $storedProfilePhotoPath;
                }

                if ($request->hasFile('cv')) {
                    $storedCvRelativePath = $request->file('cv')->store(
                        'profile/cvs/'.$user->id,
                        self::CV_DISK,
                    );
                    $profile->cv_path = $storedCvRelativePath;
                }

                $profile->save();
                $user->save();
            });
        } catch (Throwable $e) {
            if (is_string($storedCvRelativePath) && $storedCvRelativePath !== '') {
                Storage::disk(self::CV_DISK)->delete($storedCvRelativePath);
            }
            if (is_string($storedProfilePhotoPath) && $storedProfilePhotoPath !== '') {
                Storage::disk(self::PROFILE_PHOTO_DISK)->delete($storedProfilePhotoPath);
            }

            throw $e;
        }

        if ($request->hasFile('cv') && is_string($previousCvPath) && $previousCvPath !== '') {
            Storage::disk(self::CV_DISK)->delete($previousCvPath);
            Storage::disk('local')->delete($previousCvPath);
        }

        $user = $request->user()->fresh();

        if (
            is_string($previousProfilePhotoPath) && $previousProfilePhotoPath !== ''
            && $user->profile_photo_path !== $previousProfilePhotoPath
        ) {
            Storage::disk(self::PROFILE_PHOTO_DISK)->delete($previousProfilePhotoPath);
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
