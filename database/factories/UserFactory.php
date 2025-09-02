<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'last_login_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'last_activity_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
            'timezone' => fake()->randomElement(['America/Guatemala']),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is recently active.
     */
    public function recentlyActive(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_activity_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the user is online (active in last 5 minutes).
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_activity_at' => fake()->dateTimeBetween('-5 minutes', 'now'),
        ]);
    }

    /**
     * Indicate that the user is offline (inactive for more than 15 minutes).
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_activity_at' => fake()->dateTimeBetween('-30 days', '-15 minutes'),
        ]);
    }
}
