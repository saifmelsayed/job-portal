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
        if (Schema::hasTable('job_seeker_profiles')) {
            $this->backfillJobSeekerProfileFullNames();
            $this->dropNamePartsFromJobSeekerProfiles();
        }

        if (Schema::hasTable('users')) {
            $this->ensureUsersFullNameColumn();
            $this->backfillAdminUserFullNames();
            $this->dropNamePartsFromUsers();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'first_name')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('first_name')->nullable()->after('id');
                $table->string('last_name')->nullable()->after('first_name');
            });

            foreach (DB::table('users')->where('role', UserRole::Admin->value)->cursor() as $user) {
                [$first, $last] = $this->splitFullName((string) ($user->full_name ?? ''));
                DB::table('users')->where('id', $user->id)->update([
                    'first_name' => $first !== '' ? $first : 'Staff',
                    'last_name' => $last ?? 'Admin',
                ]);
            }

            if (Schema::hasColumn('users', 'full_name')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->dropColumn('full_name');
                });
            }
        }

        if (Schema::hasTable('job_seeker_profiles') && ! Schema::hasColumn('job_seeker_profiles', 'first_name')) {
            Schema::table('job_seeker_profiles', function (Blueprint $table): void {
                $table->string('first_name')->nullable()->after('user_id');
                $table->string('last_name')->nullable()->after('first_name');
            });

            foreach (DB::table('job_seeker_profiles')->cursor() as $profile) {
                [$first, $last] = $this->splitFullName((string) ($profile->full_name ?? ''));
                DB::table('job_seeker_profiles')->where('user_id', $profile->user_id)->update([
                    'first_name' => $first !== '' ? $first : null,
                    'last_name' => $last,
                ]);
            }
        }
    }

    private function backfillJobSeekerProfileFullNames(): void
    {
        if (! Schema::hasColumn('job_seeker_profiles', 'full_name')) {
            return;
        }

        foreach (DB::table('job_seeker_profiles')->cursor() as $profile) {
            $fullName = is_string($profile->full_name) ? trim($profile->full_name) : '';
            if ($fullName !== '') {
                continue;
            }

            $composed = $this->composeFullName(
                is_string($profile->first_name ?? null) ? $profile->first_name : null,
                is_string($profile->last_name ?? null) ? $profile->last_name : null,
            );

            if ($composed === null) {
                continue;
            }

            DB::table('job_seeker_profiles')->where('user_id', $profile->user_id)->update([
                'full_name' => $composed,
            ]);
        }
    }

    private function dropNamePartsFromJobSeekerProfiles(): void
    {
        $drop = array_values(array_filter([
            Schema::hasColumn('job_seeker_profiles', 'first_name') ? 'first_name' : null,
            Schema::hasColumn('job_seeker_profiles', 'last_name') ? 'last_name' : null,
        ]));

        if ($drop === []) {
            return;
        }

        Schema::table('job_seeker_profiles', function (Blueprint $table) use ($drop): void {
            $table->dropColumn($drop);
        });
    }

    private function ensureUsersFullNameColumn(): void
    {
        if (Schema::hasColumn('users', 'full_name')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $after = Schema::hasColumn('users', 'last_name') ? 'last_name' : 'id';
            $table->string('full_name')->nullable()->after($after);
        });
    }

    private function backfillAdminUserFullNames(): void
    {
        if (! Schema::hasColumn('users', 'full_name')) {
            return;
        }

        foreach (DB::table('users')->where('role', UserRole::Admin->value)->cursor() as $user) {
            $fullName = is_string($user->full_name) ? trim($user->full_name) : '';
            if ($fullName !== '') {
                continue;
            }

            $composed = $this->composeFullName(
                is_string($user->first_name ?? null) ? $user->first_name : null,
                is_string($user->last_name ?? null) ? $user->last_name : null,
            );

            DB::table('users')->where('id', $user->id)->update([
                'full_name' => $composed ?? 'Staff Admin',
            ]);
        }
    }

    private function dropNamePartsFromUsers(): void
    {
        $drop = array_values(array_filter([
            Schema::hasColumn('users', 'first_name') ? 'first_name' : null,
            Schema::hasColumn('users', 'last_name') ? 'last_name' : null,
        ]));

        if ($drop === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($drop): void {
            $table->dropColumn($drop);
        });
    }

    private function composeFullName(?string $first, ?string $last): ?string
    {
        $parts = array_values(array_filter(
            [is_string($first) ? trim($first) : '', is_string($last) ? trim($last) : ''],
            static fn (string $part): bool => $part !== '',
        ));

        if ($parts === []) {
            return null;
        }

        return implode(' ', $parts);
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function splitFullName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            return ['', null];
        }

        $parts = preg_split('/\s+/', $fullName, 2);
        $first = $parts[0] ?? '';
        $last = isset($parts[1]) ? trim($parts[1]) : null;
        if ($last === '') {
            $last = null;
        }

        return [$first, $last];
    }
};
