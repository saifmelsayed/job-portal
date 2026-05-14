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
            ->whereHas('admin', static function ($q): void {
                $q->where('is_super_admin', true);
            })
            ->exists();

        if ($alreadyHasSuperAdmin) {
            return;
        }

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            return;
        }

        $user = User::query()->create([
            'email' => strtolower(trim($email)),
            'password' => $password,
            'role' => UserRole::Admin,
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'phone' => null,
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $user->admin()->create([
            'is_super_admin' => true,
        ]);
    }
}
