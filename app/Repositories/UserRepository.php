<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Arr;

class UserRepository
{
    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return User::query()->create(Arr::except($data, ['skills']));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): void
    {
        $user->update(Arr::except($data, ['skills']));
    }
}
