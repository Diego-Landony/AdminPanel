<?php

use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;

beforeEach(function () {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

test('puede listar combos', function () {
    $combo1 = Combo::factory()->create(['name' => 'Combo 1']);
    $combo2 = Combo::factory()->create(['name' => 'Combo 2']);

    $product = Product::factory()->create();
    $combo1->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'label' => 'Sub',
        'sort_order' => 1,
    ]);

    $response = $this->get(route('menu.combos.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('menu/combos/index')
        ->has('combos', 2)
        ->has('stats')
    );
});

test('puede buscar combos por nombre', function () {
    Combo::factory()->create(['name' => 'Combo Familiar']);
    Combo::factory()->create(['name' => 'Combo Personal']);

    $response = $this->get(route('menu.combos.index', ['search' => 'Familiar']));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('combos', 1)
        ->where('combos.0.name', 'Combo Familiar')
    );
});

test('puede crear un combo con productos', function () {
    $product1 = Product::factory()->create(['name' => 'Sub Italiano']);
    $product2 = Product::factory()->create(['name' => 'Bebida']);
    $category = Category::factory()->create(['is_combo_category' => true]);

    $comboData = [
        'name' => 'Combo Test',
        'description' => 'Descripción del combo',
        'precio_pickup_capital' => 100.00,
        'precio_domicilio_capital' => 110.00,
        'precio_pickup_interior' => 95.00,
        'precio_domicilio_interior' => 105.00,
        'is_active' => true,
        'sort_order' => 1,
        'items' => [
            [
                'product_id' => $product1->id,
                'quantity' => 1,
                'label' => 'Sub',
                'sort_order' => 1,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 1,
                'label' => 'Bebida',
                'sort_order' => 2,
            ],
        ],
        'category_id' => $category->id,
    ];

    $response = $this->post(route('menu.combos.store'), $comboData);

    $response->assertRedirect(route('menu.combos.index'));

    $this->assertDatabaseHas('combos', [
        'name' => 'Combo Test',
        'precio_pickup_capital' => 100.00,
        'is_active' => true,
        'category_id' => $category->id,
    ]);

    $combo = Combo::where('name', 'Combo Test')->first();
    expect($combo->items)->toHaveCount(2);
    expect($combo->category->id)->toBe($category->id);
});

test('requiere mínimo 2 productos en un combo', function () {
    $product = Product::factory()->create();

    $comboData = [
        'name' => 'Combo Inválido',
        'precio_pickup_capital' => 100.00,
        'precio_domicilio_capital' => 110.00,
        'precio_pickup_interior' => 95.00,
        'precio_domicilio_interior' => 105.00,
        'is_active' => true,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'label' => 'Sub',
                'sort_order' => 1,
            ],
        ],
    ];

    $response = $this->post(route('menu.combos.store'), $comboData);

    $response->assertSessionHasErrors(['items']);
});

test('valida que delivery precio sea mayor o igual que pickup', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $comboData = [
        'name' => 'Combo Test',
        'precio_pickup_capital' => 120.00,
        'precio_domicilio_capital' => 110.00, // Menor que pickup - inválido
        'precio_pickup_interior' => 95.00,
        'precio_domicilio_interior' => 105.00,
        'is_active' => true,
        'items' => [
            [
                'product_id' => $product1->id,
                'quantity' => 1,
                'label' => 'Sub',
                'sort_order' => 1,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 1,
                'label' => 'Bebida',
                'sort_order' => 2,
            ],
        ],
    ];

    $response = $this->post(route('menu.combos.store'), $comboData);

    $response->assertSessionHasErrors(['precio_domicilio_capital']);
});

test('puede editar un combo', function () {
    $category = Category::factory()->create(['is_combo_category' => true]);
    $combo = Combo::factory()->create(['name' => 'Nombre Original', 'category_id' => $category->id]);
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();
    $product3 = Product::factory()->create();

    $combo->items()->create([
        'product_id' => $product1->id,
        'quantity' => 1,
        'label' => 'Sub 1',
        'sort_order' => 1,
    ]);

    $combo->items()->create([
        'product_id' => $product2->id,
        'quantity' => 1,
        'label' => 'Sub 2',
        'sort_order' => 2,
    ]);

    $response = $this->put(route('menu.combos.update', $combo), [
        'name' => 'Nombre Actualizado',
        'description' => 'Nueva descripción',
        'precio_pickup_capital' => 120.00,
        'precio_domicilio_capital' => 130.00,
        'precio_pickup_interior' => 110.00,
        'precio_domicilio_interior' => 120.00,
        'is_active' => false,
        'category_id' => $category->id,
        'items' => [
            [
                'product_id' => $product2->id,
                'quantity' => 2,
                'label' => 'Sub Principal',
                'sort_order' => 1,
            ],
            [
                'product_id' => $product3->id,
                'quantity' => 1,
                'label' => 'Bebida',
                'sort_order' => 2,
            ],
        ],
    ]);

    $response->assertRedirect(route('menu.combos.index'));

    $this->assertDatabaseHas('combos', [
        'id' => $combo->id,
        'name' => 'Nombre Actualizado',
        'is_active' => false,
    ]);

    $combo->refresh();
    expect($combo->items)->toHaveCount(2);
    expect($combo->items->first()->product_id)->toBe($product2->id);
    expect($combo->items->first()->quantity)->toBe(2);
});

