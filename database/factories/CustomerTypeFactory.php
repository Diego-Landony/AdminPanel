<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerType>
 */
class CustomerTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'points_required' => fake()->numberBetween(0, 1000),
            'multiplier' => fake()->randomFloat(2, 1.0, 3.0),
            'color' => fake()->randomElement(['green', 'orange', 'gray', 'yellow', 'purple']),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the customer type is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a regular customer type.
     */
    public function regular(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'regular',
            'points_required' => 0,
            'multiplier' => 1.00,
            'color' => 'green',
        ]);
    }

    /**
     * Create a bronze customer type.
     */
    public function bronze(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'bronze',
            'points_required' => 50,
            'multiplier' => 1.25,
            'color' => 'orange',
        ]);
    }

    /**
     * Create a silver customer type.
     */
    public function silver(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'silver',
            'points_required' => 125,
            'multiplier' => 1.50,
            'color' => 'gray',
        ]);
    }

    /**
     * Create a gold customer type.
     */
    public function gold(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'gold',
            'points_required' => 325,
            'multiplier' => 1.75,
            'color' => 'yellow',
        ]);
    }

    /**
     * Create a platinum customer type.
     */
    public function platinum(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'platinum',
            'points_required' => 1000,
            'multiplier' => 2.00,
            'color' => 'purple',
        ]);
    }
}
