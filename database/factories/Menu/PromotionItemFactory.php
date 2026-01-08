<?php

namespace Database\Factories\Menu;

use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\Promotion;
use App\Models\Menu\PromotionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu\PromotionItem>
 */
class PromotionItemFactory extends Factory
{
    protected $model = PromotionItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'promotion_id' => Promotion::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'category_id' => Category::factory(),
            'special_price_pickup_capital' => null,
            'special_price_delivery_capital' => null,
            'special_price_pickup_interior' => null,
            'special_price_delivery_interior' => null,
            'discount_percentage' => null,
            'service_type' => null,
            'validity_type' => 'permanent',
            'valid_from' => null,
            'valid_until' => null,
            'time_from' => null,
            'time_until' => null,
            'weekdays' => null,
        ];
    }
}
