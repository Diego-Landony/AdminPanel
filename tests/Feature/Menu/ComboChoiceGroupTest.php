<?php

use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    $permission = Permission::firstOrCreate(
        ['name' => 'menu.combos.create'],
        ['display_name' => 'Crear Combos', 'group' => 'menu']
    );
    $role = Role::firstOrCreate(
        ['name' => 'combo_manager'],
        ['description' => 'Combo Manager', 'is_system' => false]
    );
    $role->permissions()->syncWithoutDetaching([$permission->id]);
    $this->user->roles()->attach($role);

    $this->actingAs($this->user);

    $this->comboCategory = Category::factory()->create([
        'is_combo_category' => true,
        'is_active' => true,
        'uses_variants' => false,
    ]);

    // Crear categoría Subs (con variantes)
    $subsCategory = Category::factory()->create([
        'name' => 'Subs',
        'is_active' => true,
        'uses_variants' => true,
        'variant_definitions' => ['15cm', '30cm'],
    ]);

    // Crear productos CON variantes (como el sistema real)
    $this->products = [
        Product::factory()->create([
            'category_id' => $subsCategory->id,
            'name' => 'Italian BMT',
            'has_variants' => true,
            'is_active' => true,
        ]),
        Product::factory()->create([
            'category_id' => $subsCategory->id,
            'name' => 'Pollo Teriyaki',
            'has_variants' => true,
            'is_active' => true,
        ]),
        Product::factory()->create([
            'category_id' => $subsCategory->id,
            'name' => 'Atún',
            'has_variants' => true,
            'is_active' => true,
        ]),
    ];

    // Crear variantes para cada producto (15cm y 30cm)
    foreach ($this->products as $product) {
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '15cm',
            'size' => '15cm',
            'precio_pickup_capital' => 30.00,
            'precio_domicilio_capital' => 35.00,
            'precio_pickup_interior' => 32.00,
            'precio_domicilio_interior' => 37.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '30cm',
            'size' => '30cm',
            'precio_pickup_capital' => 60.00,
            'precio_domicilio_capital' => 65.00,
            'precio_pickup_interior' => 62.00,
            'precio_domicilio_interior' => 67.00,
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    // Producto SIN variantes para items fijos
    $this->bebida = Product::factory()->create([
        'name' => 'Coca Cola Personal',
        'has_variants' => false,
        'is_active' => true,
        'precio_pickup_capital' => 15.00,
        'precio_domicilio_capital' => 18.00,
        'precio_pickup_interior' => 16.00,
        'precio_domicilio_interior' => 19.00,
    ]);
});

test('puede crear combo con grupo de elección', function () {
    $response = $this->postJson(route('menu.combos.store'), [
        'category_id' => $this->comboCategory->id,
        'name' => 'Combo Test',
        'description' => 'Test description',
        'precio_pickup_capital' => 48.00,
        'precio_domicilio_capital' => 53.00,
        'precio_pickup_interior' => 50.00,
        'precio_domicilio_interior' => 55.00,
        'is_active' => false,
        'items' => [
            [
                'is_choice_group' => true,
                'choice_label' => 'Elige tu sub de 15cm',
                'quantity' => 1,
                'sort_order' => 0,
                'options' => [
                    [
                        'product_id' => $this->products[0]->id,
                        'variant_id' => $this->products[0]->variants->first()->id,
                        'sort_order' => 0,
                    ],
                    [
                        'product_id' => $this->products[1]->id,
                        'variant_id' => $this->products[1]->variants->first()->id,
                        'sort_order' => 1,
                    ],
                ],
            ],
            [
                'is_choice_group' => false,
                'product_id' => $this->bebida->id,
                'variant_id' => null,
                'quantity' => 1,
                'sort_order' => 1,
            ],
        ],
    ]);

    $response->assertStatus(201);

    $combo = Combo::where('name', 'Combo Test')->first();
    expect($combo)->not->toBeNull();
    expect($combo->items)->toHaveCount(2);

    $choiceGroup = $combo->items->where('is_choice_group', true)->first();
    expect($choiceGroup)->not->toBeNull();
    expect($choiceGroup->choice_label)->toBe('Elige tu sub de 15cm');
    expect($choiceGroup->options)->toHaveCount(2);
});

