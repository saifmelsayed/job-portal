<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    private const string TOKEN_NAME = 'api';

    public function __construct(
        private UserRepository $users
    ) {}

    /**
     * @param  array<string, mixed>  $data  Result of RegisterRequest validation.
     * @return array{user: User}
     */
    public function register(array $data): array
    {
        $role = UserRole::from($data['role']);

        $userData = [
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'role' => $role,
            'status' => 'active',
            'email_verified_at' => now(),
        ];

        return DB::transaction(function () use ($role, $data, $userData): array {
            $user = $this->users->create($userData);

            if ($role === UserRole::JobSeeker) {
                $user->jobSeekerProfile()->create([
                    'full_name' => $data['full_name'],
                    'cv_path' => $data['cv_path'] ?? null,
                    'gender' => null,
                    'disability_type' => null,
                ]);

                $seen = [];
                $order = 0;
                foreach ($this->normalizeSkills($data['skills']) as $name) {
                    $trimmed = mb_substr(trim($name), 0, 100);
                    if ($trimmed === '') {
                        continue;
                    }
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
                $user->unsetRelation('skills');
            }

            if ($role === UserRole::Company) {
                $user->companyProfile()->create([
                    'company_name' => $data['company_name'],
                    'industry' => $data['industry'],
                    'company_size' => $data['company_size'],
                    'disability_support_policy' => null,
                ]);
            }

            return [
                'user' => $user,
            ];
        });
    }

    /**
     * @param  list<string>  $skills
     * @return list<string>
     */
    private function normalizeSkills(array $skills): array
    {
        return array_values(array_filter(
            array_map('trim', $skills),
            fn (string $s): bool => $s !== ''
        ));
    }

    /**
     * @return array{user: User, token: string}|array{disabled: true}|null
     */
    public function attemptLogin(string $email, string $password): array|null
    {
        $user = $this->users->findByEmail($email);
        if (! $user || ! Hash::check($password, $user->getAuthPassword())) {
            return null;
        }

        if ($user->status !== 'active') {
            return ['disabled' => true];
        }

        if ($user->isAdmin()) {
            return null;
        }

        return [
            'user' => $user,
            'token' => $user->createToken(self::TOKEN_NAME)->plainTextToken,
        ];
    }

    /**
     * Login for administrator accounts only (separate from job seeker / company login).
     *
     * @return array{user: User, token: string}|array{disabled: true}|null
     */
    public function attemptAdminLogin(string $email, string $password): array|null
    {
        $user = $this->users->findByEmail($email);
        if (! $user || ! Hash::check($password, $user->getAuthPassword())) {
            return null;
        }

        if ($user->status !== 'active') {
            return ['disabled' => true];
        }

        if (! $user->isAdmin()) {
            return null;
        }

        return [
            'user' => $user,
            'token' => $user->createToken(self::TOKEN_NAME)->plainTextToken,
        ];
    }

    public function revokeCurrentToken(?User $user): void
    {
        $user?->currentAccessToken()?->delete();
    }
}
