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
     * Persist a row in `users` only. Profile rows (`job_seeker_profiles`, `company_profiles`)
     * must be created in the same outer transaction by the caller (see `AuthService::register`).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        $fillable = (new User)->getFillable();

        $attributes = Arr::only(Arr::except($data, ['skills']), $fillable);

        return User::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): void
    {
        $attributes = Arr::only(Arr::except($data, ['skills']), $user->getFillable());

        if ($attributes !== []) {
            $user->update($attributes);
        }
    }
}
