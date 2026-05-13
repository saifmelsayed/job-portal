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
            if (! $user->isJobSeeker()) {
                return;
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
        });
    }

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $firstName.' '.$lastName,
            'cv_path' => null,
            'company_name' => null,
            'industry' => null,
            'company_size' => null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->e164PhoneNumber(),
            'role' => UserRole::JobSeeker,
            'status' => 'active',
            'gender' => fake()->optional(0.7)->randomElement(['male', 'female', 'other']),
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
        return $this->state(function (array $attributes) {
            $first = $attributes['first_name'] ?? fake()->firstName();
            $last = $attributes['last_name'] ?? fake()->lastName();

            return [
                'role' => UserRole::JobSeeker,
                'first_name' => $first,
                'last_name' => $last,
                'full_name' => $first.' '.$last,
                'company_name' => null,
                'industry' => null,
                'company_size' => null,
            ];
        });
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Company,
            'first_name' => null,
            'last_name' => null,
            'full_name' => null,
            'cv_path' => null,
            'company_name' => fake()->company(),
            'industry' => 'Technology',
            'company_size' => '1-10',
        ]);
    }
}
