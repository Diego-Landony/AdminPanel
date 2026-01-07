<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserActivity>
 */
class UserActivityFactory extends Factory
{
    protected $model = UserActivity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'activity_type' => fake()->randomElement(['login', 'logout', 'action', 'api_call', 'settings_change']),
            'description' => fake()->sentence(),
            'user_agent' => fake()->userAgent(),
            'url' => fake()->url(),
            'method' => fake()->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'metadata' => null,
        ];
    }

    /**
     * State for login activity.
     */
    public function login(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'login',
            'description' => 'Usuario inició sesión',
        ]);
    }

    /**
     * State for logout activity.
     */
    public function logout(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'logout',
            'description' => 'Usuario cerró sesión',
        ]);
    }

    /**
     * State for page view activity.
     */
    public function pageView(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'page_view',
            'description' => 'Usuario visitó una página',
        ]);
    }

    /**
     * State for heartbeat activity.
     */
    public function heartbeat(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_type' => 'heartbeat',
            'description' => 'Heartbeat',
        ]);
    }
}
