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
        return [
            'customer_id' => Customer::factory(),
            'fcm_token' => fake()->unique()->sha256(),
            'device_type' => fake()->randomElement(['ios', 'android', 'web']),
            'device_name' => fake()->optional()->randomElement([
                'iPhone de Juan',
                'iPad Pro',
                'Samsung Galaxy',
                'Xiaomi Mi 11',
                'Google Pixel',
                'Navegador Chrome',
                'Navegador Safari',
            ]),
            'device_model' => fake()->optional()->randomElement([
                'iPhone 14 Pro',
                'iPhone 13',
                'iPad Pro 12.9',
                'Samsung Galaxy S23',
                'Xiaomi Mi 11',
                'Google Pixel 7',
                'Chrome on Windows',
                'Safari on macOS',
            ]),
            'last_used_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the device is for iOS.
     */
    public function ios(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'ios',
            'device_name' => fake()->randomElement(['iPhone de Juan', 'iPad Pro', 'iPhone de María']),
            'device_model' => fake()->randomElement(['iPhone 14 Pro', 'iPhone 13', 'iPad Pro 12.9', 'iPhone 15']),
        ]);
    }

    /**
     * Indicate that the device is for Android.
     */
    public function android(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'android',
            'device_name' => fake()->randomElement(['Samsung Galaxy', 'Xiaomi Mi 11', 'Google Pixel']),
            'device_model' => fake()->randomElement(['Samsung Galaxy S23', 'Xiaomi Mi 11', 'Google Pixel 7', 'OnePlus 11']),
        ]);
    }

    /**
     * Indicate that the device is web.
     */
    public function web(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => 'web',
            'device_name' => fake()->randomElement(['Navegador Chrome', 'Navegador Safari', 'Navegador Firefox']),
            'device_model' => fake()->randomElement(['Chrome on Windows', 'Safari on macOS', 'Firefox on Linux']),
        ]);
    }

    /**
     * Indicate that the device is active (used recently).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the device is inactive (not used recently).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => fake()->optional(0.7)->dateTimeBetween('-90 days', '-31 days'),
        ]);
    }
}
