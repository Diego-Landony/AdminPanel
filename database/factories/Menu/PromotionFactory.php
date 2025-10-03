<?php

namespace Database\Factories\Menu;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu\Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(),
            'description' => fake()->optional()->paragraph(),
            'type' => 'percentage_discount',
            'discount_value' => fake()->numberBetween(10, 30),
            'applies_to' => 'product',
            'is_permanent' => true,
            'valid_from' => null,
            'valid_until' => null,
            'has_time_restriction' => false,
            'time_from' => null,
            'time_until' => null,
            'active_days' => null,
            'is_active' => true,
        ];
    }
}
