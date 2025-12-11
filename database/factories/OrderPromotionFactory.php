<?php

namespace Database\Factories;

use App\Models\Menu\Promotion;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderPromotion>
 */
class OrderPromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'promotion_id' => Promotion::factory(),
            'promotion_type' => fake()->randomElement(['percentage', 'fixed_amount', 'daily_special', 'bundle_special']),
            'promotion_name' => fake()->words(3, true),
            'discount_amount' => fake()->randomFloat(2, 5, 50),
            'description' => fake()->sentence(),
            'created_at' => now(),
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'promotion_type' => 'percentage',
            'promotion_name' => fake()->randomElement(['Descuento 10%', 'Descuento 20%', 'Descuento 30%']),
        ]);
    }

    public function fixedAmount(): static
    {
        return $this->state(fn (array $attributes) => [
            'promotion_type' => 'fixed_amount',
            'promotion_name' => fake()->randomElement(['Descuento Q10', 'Descuento Q20', 'Descuento Q30']),
        ]);
    }

    public function dailySpecial(): static
    {
        return $this->state(fn (array $attributes) => [
            'promotion_type' => 'daily_special',
            'promotion_name' => 'Sub del Día',
            'description' => 'Promoción del Sub del Día',
        ]);
    }

    public function bundleSpecial(): static
    {
        return $this->state(fn (array $attributes) => [
            'promotion_type' => 'bundle_special',
            'promotion_name' => fake()->randomElement(['Combo 2x1', 'Combo Especial', 'Combo Navideño']),
            'description' => 'Promoción de combinado especial',
        ]);
    }
}
