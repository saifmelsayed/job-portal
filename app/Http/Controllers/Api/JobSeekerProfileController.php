<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
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
    private const SCALAR_USER_KEYS = ['phone', 'gender', 'city', 'street'];

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        unset($validated['cv'], $validated['profile_photo'], $validated['clear_profile_photo']);

        $previousCvPath = $user->cv_path;
        $storedCvRelativePath = null;
        $previousProfilePhotoPath = $user->profile_photo_path;
        $storedProfilePhotoPath = null;

        try {
            DB::transaction(function () use ($request, $validated, &$storedCvRelativePath, &$storedProfilePhotoPath): void {
                $user = $request->user();

                foreach (self::SCALAR_USER_KEYS as $field) {
                    if (Arr::exists($validated, $field)) {
                        $user->{$field} = $validated[$field];
                    }
                }

                if (\array_key_exists('full_name', $validated)) {
                    [$first, $last] = $this->splitFullName((string) $validated['full_name']);
                    $user->first_name = $first;
                    $user->last_name = $last;
                    $user->full_name = $validated['full_name'];
                }

                if (\array_key_exists('skills', $validated)) {
                    $user->skills()->delete();
                    $seen = [];
                    $order = 0;
                    foreach ($this->normalizeSkillNames(is_array($validated['skills']) ? $validated['skills'] : []) as $trimmed) {
                        $key = mb_strtolower($trimmed);
                        if (isset($seen[$key])) {
                            continue;
                        }
                        $seen[$key] = true;
                        $user->skills()->create([
                            'name' => $trimmed,
                            'sort_order' => $order,
                        ]);
                        $order++;
                    }
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
                    $user->cv_path = $storedCvRelativePath;
                }

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
            'skills',
            'educations',
            'experiences',
            'certificates',
        ]);

        return ApiResponse::data(
            (new UserResource($user))->toArray($request),
        );
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function splitFullName(string $fullName): array
    {
        $fullName = trim($fullName);
        $parts = preg_split('/\s+/', $fullName, 2);

        $first = $parts[0] ?? '';
        $last = isset($parts[1]) ? trim($parts[1]) : null;
        if ($last === '') {
            $last = null;
        }

        return [$first, $last];
    }

    /**
     * @param  array<int, mixed>  $skills
     * @return list<string>
     */
    private function normalizeSkillNames(array $skills): array
    {
        $out = [];
        foreach ($skills as $s) {
            if (! is_string($s)) {
                continue;
            }
            $t = mb_substr(trim($s), 0, 100);
            if ($t === '') {
                continue;
            }
            $out[] = $t;
        }

        return $out;
    }
}
