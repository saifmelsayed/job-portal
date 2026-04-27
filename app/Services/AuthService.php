<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    private const string TOKEN_NAME = 'api';

    public function __construct(
        private UserRepository $users
    ) {}

    /**
     * @param  array<string, mixed>  $data  Result of RegisterRequest validation.
     * @return array{user: User, token: string}
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
            $userData['skills'] = $this->normalizeSkills($data['skills']);
            $userData['cv_path'] = $data['cv_path'] ?? null;
            $userData['company_name'] = null;
            $userData['industry'] = null;
            $userData['company_size'] = null;
        } else {
            $userData['first_name'] = null;
            $userData['last_name'] = null;
            $userData['full_name'] = null;
            $userData['skills'] = null;
            $userData['cv_path'] = null;
            $userData['company_name'] = $data['company_name'];
            $userData['industry'] = $data['industry'];
            $userData['company_size'] = $data['company_size'];
        }

        $user = $this->users->create($userData);
        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
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
     * @return array{user: User, token: string}|null
     */
    public function attemptLogin(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail($email);
        if (! $user || ! Hash::check($password, $user->getAuthPassword())) {
            return null;
        }

        $token = $user->createToken(self::TOKEN_NAME)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function revokeCurrentToken(?User $user): void
    {
        $user?->currentAccessToken()?->delete();
    }
}
