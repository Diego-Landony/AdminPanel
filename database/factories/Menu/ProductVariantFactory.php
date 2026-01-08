<?php

namespace Database\Factories\Menu;

use App\Models\Menu\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $basePrice = $this->faker->randomFloat(2, 30, 120);

        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper($this->faker->unique()->bothify('PROD-???-####')),
            'name' => $this->faker->words(3, true),
            'size' => null, // Se define dinámicamente según la categoría

            // 4 tipos de precio
            'precio_pickup_capital' => $basePrice,
            'precio_domicilio_capital' => $basePrice + $this->faker->randomFloat(2, 5, 15),
            'precio_pickup_interior' => $basePrice - $this->faker->randomFloat(2, 3, 10),
            'precio_domicilio_interior' => $basePrice + $this->faker->randomFloat(2, 2, 8),

            'is_daily_special' => false,
            'daily_special_days' => null,
            'daily_special_precio_pickup_capital' => null,
            'daily_special_precio_domicilio_capital' => null,
            'daily_special_precio_pickup_interior' => null,
            'daily_special_precio_domicilio_interior' => null,

            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    /**
     * Indica que esta variante es Sub del Día
     */
    public function dailySpecial(array $days = [0, 1, 2, 3, 4, 5, 6]): static
    {
        $basePrice = $this->faker->randomFloat(2, 25, 80);

        return $this->state(fn (array $attributes) => [
            'is_daily_special' => true,
            'daily_special_days' => $days,
            'daily_special_precio_pickup_capital' => $basePrice,
            'daily_special_precio_domicilio_capital' => $basePrice + 10,
            'daily_special_precio_pickup_interior' => $basePrice - 5,
            'daily_special_precio_domicilio_interior' => $basePrice + 5,
        ]);
    }

    /**
     * Estado para definir variante con un tamaño/tipo específico
     *
     * @param  string  $variantName  Nombre de la variante según definición de categoría
     * @param  float  $priceMultiplier  Multiplicador de precio (default: 1.0)
     */
    public function withVariant(string $variantName, float $priceMultiplier = 1.0): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => $variantName,
            'name' => $attributes['name'].' - '.$variantName,
            'precio_pickup_capital' => $attributes['precio_pickup_capital'] * $priceMultiplier,
            'precio_domicilio_capital' => $attributes['precio_domicilio_capital'] * $priceMultiplier,
            'precio_pickup_interior' => $attributes['precio_pickup_interior'] * $priceMultiplier,
            'precio_domicilio_interior' => $attributes['precio_domicilio_interior'] * $priceMultiplier,
        ]);
    }
}
