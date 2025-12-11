<?php

namespace Database\Factories\Menu;

use App\Models\Menu\BadgeType;
use App\Models\Menu\Product;
use App\Models\Menu\ProductBadge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu\ProductBadge>
 */
class ProductBadgeFactory extends Factory
{
    protected $model = ProductBadge::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'badge_type_id' => BadgeType::factory(),
            'badgeable_type' => Product::class,
            'badgeable_id' => Product::factory(),
            'validity_type' => 'permanent',
            'valid_from' => null,
            'valid_until' => null,
            'weekdays' => null,
            'is_active' => true,
        ];
    }
}
