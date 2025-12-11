<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CartItem>
 */
class CartItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => \App\Models\Cart::factory(),
            'product_id' => \App\Models\Menu\Product::factory(),
            'variant_id' => null,
            'combo_id' => null,
            'quantity' => fake()->numberBetween(1, 5),
            'unit_price' => fake()->randomFloat(2, 20, 100),
            'subtotal' => function (array $attributes) {
                return $attributes['unit_price'] * $attributes['quantity'];
            },
            'selected_options' => null,
            'combo_selections' => null,
            'notes' => null,
        ];
    }

    public function forProduct(\App\Models\Menu\Product $product, ?int $variantId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'combo_id' => null,
        ]);
    }

    public function forCombo(\App\Models\Menu\Combo $combo): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => null,
            'variant_id' => null,
            'combo_id' => $combo->id,
        ]);
    }

    public function withOptions(array $options): static
    {
        return $this->state(fn (array $attributes) => [
            'selected_options' => $options,
        ]);
    }

    public function withComboSelections(array $selections): static
    {
        return $this->state(fn (array $attributes) => [
            'combo_selections' => $selections,
        ]);
    }

    public function withNotes(string $notes): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes,
        ]);
    }
}