test('puede eliminar un combo', function () {
    $combo = Combo::factory()->create();
    $product = Product::factory()->create();

    $combo->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'label' => 'Sub',
        'sort_order' => 1,
    ]);

    $response = $this->delete(route('menu.combos.destroy', $combo));

    $response->assertRedirect(route('menu.combos.index'));

    $this->assertSoftDeleted('combos', [
        'id' => $combo->id,
    ]);
});

test('puede activar o desactivar un combo', function () {
    $combo = Combo::factory()->create(['is_active' => true]);

    $response = $this->post(route('menu.combos.toggle', $combo));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('combos', [
        'id' => $combo->id,
        'is_active' => false,
    ]);
});

test('no puede activar combo con productos inactivos', function () {
    $activeProduct = Product::factory()->create(['is_active' => true]);
    $inactiveProduct = Product::factory()->create(['is_active' => false, 'name' => 'Producto Inactivo']);

    $comboData = [
        'name' => 'Combo Test',
        'precio_pickup_capital' => 100.00,
        'precio_domicilio_capital' => 110.00,
        'precio_pickup_interior' => 95.00,
        'precio_domicilio_interior' => 105.00,
        'is_active' => true, // Intentando activar
        'items' => [
            [
                'product_id' => $activeProduct->id,
                'quantity' => 1,
                'label' => 'Sub Activo',
                'sort_order' => 1,
            ],
            [
                'product_id' => $inactiveProduct->id,
                'quantity' => 1,
                'label' => 'Sub Inactivo',
                'sort_order' => 2,
            ],
        ],
    ];

    $response = $this->post(route('menu.combos.store'), $comboData);

    $response->assertSessionHasErrors(['is_active']);
});

test('puede crear combo con productos duplicados en diferentes cantidades', function () {
    $product = Product::factory()->create();
    $category = Category::factory()->create(['is_combo_category' => true]);

    $comboData = [
        'name' => 'Combo 2x1',
        'precio_pickup_capital' => 100.00,
        'precio_domicilio_capital' => 110.00,
        'precio_pickup_interior' => 95.00,
        'precio_domicilio_interior' => 105.00,
        'is_active' => true,
        'category_id' => $category->id,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'label' => 'Sub 1',
                'sort_order' => 1,
            ],
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'label' => 'Sub 2',
                'sort_order' => 2,
            ],
        ],
    ];

    $response = $this->post(route('menu.combos.store'), $comboData);

    $response->assertRedirect(route('menu.combos.index'));

    $combo = Combo::where('name', 'Combo 2x1')->first();
    expect($combo->items)->toHaveCount(2);
});

test('valida nombre único al crear combo', function () {
    Combo::factory()->create(['name' => 'Combo Existente']);

    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $comboData = [
        'name' => 'Combo Existente',
        'precio_pickup_capital' => 100.00,
        'precio_domicilio_capital' => 110.00,
        'precio_pickup_interior' => 95.00,
        'precio_domicilio_interior' => 105.00,
        'is_active' => true,
        'items' => [
            [
                'product_id' => $product1->id,
                'quantity' => 1,
                'label' => 'Sub',
                'sort_order' => 1,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 1,
                'label' => 'Bebida',
                'sort_order' => 2,
            ],
        ],
    ];

    $response = $this->post(route('menu.combos.store'), $comboData);

    $response->assertSessionHasErrors(['name']);
});

