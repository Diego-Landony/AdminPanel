<?php

namespace Database\Factories\Menu;

use App\Models\Menu\Combo;
use App\Models\Menu\ComboItem;
use App\Models\Menu\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu\ComboItem>
 */
class ComboItemFactory extends Factory
{
    protected $model = ComboItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'combo_id' => Combo::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'is_choice_group' => false,
            'choice_label' => null,
            'quantity' => 1,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function choiceGroup(?string $label = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_choice_group' => true,
            'choice_label' => $label ?? fake()->word(),
        ]);
    }
}
