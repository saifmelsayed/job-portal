<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'full_name')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('full_name')->nullable()->after('id');
            });
        }

        if (Schema::hasTable('job_seeker_profiles') && Schema::hasColumn('job_seeker_profiles', 'full_name')) {
            $jobSeeker = UserRole::JobSeeker->value;

            foreach (DB::table('job_seeker_profiles')->cursor() as $profile) {
                $profileName = is_string($profile->full_name) ? trim($profile->full_name) : '';
                if ($profileName === '') {
                    continue;
                }

                $user = DB::table('users')
                    ->where('id', $profile->user_id)
                    ->where('role', $jobSeeker)
                    ->first(['id', 'full_name']);

                if ($user === null) {
                    continue;
                }

                $userName = is_string($user->full_name) ? trim($user->full_name) : '';
                if ($userName !== '') {
                    continue;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'full_name' => $profileName,
                ]);
            }
        }

        if (Schema::hasTable('job_seeker_profiles') && ! Schema::hasColumn('job_seeker_profiles', 'linkedin_url')) {
            Schema::table('job_seeker_profiles', function (Blueprint $table): void {
                $table->string('linkedin_url', 2048)->nullable()->after('cv_path');
            });
        }

        if (
            Schema::hasTable('job_seeker_profiles')
            && Schema::hasColumn('job_seeker_profiles', 'linkedin_url')
            && Schema::hasTable('job_applications')
            && Schema::hasColumn('job_applications', 'applicant_linkedin')
        ) {
            $latestLinkedinByUser = DB::table('job_applications')
                ->whereNotNull('applicant_linkedin')
                ->where('applicant_linkedin', '!=', '')
                ->orderByDesc('id')
                ->get(['user_id', 'applicant_linkedin'])
                ->unique('user_id')
                ->keyBy('user_id');

            foreach (DB::table('job_seeker_profiles')->cursor() as $profile) {
                $existing = is_string($profile->linkedin_url ?? null) ? trim($profile->linkedin_url) : '';
                if ($existing !== '') {
                    continue;
                }

                $row = $latestLinkedinByUser->get($profile->user_id);
                if ($row === null) {
                    continue;
                }

                $linkedin = is_string($row->applicant_linkedin) ? trim($row->applicant_linkedin) : '';
                if ($linkedin === '') {
                    continue;
                }

                DB::table('job_seeker_profiles')->where('user_id', $profile->user_id)->update([
                    'linkedin_url' => $linkedin,
                ]);
            }
        }

        if (Schema::hasTable('job_seeker_profiles') && Schema::hasColumn('job_seeker_profiles', 'full_name')) {
            Schema::table('job_seeker_profiles', function (Blueprint $table): void {
                $table->dropColumn('full_name');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('job_seeker_profiles')) {
            return;
        }

        if (! Schema::hasColumn('job_seeker_profiles', 'full_name')) {
            Schema::table('job_seeker_profiles', function (Blueprint $table): void {
                $table->string('full_name')->nullable()->after('user_id');
            });

            $jobSeeker = UserRole::JobSeeker->value;

            foreach (DB::table('job_seeker_profiles')->cursor() as $profile) {
                $user = DB::table('users')
                    ->where('id', $profile->user_id)
                    ->where('role', $jobSeeker)
                    ->first(['full_name']);

                $fullName = is_string($user->full_name ?? null) ? trim($user->full_name) : '';

                DB::table('job_seeker_profiles')->where('user_id', $profile->user_id)->update([
                    'full_name' => $fullName !== '' ? $fullName : null,
                ]);
            }
        }

        if (Schema::hasColumn('job_seeker_profiles', 'linkedin_url')) {
            Schema::table('job_seeker_profiles', function (Blueprint $table): void {
                $table->dropColumn('linkedin_url');
            });
        }
    }
};
