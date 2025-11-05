<?php

use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['menu.combos.create']);
    $this->actingAs($this->user);

    $menuStructure = createMenuStructureForComboTests();
    $this->comboCategory = $menuStructure['comboCategory'];
    $this->products = $menuStructure['products'];
    $this->bebida = $menuStructure['bebida'];
});

describe('Choice Group Creation', function () {
    test('can create combo with choice group', function () {
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
});

describe('Choice Group Validation', function () {
    test('validates choice group rules', function (callable $setup, string $expectedError) {
        $setup($this);

        $baseData = [
            'category_id' => $this->comboCategory->id,
            'name' => 'Invalid Combo',
            'precio_pickup_capital' => 48.00,
            'precio_domicilio_capital' => 53.00,
            'precio_pickup_interior' => 50.00,
            'precio_domicilio_interior' => 55.00,
            'is_active' => false,
        ];

        $response = $this->postJson(route('menu.combos.store'), array_merge($baseData, $this->testItems));
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors($expectedError);
    })->with([
        'less than 2 options' => [
            fn ($test) => $test->testItems = [
                'items' => [
                    [
                        'is_choice_group' => true,
                        'choice_label' => 'Test Group',
                        'quantity' => 1,
                        'options' => [
                            [
                                'product_id' => $test->products[0]->id,
                                'variant_id' => $test->products[0]->variants->first()->id,
                                'sort_order' => 0,
                            ],
                        ],
                    ],
                    ['is_choice_group' => false, 'product_id' => $test->bebida->id, 'quantity' => 1],
                ],
            ],
            'items.0.options',
        ],
        'missing label' => [
            fn ($test) => $test->testItems = [
                'items' => [
                    [
                        'is_choice_group' => true,
                        'choice_label' => '',
                        'quantity' => 1,
                        'options' => [
                            ['product_id' => $test->products[0]->id, 'variant_id' => $test->products[0]->variants->first()->id],
                            ['product_id' => $test->products[1]->id, 'variant_id' => $test->products[1]->variants->first()->id],
                        ],
                    ],
                    ['is_choice_group' => false, 'product_id' => $test->bebida->id, 'quantity' => 1],
                ],
            ],
            'items.0.choice_label',
        ],
        'duplicate options' => [
            fn ($test) => $test->testItems = [
                'items' => [
                    [
                        'is_choice_group' => true,
                        'choice_label' => 'Test',
                        'quantity' => 1,
                        'options' => [
                            [
                                'product_id' => $test->products[0]->id,
                                'variant_id' => $test->products[0]->variants->first()->id,
                                'sort_order' => 0,
                            ],
                            [
                                'product_id' => $test->products[0]->id,
                                'variant_id' => $test->products[0]->variants->first()->id,
                                'sort_order' => 1,
                            ],
                        ],
                    ],
                    ['is_choice_group' => false, 'product_id' => $test->bebida->id, 'quantity' => 1],
                ],
            ],
            'items.0.options.1',
        ],
        'inconsistent variants' => [
            function ($test) {
                $variant30cm = ProductVariant::factory()->create([
                    'product_id' => $test->products[1]->id,
                    'name' => '30cm',
                    'size' => '30cm',
                    'precio_pickup_capital' => 50.00,
                    'precio_domicilio_capital' => 55.00,
                    'precio_pickup_interior' => 52.00,
                    'precio_domicilio_interior' => 57.00,
                ]);

                $test->testItems = [
                    'items' => [
                        [
                            'is_choice_group' => true,
                            'choice_label' => 'Test',
                            'quantity' => 1,
                            'options' => [
                                [
                                    'product_id' => $test->products[0]->id,
                                    'variant_id' => $test->products[0]->variants->where('size', '15cm')->first()->id,
                                    'sort_order' => 0,
                                ],
                                [
                                    'product_id' => $test->products[1]->id,
                                    'variant_id' => $variant30cm->id,
                                    'sort_order' => 1,
                                ],
                            ],
                        ],
                        ['is_choice_group' => false, 'product_id' => $test->bebida->id, 'quantity' => 1],
                    ],
                ];
            },
            'items.0.options',
        ],
        'inactive products' => [
            function ($test) {
                $test->products[0]->update(['is_active' => false]);
                $test->testItems = [
                    'items' => [
                        [
                            'is_choice_group' => true,
                            'choice_label' => 'Test',
                            'quantity' => 1,
                            'options' => [
                                [
                                    'product_id' => $test->products[0]->id,
                                    'variant_id' => $test->products[0]->variants->first()->id,
                                    'sort_order' => 0,
                                ],
                                [
                                    'product_id' => $test->products[1]->id,
                                    'variant_id' => $test->products[1]->variants->first()->id,
                                    'sort_order' => 1,
                                ],
                            ],
                        ],
                        ['is_choice_group' => false, 'product_id' => $test->bebida->id, 'quantity' => 1],
                    ],
                ];
            },
            'items.0.options.0.product_id',
        ],
    ]);
});

describe('Choice Group Updates', function () {
    test('can update combo by adding choice group', function () {
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
});

describe('Choice Group Availability', function () {
    test('combo available when at least one option is active in group', function () {
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
});

describe('Product Deletion Protection', function () {
    test('cannot delete product used in choice group', function () {
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
});
