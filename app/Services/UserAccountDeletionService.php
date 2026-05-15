<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserAccountDeletionService
{
    private const string PUBLIC_DISK = 'public';

    private const string LOCAL_DISK = 'local';

    public function delete(User $user): void
    {
        $user->loadMissing(['jobSeekerProfile', 'jobApplications']);

        $photoPath = $user->profile_photo_path;
        $profileCvPath = $user->jobSeekerProfile?->cv_path;

        $applicationCvPaths = [];
        foreach ($user->jobApplications as $application) {
            $path = $application->cv_path;
            if (is_string($path) && $path !== '') {
                $applicationCvPaths[$path] = true;
            }
        }
        $applicationCvPaths = array_keys($applicationCvPaths);

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });

        $public = Storage::disk(self::PUBLIC_DISK);

        if (is_string($photoPath) && $photoPath !== '') {
            $public->delete($photoPath);
        }

        if (is_string($profileCvPath) && $profileCvPath !== '') {
            $public->delete($profileCvPath);
            Storage::disk(self::LOCAL_DISK)->delete($profileCvPath);
        }

        foreach ($applicationCvPaths as $path) {
            $public->delete($path);
        }
    }
}