test('permite mismo nombre al editar el mismo combo', function () {
    $combo = Combo::factory()->create(['name' => 'Combo Original']);
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $combo->items()->createMany([
        [
            'product_id' => $product1->id,
            'quantity' => 1,
            'label' => 'Sub',
            'sort_order' => 1,
        ],
        [
            'product_id' => $product2->id,
            'quantity' => 1,
            'label' => 'Bebida',
            'sort_order' => 2,
        ],
    ]);

    $response = $this->put(route('menu.combos.update', $combo), [
        'name' => 'Combo Original', // Mismo nombre
        'precio_pickup_capital' => 100.00,
        'precio_domicilio_capital' => 110.00,
        'precio_pickup_interior' => 95.00,
        'precio_domicilio_interior' => 105.00,
        'is_active' => true,
        'items' => [
            [
                'product_id' => $product1->id,
                'quantity' => 1,
                'label' => 'Sub',
                'sort_order' => 1,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 1,
                'label' => 'Bebida',
                'sort_order' => 2,
            ],
        ],
    ]);

    $response->assertRedirect();
});

test('stats muestra combos activos y disponibles correctamente', function () {
    $activeProduct = Product::factory()->create(['is_active' => true]);
    $inactiveProduct = Product::factory()->create(['is_active' => false]);

    $comboActive = Combo::factory()->create(['is_active' => true]);
    $comboActive->items()->createMany([
        [
            'product_id' => $activeProduct->id,
            'quantity' => 1,
            'label' => 'Sub 1',
            'sort_order' => 1,
        ],
        [
            'product_id' => $activeProduct->id,
            'quantity' => 1,
            'label' => 'Sub 2',
            'sort_order' => 2,
        ],
    ]);

    $comboInactive = Combo::factory()->create(['is_active' => false]);
    $comboInactive->items()->createMany([
        [
            'product_id' => $activeProduct->id,
            'quantity' => 1,
            'label' => 'Sub 1',
            'sort_order' => 1,
        ],
        [
            'product_id' => $activeProduct->id,
            'quantity' => 1,
            'label' => 'Sub 2',
            'sort_order' => 2,
        ],
    ]);

    $comboWithInactiveProduct = Combo::factory()->create(['is_active' => true]);
    $comboWithInactiveProduct->items()->createMany([
        [
            'product_id' => $activeProduct->id,
            'quantity' => 1,
            'label' => 'Sub Activo',
            'sort_order' => 1,
        ],
        [
            'product_id' => $inactiveProduct->id,
            'quantity' => 1,
            'label' => 'Sub Inactivo',
            'sort_order' => 2,
        ],
    ]);

    $response = $this->get(route('menu.combos.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('stats.total_combos', 3)
        ->where('stats.active_combos', 2)
        ->where('stats.available_combos', 1) // Solo el primero está activo con todos los productos activos
    );
});

test('puede cambiar categoría del combo', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();
    $category1 = Category::factory()->create(['name' => 'Combos', 'is_combo_category' => true]);
    $category2 = Category::factory()->create(['name' => 'Ofertas', 'is_combo_category' => true]);

    $combo = Combo::factory()->create(['category_id' => $category1->id]);
    $combo->items()->createMany([
        [
            'product_id' => $product1->id,
            'quantity' => 1,
            'label' => 'Sub',
            'sort_order' => 1,
        ],
        [
            'product_id' => $product2->id,
            'quantity' => 1,
            'label' => 'Bebida',
            'sort_order' => 2,
        ],
    ]);

    expect($combo->category->id)->toBe($category1->id);

    $response = $this->put(route('menu.combos.update', $combo), [
        'name' => $combo->name,
        'category_id' => $category2->id,
        'precio_pickup_capital' => 100.00,
        'precio_domicilio_capital' => 110.00,
        'precio_pickup_interior' => 95.00,
        'precio_domicilio_interior' => 105.00,
        'is_active' => true,
        'items' => [
            [
                'product_id' => $product1->id,
                'quantity' => 1,
                'label' => 'Sub',
                'sort_order' => 1,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 1,
                'label' => 'Bebida',
                'sort_order' => 2,
            ],
        ],
    ]);

    $response->assertRedirect();

    $combo->refresh();
    expect($combo->category->id)->toBe($category2->id);
});

test('valida que la categoría sea de tipo combo', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();
    $regularCategory = Category::factory()->create(['is_combo_category' => false]);

    $comboData = [
        'name' => 'Combo Test',
        'category_id' => $regularCategory->id,
        'precio_pickup_capital' => 100.00,
        'precio_domicilio_capital' => 110.00,
        'precio_pickup_interior' => 95.00,
        'precio_domicilio_interior' => 105.00,
        'is_active' => true,
        'items' => [
            [
                'product_id' => $product1->id,
                'quantity' => 1,
                'label' => 'Sub',
                'sort_order' => 1,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 1,
                'label' => 'Bebida',
                'sort_order' => 2,
            ],
        ],
    ];

    $response = $this->post(route('menu.combos.store'), $comboData);

    $response->assertSessionHasErrors(['category_id']);
});
