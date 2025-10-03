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
            'is_customizable' => fake()->boolean(60), // 60% probabilidad de ser personalizable
            'is_active' => true,
        ];
    }

    /**
     * Estado para productos personalizables (con secciones)
     */
    public function customizable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_customizable' => true,
        ]);
    }

    /**
     * Estado para productos NO personalizables (venta directa)
     */
    public function simple(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_customizable' => false,
        ]);
    }
}
