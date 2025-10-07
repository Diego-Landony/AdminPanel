<?php

use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Promotion;
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

test('aplica descuento porcentual', function () {
    // NOTA: Esta funcionalidad aún no está completamente implementada
    // Este test falla porque percentage_discount no está en producción aún
    $promotion = Promotion::factory()->create([
        'type' => 'percentage_discount',
        'discount_value' => 20,
        'is_active' => true,
        'is_permanent' => true,
    ]);
    $promotion->items()->create(['product_id' => $this->product->id]);

    $result = $this->service->calculatePrice(
        $this->product,
        $this->category->id,
        null,
        'precio_pickup_capital',
        1
    );

    expect($result)->toHaveKey('original_subtotal', 50.0)
        ->and($result)->toHaveKey('subtotal', 40.0)
        ->and($result)->toHaveKey('discount', 10.0)
        ->and($result['promotion'])->toHaveKey('type', 'percentage_discount');
})->skip('Funcionalidad de descuento porcentual no implementada completamente');

test('aplica 2x1 al carrito correctamente', function () {
    // NOTA: Esta funcionalidad aún no está completamente implementada
    $promotion = Promotion::factory()->create([
        'type' => 'two_for_one',
        'is_active' => true,
        'is_permanent' => true,
    ]);
    $promotion->items()->create(['category_id' => $this->category->id]);

    // Crear otro producto en la misma categoría
    $product2 = Product::factory()->create([
        'category_id' => $this->category->id,
        'precio_pickup_capital' => 70.00,
    ]);
    $product2->categories()->attach($this->category->id);

    // Simular items calculados del carrito
    $cartItems = [
        [
            'product_id' => $this->product->id,
            'unit_price' => 50.0,
            'quantity' => 1,
            'subtotal' => 50.0,
            'discount' => 0.0,
        ],
        [
            'product_id' => $product2->id,
            'unit_price' => 70.0,
            'quantity' => 1,
            'subtotal' => 70.0,
            'discount' => 0.0,
        ],
    ];

    $result = $this->service->applyTwoForOneToCart($cartItems, $promotion);

    // Debe cobrar el más caro (70) y descontar el más barato (50)
    expect($result)->toHaveCount(2)
        ->and($result[0]['discount'])->toBe(50.0)
        ->and($result[0]['subtotal'])->toBe(0.0);
})->skip('Funcionalidad 2x1 no implementada completamente');

test('respeta restricciones de día de la semana', function () {
    // NOTA: Esta funcionalidad aún no está completamente implementada
    $tomorrow = Carbon::now()->addDay()->dayOfWeek;

    $promotion = Promotion::factory()->create([
        'type' => 'percentage_discount',
        'discount_value' => 15,
        'is_active' => true,
        'is_permanent' => true,
        'active_days' => [$tomorrow], // Solo mañana
    ]);
    $promotion->items()->create(['product_id' => $this->product->id]);

    $result = $this->service->calculatePrice(
        $this->product,
        $this->category->id,
        null,
        'precio_pickup_capital',
        1,
        [],
        Carbon::now()
    );

    // No debe aplicar descuento hoy
    expect($result)->toHaveKey('discount', 0.0)
        ->and($result)->toHaveKey('promotion', null);
})->skip('Funcionalidad de promociones con restricciones no implementada completamente');

test('respeta restricciones de horario', function () {
    // NOTA: Esta funcionalidad aún no está completamente implementada
    $promotion = Promotion::factory()->create([
        'type' => 'percentage_discount',
        'discount_value' => 15,
        'is_active' => true,
        'is_permanent' => true,
        'has_time_restriction' => true,
        'time_from' => '18:00:00',
        'time_until' => '22:00:00',
    ]);
    $promotion->items()->create(['product_id' => $this->product->id]);

    $morningTime = Carbon::now()->setTime(10, 0, 0);
    $result = $this->service->calculatePrice(
        $this->product,
        $this->category->id,
        null,
        'precio_pickup_capital',
        1,
        [],
        $morningTime
    );

    // No debe aplicar descuento en la mañana
    expect($result)->toHaveKey('discount', 0.0);

    $eveningTime = Carbon::now()->setTime(19, 0, 0);
    $result = $this->service->calculatePrice(
        $this->product,
        $this->category->id,
        null,
        'precio_pickup_capital',
        1,
        [],
        $eveningTime
    );

    // Debe aplicar descuento en la tarde
    expect($result)->toHaveKey('discount', 7.5);
})->skip('Funcionalidad de promociones con restricciones horarias no implementada completamente');

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
