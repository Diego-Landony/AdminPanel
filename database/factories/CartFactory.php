<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => \App\Models\Customer::factory(),
            'restaurant_id' => \App\Models\Restaurant::factory(),
            'service_type' => fake()->randomElement(['pickup', 'delivery']),
            'zone' => fake()->randomElement(['capital', 'interior']),
            'status' => 'active',
            'expires_at' => now()->addHours(24),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'abandoned',
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'converted',
        ]);
    }

    public function pickup(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'pickup',
        ]);
    }

    public function delivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'delivery',
        ]);
    }

    public function capital(): static
    {
        return $this->state(fn (array $attributes) => [
            'zone' => 'capital',
        ]);
    }

    public function interior(): static
    {
        return $this->state(fn (array $attributes) => [
            'zone' => 'interior',
        ]);
    }
}
