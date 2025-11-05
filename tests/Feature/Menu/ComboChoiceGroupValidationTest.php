<?php

use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Duplicate Option Validation', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();

        // Dar permisos al usuario
        $permission = Permission::firstOrCreate(
            ['name' => 'menu.combos.create', 'display_name' => 'Crear Combos', 'group' => 'menu']
        );
        $role = Role::firstOrCreate(
            ['name' => 'combo_manager'],
            ['description' => 'Combo Manager', 'is_system' => false]
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $this->user->roles()->attach($role);

        $this->actingAs($this->user);

        $this->category = Category::factory()->create([
            'is_active' => true,
            'is_combo_category' => true,
        ]);

        $this->product1 = Product::factory()->create(['is_active' => true]);
        $this->product2 = Product::factory()->create(['is_active' => true]);
    });

    test('rejects groups with duplicate options - same product without variants', function () {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Combo Test',
            'description' => 'Test',
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 50.00,
            'precio_domicilio_interior' => 55.00,
            'is_active' => false,
            'items' => [
                [
                    'is_choice_group' => true,
                    'choice_label' => 'Elige tu complemento',
                    'quantity' => 1,
                    'sort_order' => 0,
                    'options' => [
                        [
                            'product_id' => $this->product1->id,
                            'variant_id' => null,
                            'sort_order' => 0,
                        ],
                        [
                            'product_id' => $this->product1->id, // DUPLICADO
                            'variant_id' => null,
                            'sort_order' => 1,
                        ],
                    ],
                ],
                [
                    'product_id' => $this->product2->id,
                    'variant_id' => null,
                    'quantity' => 1,
                    'sort_order' => 1,
                ],
            ],
        ];

        $response = $this->postJson(route('menu.combos.store'), $data);

        $response->assertUnprocessable();
        $allErrors = $response->json('errors');

        // Buscar el error de opción duplicada en cualquier campo de opciones
        $foundDuplicateError = false;
        foreach ($allErrors as $key => $messages) {
            if (str_contains($key, 'items.0.options') && is_array($messages)) {
                foreach ($messages as $message) {
                    if (str_contains($message, 'Esta opción ya existe en el grupo')) {
                        $foundDuplicateError = true;
                        break 2;
                    }
                }
            }
        }

        expect($foundDuplicateError)->toBeTrue('No se encontró el error de opción duplicada');
    });

    test('rejects groups with duplicate options - same product and same variant', function () {
        $productWithVariants = Product::factory()->create([
            'is_active' => true,
            'has_variants' => true,
        ]);

        $variant1 = ProductVariant::factory()->create([
            'product_id' => $productWithVariants->id,
            'is_active' => true,
        ]);

        $data = [
            'category_id' => $this->category->id,
            'name' => 'Combo Test Variantes',
            'description' => 'Test',
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 50.00,
            'precio_domicilio_interior' => 55.00,
            'is_active' => false,
            'items' => [
                [
                    'is_choice_group' => true,
                    'choice_label' => 'Elige tu sub',
                    'quantity' => 1,
                    'sort_order' => 0,
                    'options' => [
                        [
                            'product_id' => $productWithVariants->id,
                            'variant_id' => $variant1->id,
                            'sort_order' => 0,
                        ],
                        [
                            'product_id' => $productWithVariants->id, // DUPLICADO
                            'variant_id' => $variant1->id, // MISMA VARIANTE
                            'sort_order' => 1,
                        ],
                    ],
                ],
                [
                    'product_id' => $this->product2->id,
                    'variant_id' => null,
                    'quantity' => 1,
                    'sort_order' => 1,
                ],
            ],
        ];

        $response = $this->postJson(route('menu.combos.store'), $data);

        $response->assertUnprocessable();
        $allErrors = $response->json('errors');

        // Buscar el error de opción duplicada en cualquier campo de opciones
        $foundDuplicateError = false;
        foreach ($allErrors as $key => $messages) {
            if (str_contains($key, 'items.0.options') && is_array($messages)) {
                foreach ($messages as $message) {
                    if (str_contains($message, 'Esta opción ya existe en el grupo')) {
                        $foundDuplicateError = true;
                        break 2;
                    }
                }
            }
        }

        expect($foundDuplicateError)->toBeTrue('No se encontró el error de opción duplicada');
    });

    test('accepts groups with different options - same product, different variants', function () {
        $productWithVariants = Product::factory()->create([
            'is_active' => true,
            'has_variants' => true,
        ]);

        $variant1 = ProductVariant::factory()->create([
            'product_id' => $productWithVariants->id,
            'is_active' => true,
        ]);

        $variant2 = ProductVariant::factory()->create([
            'product_id' => $productWithVariants->id,
            'is_active' => true,
        ]);

        $data = [
            'category_id' => $this->category->id,
            'name' => 'Combo Test Variantes Diferentes',
            'description' => 'Test',
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 50.00,
            'precio_domicilio_interior' => 55.00,
            'is_active' => false,
            'items' => [
                [
                    'is_choice_group' => true,
                    'choice_label' => 'Elige tu sub',
                    'quantity' => 1,
                    'sort_order' => 0,
                    'options' => [
                        [
                            'product_id' => $productWithVariants->id,
                            'variant_id' => $variant1->id,
                            'sort_order' => 0,
                        ],
                        [
                            'product_id' => $productWithVariants->id,
                            'variant_id' => $variant2->id, // DIFERENTE VARIANTE
                            'sort_order' => 1,
                        ],
                    ],
                ],
                [
                    'product_id' => $this->product2->id,
                    'variant_id' => null,
                    'quantity' => 1,
                    'sort_order' => 1,
                ],
            ],
        ];

        $response = $this->postJson(route('menu.combos.store'), $data);

        $response->assertStatus(201);
    });

    test('accepts groups with different options - completely different products', function () {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Combo Test Productos Diferentes',
            'description' => 'Test',
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 50.00,
            'precio_domicilio_interior' => 55.00,
            'is_active' => false,
            'items' => [
                [
                    'is_choice_group' => true,
                    'choice_label' => 'Elige tu complemento',
                    'quantity' => 1,
                    'sort_order' => 0,
                    'options' => [
                        [
                            'product_id' => $this->product1->id,
                            'variant_id' => null,
                            'sort_order' => 0,
                        ],
                        [
                            'product_id' => $this->product2->id, // DIFERENTE PRODUCTO
                            'variant_id' => null,
                            'sort_order' => 1,
                        ],
                    ],
                ],
                [
                    'product_id' => $this->product1->id,
                    'variant_id' => null,
                    'quantity' => 1,
                    'sort_order' => 1,
                ],
            ],
        ];

        $response = $this->postJson(route('menu.combos.store'), $data);

        $response->assertStatus(201);
    });
});

