<?php

namespace Database\Factories\Menu;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu\Combo>
 */
class ComboFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);
        $precioBase = fake()->randomFloat(2, 50, 300);

        return [
            'category_id' => \App\Models\Menu\Category::factory()->state(['is_combo_category' => true]),
            'name' => 'Combo '.ucfirst($name),
            'slug' => \Illuminate\Support\Str::slug('Combo '.$name),
            'description' => fake()->sentence(),
            'image' => null,

            // Precios con lógica: delivery >= pickup
            'precio_pickup_capital' => $precioBase,
            'precio_domicilio_capital' => $precioBase + 10,
            'precio_pickup_interior' => $precioBase - 10,
            'precio_domicilio_interior' => $precioBase,

            'is_active' => fake()->boolean(80), // 80% activos
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Combo inactivo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Combo con nombre específico
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
        ]);
    }
}
