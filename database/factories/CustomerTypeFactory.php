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
            'name' => fake()->unique()->randomElement(['regular', 'bronze', 'silver', 'gold', 'platinum']),
            'display_name' => fake()->words(2, true),
            'points_required' => fake()->numberBetween(0, 1000),
            'multiplier' => fake()->randomFloat(2, 1.0, 3.0),
            'color' => fake()->randomElement(['green', 'orange', 'gray', 'yellow', 'purple']),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 10),
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
            'display_name' => 'Regular',
            'points_required' => 0,
            'multiplier' => 1.00,
            'color' => 'green',
            'sort_order' => 1,
        ]);
    }

    /**
     * Create a bronze customer type.
     */
    public function bronze(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'bronze',
            'display_name' => 'Bronce',
            'points_required' => 50,
            'multiplier' => 1.25,
            'color' => 'orange',
            'sort_order' => 2,
        ]);
    }

    /**
     * Create a silver customer type.
     */
    public function silver(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'silver',
            'display_name' => 'Plata',
            'points_required' => 125,
            'multiplier' => 1.50,
            'color' => 'gray',
            'sort_order' => 3,
        ]);
    }

    /**
     * Create a gold customer type.
     */
    public function gold(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'gold',
            'display_name' => 'Oro',
            'points_required' => 325,
            'multiplier' => 1.75,
            'color' => 'yellow',
            'sort_order' => 4,
        ]);
    }

    /**
     * Create a platinum customer type.
     */
    public function platinum(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'platinum',
            'display_name' => 'Platino',
            'points_required' => 1000,
            'multiplier' => 2.00,
            'color' => 'purple',
            'sort_order' => 5,
        ]);
    }
}