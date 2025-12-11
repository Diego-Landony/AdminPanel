<?php

namespace Database\Factories\Menu;

use App\Models\Menu\ComboItem;
use App\Models\Menu\ComboItemOption;
use App\Models\Menu\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu\ComboItemOption>
 */
class ComboItemOptionFactory extends Factory
{
    protected $model = ComboItemOption::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'combo_item_id' => ComboItem::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
