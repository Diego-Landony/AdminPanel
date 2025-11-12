<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerDevice>
 */
class CustomerDeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isRecent = fake()->boolean(70);

        return [
            'customer_id' => Customer::factory(),
            'fcm_token' => fake()->randomFloat() < 0.8 ? fake()->unique()->sha256() : null,
            'device_identifier' => fake()->unique()->uuid(),
            'device_name' => fake()->optional()->randomElement([
                'iPhone de Juan',
                'iPad Pro',
                'Samsung Galaxy',
                'Xiaomi Mi 11',
                'Google Pixel',
                'Navegador Chrome',
                'Navegador Safari',
            ]),
            'last_used_at' => $isRecent
                ? fake()->dateTimeBetween('-30 days', 'now')
                : fake()->optional(0.7)->dateTimeBetween('-90 days', '-31 days'),
            'is_active' => true,
            'login_count' => fake()->numberBetween(1, 50),
        ];
    }

    /**
     * Indicate that the device is active (used recently).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'fcm_token' => $attributes['fcm_token'] ?? (fake()->randomFloat() < 0.9 ? fake()->unique()->sha256() : null),
            'last_used_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'is_active' => true,
            'login_count' => fake()->numberBetween(5, 50),
        ]);
    }

    /**
     * Indicate that the device is inactive (not used recently).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => fake()->optional(0.7)->dateTimeBetween('-90 days', '-31 days'),
            'is_active' => false,
            'login_count' => fake()->numberBetween(1, 10),
        ]);
    }

    /**
     * Indicate that this is a new device (first login).
     */
    public function newDevice(): static
    {
        return $this->state(fn (array $attributes) => [
            'login_count' => 1,
            'last_used_at' => now(),
            'is_active' => true,
        ]);
    }
}
