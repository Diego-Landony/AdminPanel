<?php

namespace Database\Factories;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Driver>
 */
class DriverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_available' => true,
            'current_latitude' => null,
            'current_longitude' => null,
            'last_location_update' => null,
            'last_login_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'last_activity_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Create an available driver (online and ready to receive orders).
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'is_available' => true,
            'current_latitude' => fake()->randomFloat(8, 14.5, 15.0),
            'current_longitude' => fake()->randomFloat(8, -91.8, -90.2),
            'last_location_update' => now(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Create an unavailable driver (offline).
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'is_available' => false,
        ]);
    }

    /**
     * Create an inactive driver.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'is_available' => false,
        ]);
    }

    /**
     * Create a driver with GPS location coordinates (Guatemala area).
     */
    public function withLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_latitude' => fake()->randomFloat(8, 14.5, 15.0),
            'current_longitude' => fake()->randomFloat(8, -91.8, -90.2),
            'last_location_update' => now(),
        ]);
    }
}