test('rechaza grupo de elección con menos de 2 opciones', function () {
    $response = $this->postJson(route('menu.combos.store'), [
        'category_id' => $this->comboCategory->id,
        'name' => 'Invalid Combo',
        'precio_pickup_capital' => 48.00,
        'precio_domicilio_capital' => 53.00,
        'precio_pickup_interior' => 50.00,
        'precio_domicilio_interior' => 55.00,
        'is_active' => false,
        'items' => [
            [
                'is_choice_group' => true,
                'choice_label' => 'Test Group',
                'quantity' => 1,
                'options' => [
                    [
                        'product_id' => $this->products[0]->id,
                        'variant_id' => $this->products[0]->variants->first()->id,
                        'sort_order' => 0,
                    ],
                ],
            ],
            [
                'is_choice_group' => false,
                'product_id' => $this->bebida->id,
                'quantity' => 1,
            ],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('items.0.options');
});

test('rechaza grupo de elección sin etiqueta', function () {
    $response = $this->postJson(route('menu.combos.store'), [
        'category_id' => $this->comboCategory->id,
        'name' => 'Invalid Combo',
        'precio_pickup_capital' => 48.00,
        'precio_domicilio_capital' => 53.00,
        'precio_pickup_interior' => 50.00,
        'precio_domicilio_interior' => 55.00,
        'is_active' => false,
        'items' => [
            [
                'is_choice_group' => true,
                'choice_label' => '',
                'quantity' => 1,
                'options' => [
                    ['product_id' => $this->products[0]->id, 'variant_id' => $this->products[0]->variants->first()->id],
                    ['product_id' => $this->products[1]->id, 'variant_id' => $this->products[1]->variants->first()->id],
                ],
            ],
            [
                'is_choice_group' => false,
                'product_id' => $this->bebida->id,
                'quantity' => 1,
            ],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('items.0.choice_label');
});

test('rechaza grupo de elección con opciones duplicadas', function () {
    $response = $this->postJson(route('menu.combos.store'), [
        'category_id' => $this->comboCategory->id,
        'name' => 'Invalid Combo',
        'precio_pickup_capital' => 48.00,
        'precio_domicilio_capital' => 53.00,
        'precio_pickup_interior' => 50.00,
        'precio_domicilio_interior' => 55.00,
        'is_active' => false,
        'items' => [
            [
                'is_choice_group' => true,
                'choice_label' => 'Test',
                'quantity' => 1,
                'options' => [
                    [
                        'product_id' => $this->products[0]->id,
                        'variant_id' => $this->products[0]->variants->first()->id,
                        'sort_order' => 0,
                    ],
                    [
                        'product_id' => $this->products[0]->id,
                        'variant_id' => $this->products[0]->variants->first()->id,
                        'sort_order' => 1,
                    ],
                ],
            ],
            [
                'is_choice_group' => false,
                'product_id' => $this->bebida->id,
                'quantity' => 1,
            ],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('items.0.options.1');
});

test('valida consistencia de variantes en grupo', function () {
    // Crear variante de 30cm para el segundo producto
    $variant30cm = ProductVariant::factory()->create([
        'product_id' => $this->products[1]->id,
        'name' => '30cm',
        'size' => '30cm',
        'precio_pickup_capital' => 50.00,
        'precio_domicilio_capital' => 55.00,
        'precio_pickup_interior' => 52.00,
        'precio_domicilio_interior' => 57.00,
    ]);

    $response = $this->postJson(route('menu.combos.store'), [
        'category_id' => $this->comboCategory->id,
        'name' => 'Invalid Combo',
        'precio_pickup_capital' => 48.00,
        'precio_domicilio_capital' => 53.00,
        'precio_pickup_interior' => 50.00,
        'precio_domicilio_interior' => 55.00,
        'is_active' => false,
        'items' => [
            [
                'is_choice_group' => true,
                'choice_label' => 'Test',
                'quantity' => 1,
                'options' => [
                    [
                        'product_id' => $this->products[0]->id,
                        'variant_id' => $this->products[0]->variants->where('size', '15cm')->first()->id,
                        'sort_order' => 0,
                    ],
                    [
                        'product_id' => $this->products[1]->id,
                        'variant_id' => $variant30cm->id,
                        'sort_order' => 1,
                    ],
                ],
            ],
            [
                'is_choice_group' => false,
                'product_id' => $this->bebida->id,
                'quantity' => 1,
            ],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('items.0.options');
});

test('rechaza productos inactivos en opciones', function () {
    // Desactivar un producto
    $this->products[0]->update(['is_active' => false]);

    $response = $this->postJson(route('menu.combos.store'), [
        'category_id' => $this->comboCategory->id,
        'name' => 'Invalid Combo',
        'precio_pickup_capital' => 48.00,
        'precio_domicilio_capital' => 53.00,
        'precio_pickup_interior' => 50.00,
        'precio_domicilio_interior' => 55.00,
        'is_active' => false,
        'items' => [
            [
                'is_choice_group' => true,
                'choice_label' => 'Test',
                'quantity' => 1,
                'options' => [
                    [
                        'product_id' => $this->products[0]->id,
                        'variant_id' => $this->products[0]->variants->first()->id,
                        'sort_order' => 0,
                    ],
                    [
                        'product_id' => $this->products[1]->id,
                        'variant_id' => $this->products[1]->variants->first()->id,
                        'sort_order' => 1,
                    ],
                ],
            ],
            [
                'is_choice_group' => false,
                'product_id' => $this->bebida->id,
                'quantity' => 1,
            ],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('items.0.options.0.product_id');
});

test('puede actualizar combo agregando grupo de elección', function () {
    // Crear combo inicial sin grupos
    $combo = Combo::factory()->create([
        'category_id' => $this->comboCategory->id,
    ]);

    $combo->items()->create([
        'product_id' => $this->bebida->id,
        'quantity' => 1,
        'is_choice_group' => false,
        'sort_order' => 0,
    ]);

    $editPermission = Permission::firstOrCreate(
        ['name' => 'menu.combos.edit'],
        ['display_name' => 'Editar Combos', 'group' => 'menu']
    );
    $this->user->roles->first()->permissions()->syncWithoutDetaching([$editPermission->id]);

    // Actualizar a combo con grupo
    $response = $this->putJson(route('menu.combos.update', $combo), [
        'category_id' => $this->comboCategory->id,
        'name' => $combo->name,
        'description' => $combo->description,
        'precio_pickup_capital' => $combo->precio_pickup_capital,
        'precio_domicilio_capital' => $combo->precio_domicilio_capital,
        'precio_pickup_interior' => $combo->precio_pickup_interior,
        'precio_domicilio_interior' => $combo->precio_domicilio_interior,
        'is_active' => false,
        'items' => [
            [
                'is_choice_group' => true,
                'choice_label' => 'Nuevo grupo',
                'quantity' => 1,
                'sort_order' => 0,
                'options' => [
                    ['product_id' => $this->products[0]->id, 'variant_id' => $this->products[0]->variants->first()->id, 'sort_order' => 0],
                    ['product_id' => $this->products[1]->id, 'variant_id' => $this->products[1]->variants->first()->id, 'sort_order' => 1],
                ],
            ],
            [
                'is_choice_group' => false,
                'product_id' => $this->bebida->id,
                'quantity' => 1,
                'sort_order' => 1,
            ],
        ],
    ]);

    $response->assertStatus(200);
    $combo->refresh();

    $choiceGroup = $combo->items->where('is_choice_group', true)->first();
    expect($choiceGroup)->not->toBeNull();
    expect($choiceGroup->choice_label)->toBe('Nuevo grupo');
    expect($choiceGroup->options)->toHaveCount(2);
});

test('combo disponible cuando tiene al menos una opción activa en grupo', function () {
    $combo = Combo::factory()->create([
        'category_id' => $this->comboCategory->id,
        'is_active' => true,
    ]);

    $choiceItem = $combo->items()->create([
        'is_choice_group' => true,
        'choice_label' => 'Test Group',
        'quantity' => 1,
        'sort_order' => 0,
    ]);

    $choiceItem->options()->create([
        'product_id' => $this->products[0]->id,
        'variant_id' => $this->products[0]->variants->first()->id,
        'sort_order' => 0,
    ]);

    $choiceItem->options()->create([
        'product_id' => $this->products[1]->id,
        'variant_id' => $this->products[1]->variants->first()->id,
        'sort_order' => 1,
    ]);

    // Combo disponible con todas activas
    $availableCombos = Combo::available()->get();
    expect($availableCombos->contains($combo))->toBeTrue();

    // Desactivar una opción
    $this->products[0]->update(['is_active' => false]);

    // Combo sigue disponible (tiene otras opciones)
    $availableCombos = Combo::available()->get();
    expect($availableCombos->contains($combo))->toBeTrue();

    // Desactivar todas las opciones
    $this->products[1]->update(['is_active' => false]);

    // Combo ya no disponible
    $availableCombos = Combo::available()->get();
    expect($availableCombos->contains($combo))->toBeFalse();
});

test('no puede eliminar producto usado en grupo de elección', function () {
    $deletePermission = Permission::firstOrCreate(
        ['name' => 'menu.products.delete'],
        ['display_name' => 'Eliminar Productos', 'group' => 'menu']
    );
    $this->user->roles->first()->permissions()->syncWithoutDetaching([$deletePermission->id]);

    $combo = Combo::factory()->create([
        'category_id' => $this->comboCategory->id,
    ]);

    $choiceItem = $combo->items()->create([
        'is_choice_group' => true,
        'choice_label' => 'Test Group',
        'quantity' => 1,
        'sort_order' => 0,
    ]);

    $choiceItem->options()->create([
        'product_id' => $this->products[0]->id,
        'variant_id' => $this->products[0]->variants->first()->id,
        'sort_order' => 0,
    ]);

    $response = $this->deleteJson(route('menu.products.destroy', $this->products[0]));

    $response->assertStatus(422);
    $responseData = $response->json();
    expect($responseData['message'])->toContain('Está usado');
    expect(Product::find($this->products[0]->id))->not->toBeNull();
});
