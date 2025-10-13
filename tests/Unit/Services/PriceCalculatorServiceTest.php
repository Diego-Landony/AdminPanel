<?php

use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Section;
use App\Models\Menu\SectionOption;
use App\Services\PriceCalculatorService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new PriceCalculatorService;

    // Crear categoría sin variantes
    $this->category = Category::factory()->create(['uses_variants' => false]);

    // Crear producto con precios y asociarlo a la categoría
    $this->product = Product::factory()->create([
        'category_id' => $this->category->id,
        'precio_pickup_capital' => 50.00,
        'precio_domicilio_capital' => 55.00,
        'precio_pickup_interior' => 45.00,
        'precio_domicilio_interior' => 50.00,
    ]);
    $this->product->categories()->attach($this->category->id);

    // Crear categoría con variantes
    $this->categoryWithVariants = Category::factory()->create(['uses_variants' => true]);
    $this->productWithVariants = Product::factory()->create([
        'category_id' => $this->categoryWithVariants->id,
    ]);
    $this->productWithVariants->categories()->attach($this->categoryWithVariants->id);

    // Crear variante
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->productWithVariants->id,
        'precio_pickup_capital' => 50.00,
        'precio_domicilio_capital' => 55.00,
        'precio_pickup_interior' => 45.00,
        'precio_domicilio_interior' => 50.00,
        'is_daily_special' => false,
    ]);
});

test('calcula precio base sin opciones', function () {
    $result = $this->service->calculatePrice(
        $this->product,
        $this->category->id,
        null,
        'precio_pickup_capital',
        1
    );

    expect($result)->toHaveKey('unit_price', 50.0)
        ->and($result)->toHaveKey('subtotal', 50.0)
        ->and($result)->toHaveKey('discount', 0.0)
        ->and($result)->toHaveKey('is_daily_special', false);
});

test('calcula precio con cantidad múltiple', function () {
    $result = $this->service->calculatePrice(
        $this->product,
        $this->category->id,
        null,
        'precio_pickup_capital',
        3
    );

    expect($result)->toHaveKey('unit_price', 50.0)
        ->and($result)->toHaveKey('quantity', 3)
        ->and($result)->toHaveKey('subtotal', 150.0);
});

test('calcula precio con modificadores de opciones', function () {
    $section = Section::factory()->create();
    $option1 = SectionOption::factory()->create([
        'section_id' => $section->id,
        'price_modifier' => 25.00,
        'is_extra' => true,
    ]);

    $result = $this->service->calculatePrice(
        $this->product,
        $this->category->id,
        null,
        'precio_pickup_capital',
        1,
        [$option1->id]
    );

    expect($result)->toHaveKey('unit_price', 75.0)
        ->and($result)->toHaveKey('options_modifier', 25.0);
});

test('detecta sub del día correctamente', function () {
    // Crear variante Sub del Día para hoy
    $today = Carbon::now()->dayOfWeek;
    $this->variant->update([
        'is_daily_special' => true,
        'daily_special_days' => [$today],
        'daily_special_precio_pickup_capital' => 35.00,
        'daily_special_precio_domicilio_capital' => 40.00,
    ]);

    $result = $this->service->calculatePrice(
        $this->productWithVariants,
        $this->categoryWithVariants->id,
        $this->variant->id,
        'precio_pickup_capital',
        1
    );

    expect($result)->toHaveKey('is_daily_special', true)
        ->and($result)->toHaveKey('unit_price', 35.0);
});

test('no aplica promociones a sub del día', function () {
    $today = Carbon::now()->dayOfWeek;
    $this->variant->update([
        'is_daily_special' => true,
        'daily_special_days' => [$today],
        'daily_special_precio_pickup_capital' => 35.00,
    ]);

    $result = $this->service->calculatePrice(
        $this->productWithVariants,
        $this->categoryWithVariants->id,
        $this->variant->id,
        'precio_pickup_capital',
        1
    );

    // Sub del día no debe tener promociones adicionales
    expect($result)->toHaveKey('is_daily_special', true)
        ->and($result)->toHaveKey('discount', 0.0)
        ->and($result)->toHaveKey('promotion', null);
});

test('usa tipo de precio correcto para cada modalidad', function () {
    $results = [
        'precio_pickup_capital' => $this->service->calculatePrice($this->product, $this->category->id, null, 'precio_pickup_capital', 1),
        'precio_domicilio_capital' => $this->service->calculatePrice($this->product, $this->category->id, null, 'precio_domicilio_capital', 1),
        'precio_pickup_interior' => $this->service->calculatePrice($this->product, $this->category->id, null, 'precio_pickup_interior', 1),
        'precio_domicilio_interior' => $this->service->calculatePrice($this->product, $this->category->id, null, 'precio_domicilio_interior', 1),
    ];

    expect($results['precio_pickup_capital']['unit_price'])->toBe(50.0)
        ->and($results['precio_domicilio_capital']['unit_price'])->toBe(55.0)
        ->and($results['precio_pickup_interior']['unit_price'])->toBe(45.0)
        ->and($results['precio_domicilio_interior']['unit_price'])->toBe(50.0);
});
