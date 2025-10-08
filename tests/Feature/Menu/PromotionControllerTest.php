<?php

use App\Models\Menu\Product;
use App\Models\Menu\Promotion;
use App\Models\User;

beforeEach(function () {
    // Usar el helper createTestUser que crea un usuario con todos los permisos
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

test('puede listar promociones daily_special', function () {
    Promotion::factory()->count(2)->create(['type' => 'daily_special']);
    Promotion::factory()->create(['type' => 'two_for_one']);

    $response = $this->get(route('menu.promotions.daily-special.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('menu/promotions/daily-special/index')
        ->has('promotions.data', 2)
    );
});

test('puede crear promoción daily_special con weekdays', function () {
    $product = Product::factory()->create();

    $promotionData = [
        'name' => 'Sub del Día Lunes',
        'description' => 'Promoción especial de lunes',
        'type' => 'daily_special',
        'is_active' => true,
        'items' => [
            [
                'product_id' => $product->id,
                'special_price_capital' => 35.00,
                'special_price_interior' => 30.00,
                'service_type' => 'both',
                'validity_type' => 'weekdays',
                'weekdays' => [1, 2], // Lunes y Martes
            ],
        ],
    ];

    $response = $this->post(route('menu.promotions.store'), $promotionData);

    $response->assertRedirect();

    $this->assertDatabaseHas('promotions', [
        'name' => 'Sub del Día Lunes',
        'type' => 'daily_special',
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('promotion_items', [
        'product_id' => $product->id,
        'special_price_capital' => 35.00,
        'validity_type' => 'weekdays',
    ]);
});

test('puede crear promoción con validity_type date_range', function () {
    $product = Product::factory()->create();

    $promotionData = [
        'name' => 'Promoción Navideña',
        'type' => 'daily_special',
        'is_active' => true,
        'items' => [
            [
                'product_id' => $product->id,
                'special_price_capital' => 40.00,
                'special_price_interior' => 35.00,
                'service_type' => 'both',
                'validity_type' => 'date_range',
                'valid_from' => '2025-12-20',
                'valid_until' => '2025-12-31',
                'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            ],
        ],
    ];

    $response = $this->post(route('menu.promotions.store'), $promotionData);

    $response->assertRedirect();

    $this->assertDatabaseHas('promotion_items', [
        'product_id' => $product->id,
        'validity_type' => 'date_range',
        'valid_from' => '2025-12-20',
        'valid_until' => '2025-12-31',
    ]);
});

test('puede editar una promoción', function () {
    $promotion = Promotion::factory()->create([
        'name' => 'Nombre Original',
        'type' => 'daily_special',
    ]);

    $product = Product::factory()->create();
    $promotion->items()->create([
        'product_id' => $product->id,
        'special_price_capital' => 30.00,
        'special_price_interior' => 25.00,
        'service_type' => 'both',
        'validity_type' => 'weekdays',
        'weekdays' => [1],
    ]);

    $response = $this->put(route('menu.promotions.update', $promotion), [
        'name' => 'Nombre Actualizado',
        'description' => 'Nueva descripción',
        'type' => 'daily_special',
        'is_active' => false,
        'items' => [
            [
                'product_id' => $product->id,
                'special_price_capital' => 35.00,
                'special_price_interior' => 30.00,
                'service_type' => 'delivery_only',
                'validity_type' => 'weekdays',
                'weekdays' => [1, 2],
            ],
        ],
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('promotions', [
        'id' => $promotion->id,
        'name' => 'Nombre Actualizado',
        'is_active' => false,
    ]);
});

test('puede eliminar una promoción', function () {
    $promotion = Promotion::factory()->create();

    $response = $this->delete(route('menu.promotions.destroy', $promotion));

    $response->assertRedirect();

    $this->assertSoftDeleted('promotions', [
        'id' => $promotion->id,
    ]);
});

test('puede activar o desactivar una promoción', function () {
    $promotion = Promotion::factory()->create(['is_active' => true]);

    $response = $this->post(route('menu.promotions.toggle', $promotion));

    $response->assertSuccessful();

    $this->assertDatabaseHas('promotions', [
        'id' => $promotion->id,
        'is_active' => false,
    ]);
});

test('valida que promotion_items tenga weekdays si validity_type es weekdays', function () {
    $product = Product::factory()->create();

    $promotionData = [
        'name' => 'Promoción Sin Días',
        'type' => 'daily_special',
        'is_active' => true,
        'items' => [
            [
                'product_id' => $product->id,
                'special_price_capital' => 35.00,
                'special_price_interior' => 30.00,
                'service_type' => 'both',
                'validity_type' => 'weekdays',
                'weekdays' => [], // Sin días seleccionados
            ],
        ],
    ];

    $response = $this->post(route('menu.promotions.store'), $promotionData);

    $response->assertSessionHasErrors(['items.0.weekdays']);
});

test('puede listar promociones con filtro por estado', function () {
    Promotion::factory()->create(['is_active' => true, 'type' => 'daily_special']);
    Promotion::factory()->create(['is_active' => false, 'type' => 'daily_special']);

    $response = $this->get(route('menu.promotions.daily-special.index', ['is_active' => 1]));

    $response->assertSuccessful();
});
