<?php

namespace Database\Factories;

use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = fake()->randomFloat(2, 5, 50);
        $optionsPrice = fake()->randomFloat(2, 0, 10);
        $subtotal = ($unitPrice + $optionsPrice) * $quantity;

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'combo_id' => null,
            'product_snapshot' => [
                'name' => fake()->words(3, true),
                'description' => fake()->sentence(),
                'price' => $unitPrice,
            ],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'options_price' => $optionsPrice,
            'subtotal' => $subtotal,
            'selected_options' => [
                [
                    'section_name' => 'Proteína',
                    'option_name' => fake()->randomElement(['Pollo', 'Carne', 'Atún']),
                    'price' => fake()->randomFloat(2, 0, 5),
                ],
            ],
            'combo_selections' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forProduct(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => Product::factory(),
            'variant_id' => null,
            'combo_id' => null,
            'combo_selections' => null,
        ]);
    }

    public function forVariant(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => Product::factory(),
            'variant_id' => ProductVariant::factory(),
            'combo_id' => null,
            'combo_selections' => null,
        ]);
    }

    public function forCombo(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => null,
            'variant_id' => null,
            'combo_id' => Combo::factory(),
            'selected_options' => null,
            'combo_selections' => [
                [
                    'item_name' => 'Bebida',
                    'product_name' => fake()->randomElement(['Coca Cola', 'Pepsi', 'Sprite']),
                    'variant_name' => fake()->randomElement(['Regular', 'Grande']),
                ],
            ],
        ]);
    }

    public function withOptions(): static
    {
        return $this->state(fn (array $attributes) => [
            'options_price' => fake()->randomFloat(2, 5, 15),
            'selected_options' => [
                [
                    'section_name' => 'Proteína',
                    'option_name' => fake()->randomElement(['Pollo', 'Carne', 'Atún']),
                    'price' => fake()->randomFloat(2, 0, 5),
                ],
                [
                    'section_name' => 'Queso',
                    'option_name' => fake()->randomElement(['Cheddar', 'Americano', 'Suizo']),
                    'price' => fake()->randomFloat(2, 0, 3),
                ],
                [
                    'section_name' => 'Vegetales',
                    'option_name' => fake()->randomElement(['Lechuga', 'Tomate', 'Cebolla']),
                    'price' => 0,
                ],
            ],
        ]);
    }

    public function withoutOptions(): static
    {
        return $this->state(fn (array $attributes) => [
            'options_price' => 0,
            'selected_options' => [],
        ]);
    }
}
