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
                    $first = fake()->firstName();
                    $last = fake()->lastName();
                    $user->jobSeekerProfile()->create([
                        'first_name' => $first,
                        'last_name' => $last,
                        'full_name' => $first.' '.$last,
                        'cv_path' => null,
                        'gender' => fake()->optional(0.7)->randomElement(['male', 'female', 'other']),
                        'disability_type' => null,
                    ]);
                }

                if ($user->skills()->exists()) {
                    return;
                }

                foreach (['Laravel', 'PHP', 'MySQL'] as $index => $skill) {
                    $user->skills()->create([
                        'name' => $skill,
                        'sort_order' => $index,
                    ]);
                }

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
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
        ])->afterCreating(function (User $user): void {
            $user->admin?->update(['is_super_admin' => true]);
        });
    }
}
