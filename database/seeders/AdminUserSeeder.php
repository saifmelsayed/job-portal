<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates one super admin from .env — safe to run more than once.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $alreadyHasSuperAdmin = User::query()
            ->where('role', UserRole::Admin)
            ->where('is_super_admin', true)
            ->exists();

        if ($alreadyHasSuperAdmin) {
            return;
        }

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            return;
        }

        User::query()->create([
            'email' => strtolower(trim($email)),
            'password' => $password,
            'role' => UserRole::Admin,
            'is_super_admin' => true,
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'full_name' => null,
            'phone' => null,
            'cv_path' => null,
            'company_name' => null,
            'industry' => null,
            'company_size' => null,
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
    }
}
