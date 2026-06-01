<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            if ($user->isAdmin()) {
                if ($user->admin === null) {
                    $user->admin()->create(['is_super_admin' => false]);
                }

                return;
            }

            if ($user->isJobSeeker()) {
                if ($user->jobSeekerProfile === null) {
                    $user->jobSeekerProfile()->create([
                        'cv_path' => null,
                        'gender' => fake()->optional(0.7)->randomElement(['male', 'female', 'other']),
                        'disability_type' => null,
                    ]);
                }

                if ($user->full_name === null || $user->full_name === '') {
                    $user->update(['full_name' => fake()->name()]);
                }

                if ($user->skills()->exists()) {
                    return;
                }

                $user->syncSkillsFromNames(['Laravel', 'PHP', 'MySQL']);

                return;
            }

            if ($user->isCompany() && $user->companyProfile === null) {
                $user->companyProfile()->create([
                    'company_name' => fake()->company(),
                    'industry' => 'Technology',
                    'company_size' => '1-10',
                    'disability_support_policy' => null,
                ]);
            }
        });
    }

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->e164PhoneNumber(),
            'role' => UserRole::JobSeeker,
            'status' => 'active',
            'full_name' => fake()->name(),
            'city' => fake()->optional(0.8)->city(),
            'street' => fake()->optional(0.5)->streetAddress(),
            'profile_photo_path' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function jobSeeker(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::JobSeeker,
        ]);
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Company,
            'full_name' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
            'full_name' => fake()->name(),
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
            'full_name' => fake()->name(),
        ])->afterCreating(function (User $user): void {
            $user->admin?->update(['is_super_admin' => true]);
        });
    }
}
