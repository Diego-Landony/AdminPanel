<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Favorite>
 */
class FavoriteFactory extends Factory
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
            'favorable_type' => \App\Models\Menu\Product::class,
            'favorable_id' => \App\Models\Menu\Product::factory(),
        ];
    }

    /**
     * Set the favorable to a Product.
     */
    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'favorable_type' => \App\Models\Menu\Product::class,
            'favorable_id' => \App\Models\Menu\Product::factory(),
        ]);
    }

    /**
     * Set the favorable to a Combo.
     */
    public function combo(): static
    {
        return $this->state(fn (array $attributes) => [
            'favorable_type' => \App\Models\Menu\Combo::class,
            'favorable_id' => \App\Models\Menu\Combo::factory(),
        ]);
    }
}
