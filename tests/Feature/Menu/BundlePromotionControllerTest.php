<?php

use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Promotion;

beforeEach(function () {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

// ============================================================================
// Tests de Creación
// ============================================================================

test('puede crear combinado con items fijos', function () {
    $product1 = Product::factory()->create(['has_variants' => false, 'is_active' => true]);
    $product2 = Product::factory()->create(['has_variants' => false, 'is_active' => true]);

    $data = [
        'name' => 'Combinado Test '.now()->timestamp,
        'description' => 'Oferta especial de navidad',
        'is_active' => true,
        'type' => 'bundle_special', // Explícitamente incluir type
        'special_bundle_price_capital' => 45.00,
        'special_bundle_price_interior' => 48.00,
        'validity_type' => 'date_range',
        'valid_from' => now()->addDays(5)->format('Y-m-d'),
        'valid_until' => now()->addDays(30)->format('Y-m-d'),
        'items' => [
            [
                'is_choice_group' => false,
                'product_id' => $product1->id,
                'variant_id' => null,
                'quantity' => 1,
                'sort_order' => 1,
            ],
            [
                'is_choice_group' => false,
                'product_id' => $product2->id,
                'variant_id' => null,
                'quantity' => 2,
                'sort_order' => 2,
            ],
        ],
    ];

    $countBefore = Promotion::count();

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertRedirect(route('menu.promotions.bundle-specials.index'));

    expect(Promotion::count())->toBe($countBefore + 1);

    $promotion = Promotion::where('name', 'LIKE', 'Combinado Test%')->latest()->first();

    // Debug: Ver todas las promociones creadas
    $allPromotions = Promotion::where('name', 'LIKE', 'Combinado Test%')->get(['id', 'name', 'type']);
    dump('Total promociones "Combinado Test": ' . $allPromotions->count());
    dump($allPromotions->toArray());

    expect($promotion)->not->toBeNull();
    expect($promotion->type)->toBe('bundle_special');
    expect($promotion->bundleItems()->count())->toBe(2);
    expect((float) $promotion->special_bundle_price_capital)->toBe(45.00);
});

test('puede crear combinado con grupos de elección', function () {
    $product1 = Product::factory()->create(['has_variants' => false]);
    $product2 = Product::factory()->create(['has_variants' => false]);
    $product3 = Product::factory()->create(['has_variants' => false]);

    $data = [
        'name' => 'Combinado con Elección',
        'description' => 'Elige tu producto favorito',
        'is_active' => true,
        'special_bundle_price_capital' => 35.00,
        'special_bundle_price_interior' => 38.00,
        'validity_type' => 'permanent',
        'items' => [
            [
                'is_choice_group' => true,
                'choice_label' => 'Elige tu sub',
                'quantity' => 1,
                'sort_order' => 1,
                'options' => [
                    ['product_id' => $product1->id, 'variant_id' => null, 'sort_order' => 1],
                    ['product_id' => $product2->id, 'variant_id' => null, 'sort_order' => 2],
                ],
            ],
            [
                'is_choice_group' => false,
                'product_id' => $product3->id,
                'variant_id' => null,
                'quantity' => 1,
                'sort_order' => 2,
            ],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertRedirect();

    $promotion = Promotion::where('name', 'Combinado con Elección')->first();
    expect($promotion->bundleItems)->toHaveCount(2);
    expect($promotion->bundleItems->first()->options)->toHaveCount(2);
});

test('puede crear combinado con variantes', function () {
    $product = Product::factory()->create(['has_variants' => true]);
    $variant1 = ProductVariant::factory()->create(['product_id' => $product->id]);
    $variant2 = ProductVariant::factory()->create(['product_id' => $product->id]);
    $product2 = Product::factory()->create(['has_variants' => false]);

    $data = [
        'name' => 'Combinado con Variantes',
        'is_active' => true,
        'special_bundle_price_capital' => 40.00,
        'special_bundle_price_interior' => 43.00,
        'validity_type' => 'permanent',
        'items' => [
            [
                'is_choice_group' => true,
                'choice_label' => 'Elige tamaño',
                'quantity' => 1,
                'sort_order' => 1,
                'options' => [
                    ['product_id' => $product->id, 'variant_id' => $variant1->id, 'sort_order' => 1],
                    ['product_id' => $product->id, 'variant_id' => $variant2->id, 'sort_order' => 2],
                ],
            ],
            [
                'is_choice_group' => false,
                'product_id' => $product2->id,
                'variant_id' => null,
                'quantity' => 1,
                'sort_order' => 2,
            ],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertRedirect();

    $promotion = Promotion::latest()->first();
    expect($promotion->bundleItems)->toHaveCount(2);
    $choiceGroup = $promotion->bundleItems->first();
    expect($choiceGroup->options)->toHaveCount(2);
    expect($choiceGroup->options->first()->variant_id)->toBe($variant1->id);
});

// ============================================================================
// Tests de Validaciones de Vigencia
// ============================================================================

test('rechaza valid_until anterior a valid_from', function () {
    $product = Product::factory()->create();

    $data = [
        'name' => 'Combinado Inválido',
        'is_active' => true,
        'special_bundle_price_capital' => 45.00,
        'special_bundle_price_interior' => 48.00,
        'valid_from' => '2024-12-25',
        'valid_until' => '2024-12-01',
        'items' => [
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertSessionHasErrors('valid_until');
});

test('rechaza time_until anterior o igual a time_from', function () {
    $product = Product::factory()->create();

    $data = [
        'name' => 'Combinado Horario Inválido',
        'is_active' => true,
        'special_bundle_price_capital' => 45.00,
        'special_bundle_price_interior' => 48.00,
        'time_from' => '18:00',
        'time_until' => '12:00',
        'items' => [
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertSessionHasErrors('time_until');
});

test('rechaza weekdays con valores fuera de rango', function () {
    $product = Product::factory()->create();

    $data = [
        'name' => 'Combinado Weekdays Inválidos',
        'is_active' => true,
        'special_bundle_price_capital' => 45.00,
        'special_bundle_price_interior' => 48.00,
        'weekdays' => [0, 8, 10], // 8 y 10 son inválidos
        'items' => [
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertSessionHasErrors();
});

test('acepta weekdays válidos de 1 a 7', function () {
    $product = Product::factory()->create();

    $data = [
        'name' => 'Combinado Weekdays Válidos',
        'is_active' => true,
        'special_bundle_price_capital' => 45.00,
        'special_bundle_price_interior' => 48.00,
        'validity_type' => 'permanent',
        'weekdays' => [1, 2, 3, 4, 5], // Lunes a Viernes
        'items' => [
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertRedirect();
    $this->assertDatabaseHas('promotions', ['name' => 'Combinado Weekdays Válidos']);
});

test('acepta vigencia con solo fechas', function () {
    $product = Product::factory()->create();

    $validFrom = now()->addDays(10)->format('Y-m-d');
    $validUntil = now()->addDays(35)->format('Y-m-d');

    $data = [
        'name' => 'Combinado Solo Fechas',
        'is_active' => true,
        'special_bundle_price_capital' => 45.00,
        'special_bundle_price_interior' => 48.00,
        'validity_type' => 'date_range',
        'valid_from' => $validFrom,
        'valid_until' => $validUntil,
        'items' => [
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertRedirect();
    $promotion = Promotion::where('name', 'Combinado Solo Fechas')->first();
    expect($promotion->valid_from)->toBe($validFrom);
    expect($promotion->time_from)->toBeNull();
});

test('acepta vigencia con solo horarios', function () {
    $product = Product::factory()->create();

    $data = [
        'name' => 'Combinado Solo Horarios',
        'is_active' => true,
        'special_bundle_price_capital' => 45.00,
        'special_bundle_price_interior' => 48.00,
        'validity_type' => 'time_range',
        'time_from' => '11:00',
        'time_until' => '14:00',
        'items' => [
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertRedirect();
    $promotion = Promotion::where('name', 'Combinado Solo Horarios')->first();
    expect($promotion->time_from)->toBe('11:00');
    expect($promotion->valid_from)->toBeNull();
});

test('acepta vigencia null para siempre válido', function () {
    $product = Product::factory()->create();

    $data = [
        'name' => 'Combinado Siempre Válido',
        'is_active' => true,
        'special_bundle_price_capital' => 45.00,
        'special_bundle_price_interior' => 48.00,
        'validity_type' => 'permanent',
        'items' => [
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertRedirect();
    $promotion = Promotion::where('name', 'Combinado Siempre Válido')->first();
    expect($promotion->valid_from)->toBeNull();
    expect($promotion->valid_until)->toBeNull();
    expect($promotion->weekdays)->toBeNull();
});

test('acepta vigencia con solo weekdays', function () {
    $product = Product::factory()->create();

    $data = [
        'name' => 'Combinado Solo Días',
        'is_active' => true,
        'special_bundle_price_capital' => 45.00,
        'special_bundle_price_interior' => 48.00,
        'validity_type' => 'permanent',
        'weekdays' => [6, 7], // Sábado y Domingo
        'items' => [
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertRedirect();
    $promotion = Promotion::where('name', 'Combinado Solo Días')->first();
    expect($promotion->weekdays)->toBe([6, 7]);
    expect($promotion->valid_from)->toBeNull();
    expect($promotion->time_from)->toBeNull();
});

// ============================================================================
// Tests de Actualización
// ============================================================================

test('puede actualizar combinado y cambiar items', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $oldProduct = Product::factory()->create();
    $promotion->bundleItems()->create([
        'is_choice_group' => false,
        'product_id' => $oldProduct->id,
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    $newProduct = Product::factory()->create();
    $updateData = [
        'name' => 'Combinado Actualizado',
        'is_active' => true,
        'special_bundle_price_capital' => 50.00,
        'special_bundle_price_interior' => 53.00,
        'validity_type' => 'permanent',
        'items' => [
            ['is_choice_group' => false, 'product_id' => $newProduct->id, 'quantity' => 1, 'sort_order' => 1],
            ['is_choice_group' => false, 'product_id' => $newProduct->id, 'quantity' => 1, 'sort_order' => 2],
        ],
    ];

    $response = $this->put(route('menu.promotions.bundle-specials.update', $promotion->id), $updateData);

    $response->assertRedirect();
    $promotion->refresh();
    expect($promotion->name)->toBe('Combinado Actualizado');
    expect($promotion->bundleItems)->toHaveCount(2);
});

test('puede cambiar vigencia temporal al actualizar', function () {
    $promotion = Promotion::factory()->create([
        'type' => 'bundle_special',
        'valid_from' => now()->subDays(30)->format('Y-m-d'),
        'valid_until' => now()->subDays(10)->format('Y-m-d'),
    ]);
    $product = Product::factory()->create();

    $validFrom = now()->addDays(10)->format('Y-m-d');
    $validUntil = now()->addDays(35)->format('Y-m-d');

    $updateData = [
        'name' => $promotion->name,
        'is_active' => true,
        'special_bundle_price_capital' => 45.00,
        'special_bundle_price_interior' => 48.00,
        'validity_type' => 'date_time_range',
        'valid_from' => $validFrom,
        'valid_until' => $validUntil,
        'time_from' => '17:00',
        'time_until' => '23:00',
        'weekdays' => [6, 7], // Sábado y Domingo
        'items' => [
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
        ],
    ];

    $response = $this->put(route('menu.promotions.bundle-specials.update', $promotion->id), $updateData);

    $response->assertRedirect();
    $promotion->refresh();
    expect($promotion->valid_from)->toBe($validFrom);
    expect($promotion->time_from)->toBe('17:00');
    expect($promotion->weekdays)->toBe([6, 7]);
});

// ============================================================================
// Tests de Eliminación
// ============================================================================

test('puede eliminar combinado con soft delete', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create();
    $promotion->bundleItems()->create([
        'is_choice_group' => false,
        'product_id' => $product->id,
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    $response = $this->delete(route('menu.promotions.bundle-specials.destroy', $promotion->id));

    $response->assertRedirect();
    $this->assertSoftDeleted('promotions', ['id' => $promotion->id]);
});

// ============================================================================
// Tests de Validaciones de Items
// ============================================================================

test('rechaza combinado con menos de 2 items', function () {
    $product = Product::factory()->create();

    $data = [
        'name' => 'Combinado con 1 Item',
        'is_active' => true,
        'special_bundle_price_capital' => 45.00,
        'special_bundle_price_interior' => 48.00,
        'items' => [
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertSessionHasErrors('items');
});

test('rechaza grupo de elección con menos de 2 opciones', function () {
    $product = Product::factory()->create();

    $data = [
        'name' => 'Combinado Grupo Inválido',
        'is_active' => true,
        'special_bundle_price_capital' => 45.00,
        'special_bundle_price_interior' => 48.00,
        'items' => [
            [
                'is_choice_group' => true,
                'choice_label' => 'Elige',
                'quantity' => 1,
                'sort_order' => 1,
                'options' => [
                    ['product_id' => $product->id, 'sort_order' => 1],
                ],
            ],
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertSessionHasErrors();
});

test('rechaza precios negativos', function () {
    $product = Product::factory()->create();

    $data = [
        'name' => 'Combinado Precio Negativo',
        'is_active' => true,
        'special_bundle_price_capital' => -10.00,
        'special_bundle_price_interior' => 48.00,
        'items' => [
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertSessionHasErrors('special_bundle_price_capital');
});

test('rechaza precio cero', function () {
    $product = Product::factory()->create();

    $data = [
        'name' => 'Combinado Precio Cero',
        'is_active' => true,
        'special_bundle_price_capital' => 45.00,
        'special_bundle_price_interior' => 0,
        'items' => [
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
            ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
        ],
    ];

    $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

    $response->assertSessionHasErrors('special_bundle_price_interior');
});

// ============================================================================
// Tests de Index y Stats
// ============================================================================

test('index muestra solo combinados', function () {
    Promotion::factory()->count(3)->create(['type' => 'bundle_special']);
    Promotion::factory()->count(2)->create(['type' => 'daily_special']);

    $response = $this->get(route('menu.promotions.bundle-specials.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('menu/promotions/bundle-specials/index')
        ->has('combinados', 3)
    );
});

test('stats calculan valores correctos', function () {
    // Crear combinados con diferentes estados
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => now()->subDays(5)->format('Y-m-d'),
        'valid_until' => now()->addDays(5)->format('Y-m-d'),
    ]);

    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => false,
    ]);

    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => now()->addDays(10)->format('Y-m-d'),
    ]);

    $response = $this->get(route('menu.promotions.bundle-specials.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('stats.total_combinados', 3)
        ->where('stats.active_combinados', 2)
    );
});
