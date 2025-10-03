<?php

use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\Promotion;
use App\Models\Menu\Section;
use App\Models\Menu\SectionOption;
use App\Services\PriceCalculatorService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new PriceCalculatorService;
    $this->category = Category::factory()->create();
    $this->product = Product::factory()->create([
        'category_id' => $this->category->id,
        'base_price' => 50,
        'delivery_price' => 55,
        'interior_base_price' => 45,
        'interior_delivery_price' => 50,
        'is_daily_special' => false,
    ]);
});

test('calcula precio base sin opciones', function () {
    $result = $this->service->calculatePrice($this->product, 'base_price', 1);

    expect($result)->toHaveKey('unit_price', 50.0)
        ->and($result)->toHaveKey('subtotal', 50.0)
        ->and($result)->toHaveKey('discount', 0.0)
        ->and($result)->toHaveKey('is_daily_special', false);
});

test('calcula precio con cantidad múltiple', function () {
    $result = $this->service->calculatePrice($this->product, 'base_price', 3);

    expect($result)->toHaveKey('unit_price', 50.0)
        ->and($result)->toHaveKey('quantity', 3)
        ->and($result)->toHaveKey('subtotal', 150.0);
});

test('calcula precio con modificadores de opciones', function () {
    $section = Section::factory()->create();
    $option1 = SectionOption::factory()->create([
        'section_id' => $section->id,
        'base_price_modifier' => 25,
        'delivery_price_modifier' => 30,
        'interior_base_price_modifier' => 20,
        'interior_delivery_price_modifier' => 25,
    ]);

    $result = $this->service->calculatePrice(
        $this->product,
        'base_price',
        1,
        [$option1->id]
    );

    expect($result)->toHaveKey('unit_price', 75.0)
        ->and($result)->toHaveKey('options_modifier', 25.0);
});

test('detecta sub del día correctamente', function () {
    // Crear producto Sub del Día para hoy
    $today = Carbon::now()->dayOfWeek;
    $this->product->update([
        'is_daily_special' => true,
        'daily_special_days' => [$today],
        'daily_special_base_price' => 35,
        'daily_special_delivery_price' => 40,
    ]);

    $result = $this->service->calculatePrice($this->product, 'base_price', 1);

    expect($result)->toHaveKey('is_daily_special', true)
        ->and($result)->toHaveKey('unit_price', 35.0);
});

test('no aplica promociones a sub del día', function () {
    $today = Carbon::now()->dayOfWeek;
    $this->product->update([
        'is_daily_special' => true,
        'daily_special_days' => [$today],
        'daily_special_base_price' => 35,
    ]);

    // Crear promoción de descuento
    $promotion = Promotion::factory()->create([
        'type' => 'percentage_discount',
        'discount_value' => 20,
        'applies_to' => 'product',
        'is_active' => true,
        'is_permanent' => true,
    ]);
    $promotion->items()->create(['product_id' => $this->product->id]);

    $result = $this->service->calculatePrice($this->product, 'base_price', 1);

    expect($result)->toHaveKey('is_daily_special', true)
        ->and($result)->toHaveKey('discount', 0.0)
        ->and($result)->toHaveKey('promotion', null);
});

test('aplica descuento porcentual', function () {
    $promotion = Promotion::factory()->create([
        'type' => 'percentage_discount',
        'discount_value' => 20,
        'applies_to' => 'product',
        'is_active' => true,
        'is_permanent' => true,
    ]);
    $promotion->items()->create(['product_id' => $this->product->id]);

    $result = $this->service->calculatePrice($this->product, 'base_price', 1);

    expect($result)->toHaveKey('original_subtotal', 50.0)
        ->and($result)->toHaveKey('subtotal', 40.0)
        ->and($result)->toHaveKey('discount', 10.0)
        ->and($result['promotion'])->toHaveKey('type', 'percentage_discount');
});

test('aplica 2x1 al carrito correctamente', function () {
    $promotion = Promotion::factory()->create([
        'type' => 'two_for_one',
        'applies_to' => 'category',
        'is_active' => true,
        'is_permanent' => true,
    ]);
    $promotion->items()->create(['category_id' => $this->category->id]);

    $product2 = Product::factory()->create([
        'category_id' => $this->category->id,
        'base_price' => 70,
    ]);

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
        ->and($result[0]['subtotal'])->toBe(0);
});

test('respeta restricciones de día de la semana', function () {
    $tomorrow = Carbon::now()->addDay()->dayOfWeek;

    $promotion = Promotion::factory()->create([
        'type' => 'percentage_discount',
        'discount_value' => 15,
        'applies_to' => 'product',
        'is_active' => true,
        'is_permanent' => true,
        'active_days' => [$tomorrow], // Solo mañana
    ]);
    $promotion->items()->create(['product_id' => $this->product->id]);

    $result = $this->service->calculatePrice($this->product, 'base_price', 1, [], Carbon::now());

    // No debe aplicar descuento hoy
    expect($result)->toHaveKey('discount', 0.0)
        ->and($result)->toHaveKey('promotion', null);
});

test('respeta restricciones de horario', function () {
    $promotion = Promotion::factory()->create([
        'type' => 'percentage_discount',
        'discount_value' => 15,
        'applies_to' => 'product',
        'is_active' => true,
        'is_permanent' => true,
        'has_time_restriction' => true,
        'time_from' => '18:00:00',
        'time_until' => '22:00:00',
    ]);
    $promotion->items()->create(['product_id' => $this->product->id]);

    $morningTime = Carbon::now()->setTime(10, 0, 0);
    $result = $this->service->calculatePrice($this->product, 'base_price', 1, [], $morningTime);

    // No debe aplicar descuento en la mañana
    expect($result)->toHaveKey('discount', 0.0);

    $eveningTime = Carbon::now()->setTime(19, 0, 0);
    $result = $this->service->calculatePrice($this->product, 'base_price', 1, [], $eveningTime);

    // Debe aplicar descuento en la tarde
    expect($result)->toHaveKey('discount', 7.5);
});

test('usa tipo de precio correcto para cada modalidad', function () {
    $results = [
        'base_price' => $this->service->calculatePrice($this->product, 'base_price', 1),
        'delivery_price' => $this->service->calculatePrice($this->product, 'delivery_price', 1),
        'interior_base_price' => $this->service->calculatePrice($this->product, 'interior_base_price', 1),
        'interior_delivery_price' => $this->service->calculatePrice($this->product, 'interior_delivery_price', 1),
    ];

    expect($results['base_price']['unit_price'])->toBe(50.0)
        ->and($results['delivery_price']['unit_price'])->toBe(55.0)
        ->and($results['interior_base_price']['unit_price'])->toBe(45.0)
        ->and($results['interior_delivery_price']['unit_price'])->toBe(50.0);
});
