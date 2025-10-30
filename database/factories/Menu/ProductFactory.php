<?php

namespace Database\Factories\Menu;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(fake()->numberBetween(2, 3), true),
            'description' => fake()->optional()->paragraph(),
            'image' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
            'has_variants' => false,
            'category_id' => \App\Models\Menu\Category::factory(),
        ];
    }

    /**
     * Estado para productos con variantes
     */
    public function withVariants(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_variants' => true,
        ]);
    }

    /**
     * Estado para productos sin variantes
     */
    public function withoutVariants(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_variants' => false,
        ]);
    }
}