describe('Other Choice Group Validations', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();

        // Crear rol con permisos
        $permission = Permission::firstOrCreate(['name' => 'menu.combos.create', 'display_name' => 'Crear Combos', 'group' => 'menu']);
        $role = Role::firstOrCreate(['name' => 'test_role', 'description' => 'Test Role']);
        $role->permissions()->sync([$permission->id]);
        $this->user->roles()->attach($role->id);

        $this->actingAs($this->user);

        $this->category = Category::factory()->create([
            'is_active' => true,
            'is_combo_category' => true,
        ]);

        $this->product1 = Product::factory()->create(['is_active' => true]);
        $this->product2 = Product::factory()->create(['is_active' => true]);
    });

    test('rejects groups with less than 2 options', function () {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Combo Test',
            'description' => 'Test',
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 50.00,
            'precio_domicilio_interior' => 55.00,
            'is_active' => false,
            'items' => [
                [
                    'is_choice_group' => true,
                    'choice_label' => 'Elige tu complemento',
                    'quantity' => 1,
                    'sort_order' => 0,
                    'options' => [
                        [
                            'product_id' => $this->product1->id,
                            'variant_id' => null,
                            'sort_order' => 0,
                        ],
                        // Solo 1 opción - debe fallar
                    ],
                ],
                [
                    'product_id' => $this->product2->id,
                    'variant_id' => null,
                    'quantity' => 1,
                    'sort_order' => 1,
                ],
            ],
        ];

        $response = $this->postJson(route('menu.combos.store'), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('items.0.options');
    });

    test('rejects groups without label', function () {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Combo Test',
            'description' => 'Test',
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 50.00,
            'precio_domicilio_interior' => 55.00,
            'is_active' => false,
            'items' => [
                [
                    'is_choice_group' => true,
                    'choice_label' => '', // SIN ETIQUETA
                    'quantity' => 1,
                    'sort_order' => 0,
                    'options' => [
                        [
                            'product_id' => $this->product1->id,
                            'variant_id' => null,
                            'sort_order' => 0,
                        ],
                        [
                            'product_id' => $this->product2->id,
                            'variant_id' => null,
                            'sort_order' => 1,
                        ],
                    ],
                ],
                [
                    'product_id' => $this->product1->id,
                    'variant_id' => null,
                    'quantity' => 1,
                    'sort_order' => 1,
                ],
            ],
        ];

        $response = $this->postJson(route('menu.combos.store'), $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('items.0.choice_label');
    });
});
