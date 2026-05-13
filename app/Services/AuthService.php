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
        ];

        if ($role === UserRole::JobSeeker) {
            [$first, $last] = $this->splitFullName($data['full_name']);
            $userData['first_name'] = $first;
            $userData['last_name'] = $last;
            $userData['full_name'] = $data['full_name'];
            $userData['cv_path'] = $data['cv_path'] ?? null;
            $userData['company_name'] = null;
            $userData['industry'] = null;
            $userData['company_size'] = null;
        } else {
            $userData['first_name'] = null;
            $userData['last_name'] = null;
            $userData['full_name'] = null;
            $userData['cv_path'] = null;
            $userData['company_name'] = $data['company_name'];
            $userData['industry'] = $data['industry'];
            $userData['company_size'] = $data['company_size'];
        }

        $userData['email_verified_at'] = now();

        return DB::transaction(function () use ($role, $data, $userData): array {
            $user = $this->users->create($userData);

            if ($role === UserRole::JobSeeker) {
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

            return [
                'user' => $user,
            ];
        });
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
