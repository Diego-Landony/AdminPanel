<?php

use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\ComboItem;
use App\Models\Menu\ComboItemOption;
use App\Models\Menu\Product;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Combo Creation with Choice Groups', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
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
        $this->product3 = Product::factory()->create(['is_active' => true]);
    });

    test('can create combo with choice group', function () {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Combo con Grupo',
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
                            'product_id' => $this->product2->id,
                            'variant_id' => null,
                            'sort_order' => 1,
                        ],
                    ],
                ],
                [
                    'product_id' => $this->product3->id,
                    'variant_id' => null,
                    'quantity' => 1,
                    'sort_order' => 1,
                ],
            ],
        ];

        $response = $this->postJson(route('menu.combos.store'), $data);

        $response->assertStatus(201);

        // Verificar que el combo se creó
        $combo = Combo::where('name', 'Combo con Grupo')->first();
        expect($combo)->not->toBeNull();

        // Verificar que tiene 2 items
        expect($combo->items)->toHaveCount(2);

        // Verificar que el primer item es un grupo de elección
        $groupItem = $combo->items->first();
        expect($groupItem->is_choice_group)->toBeTrue();
        expect($groupItem->choice_label)->toBe('Elige tu complemento');
        expect($groupItem->product_id)->toBeNull();

        // Verificar que el grupo tiene 2 opciones
        expect($groupItem->options)->toHaveCount(2);

        // Verificar que el segundo item es fijo
        $fixedItem = $combo->items->last();
        expect($fixedItem->is_choice_group)->toBeFalse();
        expect($fixedItem->product_id)->toBe($this->product3->id);
    });

    test('can create combo with multiple choice groups', function () {
        $product4 = Product::factory()->create(['is_active' => true]);
        $product5 = Product::factory()->create(['is_active' => true]);

        $data = [
            'category_id' => $this->category->id,
            'name' => 'Combo Multi Grupos',
            'description' => 'Test',
            'precio_pickup_capital' => 60.00,
            'precio_domicilio_capital' => 65.00,
            'precio_pickup_interior' => 60.00,
            'precio_domicilio_interior' => 65.00,
            'is_active' => false,
            'items' => [
                [
                    'is_choice_group' => true,
                    'choice_label' => 'Grupo 1',
                    'quantity' => 1,
                    'sort_order' => 0,
                    'options' => [
                        ['product_id' => $this->product1->id, 'variant_id' => null, 'sort_order' => 0],
                        ['product_id' => $this->product2->id, 'variant_id' => null, 'sort_order' => 1],
                    ],
                ],
                [
                    'is_choice_group' => true,
                    'choice_label' => 'Grupo 2',
                    'quantity' => 1,
                    'sort_order' => 1,
                    'options' => [
                        ['product_id' => $product4->id, 'variant_id' => null, 'sort_order' => 0],
                        ['product_id' => $product5->id, 'variant_id' => null, 'sort_order' => 1],
                    ],
                ],
            ],
        ];

        $response = $this->postJson(route('menu.combos.store'), $data);

        $response->assertStatus(201);

        $combo = Combo::where('name', 'Combo Multi Grupos')->first();
        expect($combo->items)->toHaveCount(2);
        expect($combo->items->where('is_choice_group', true))->toHaveCount(2);
    });

    test('can create mixed combo with fixed items and groups', function () {
        $data = [
            'category_id' => $this->category->id,
            'name' => 'Combo Mixto',
            'description' => 'Test',
            'precio_pickup_capital' => 55.00,
            'precio_domicilio_capital' => 60.00,
            'precio_pickup_interior' => 55.00,
            'precio_domicilio_interior' => 60.00,
            'is_active' => false,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'variant_id' => null,
                    'quantity' => 1,
                    'sort_order' => 0,
                ],
                [
                    'is_choice_group' => true,
                    'choice_label' => 'Elige uno',
                    'quantity' => 1,
                    'sort_order' => 1,
                    'options' => [
                        ['product_id' => $this->product2->id, 'variant_id' => null, 'sort_order' => 0],
                        ['product_id' => $this->product3->id, 'variant_id' => null, 'sort_order' => 1],
                    ],
                ],
            ],
        ];

        $response = $this->postJson(route('menu.combos.store'), $data);

        $response->assertStatus(201);

        $combo = Combo::where('name', 'Combo Mixto')->first();
        expect($combo->items)->toHaveCount(2);
        expect($combo->items->where('is_choice_group', false))->toHaveCount(1);
        expect($combo->items->where('is_choice_group', true))->toHaveCount(1);
    });
});

describe('Combo Update and Edit', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $editPerm = Permission::firstOrCreate(
            ['name' => 'menu.combos.edit', 'display_name' => 'Editar Combos', 'group' => 'menu']
        );
        $viewPerm = Permission::firstOrCreate(
            ['name' => 'menu.combos.view', 'display_name' => 'Ver Combos', 'group' => 'menu']
        );
        $role = Role::firstOrCreate(
            ['name' => 'combo_editor'],
            ['description' => 'Combo Editor', 'is_system' => false]
        );
        $role->permissions()->syncWithoutDetaching([$editPerm->id, $viewPerm->id]);
        $this->user->roles()->attach($role);
        $this->actingAs($this->user);

        $this->category = Category::factory()->create([
            'is_active' => true,
            'is_combo_category' => true,
        ]);

        $this->product1 = Product::factory()->create(['is_active' => true]);
        $this->product2 = Product::factory()->create(['is_active' => true]);
    });

    test('can update combo by adding choice group', function () {
        $combo = Combo::factory()->create(['category_id' => $this->category->id]);
        $item = ComboItem::create([
            'combo_id' => $combo->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'sort_order' => 0,
        ]);

        $product3 = Product::factory()->create(['is_active' => true]);

        $data = [
            'category_id' => $this->category->id,
            'name' => $combo->name,
            'description' => $combo->description,
            'precio_pickup_capital' => $combo->precio_pickup_capital,
            'precio_domicilio_capital' => $combo->precio_domicilio_capital,
            'precio_pickup_interior' => $combo->precio_pickup_interior,
            'precio_domicilio_interior' => $combo->precio_domicilio_interior,
            'is_active' => false,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'variant_id' => null,
                    'quantity' => 1,
                    'sort_order' => 0,
                ],
                [
                    'is_choice_group' => true,
                    'choice_label' => 'Nuevo Grupo',
                    'quantity' => 1,
                    'sort_order' => 1,
                    'options' => [
                        ['product_id' => $this->product2->id, 'variant_id' => null, 'sort_order' => 0],
                        ['product_id' => $product3->id, 'variant_id' => null, 'sort_order' => 1],
                    ],
                ],
            ],
        ];

        $response = $this->putJson(route('menu.combos.update', $combo), $data);

        $response->assertStatus(200);

        $combo->refresh();
        expect($combo->items)->toHaveCount(2);
        expect($combo->items->where('is_choice_group', true))->toHaveCount(1);
    });

    test('can view combo with groups in edit', function () {
        $combo = Combo::factory()->create(['category_id' => $this->category->id]);
        $groupItem = ComboItem::create([
            'combo_id' => $combo->id,
            'is_choice_group' => true,
            'choice_label' => 'Test Group',
            'quantity' => 1,
            'sort_order' => 0,
        ]);

        ComboItemOption::create([
            'combo_item_id' => $groupItem->id,
            'product_id' => $this->product1->id,
            'sort_order' => 0,
        ]);

        ComboItemOption::create([
            'combo_item_id' => $groupItem->id,
            'product_id' => $this->product2->id,
            'sort_order' => 1,
        ]);

        $response = $this->get(route('menu.combos.edit', $combo));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('menu/combos/edit')
            ->has('combo.items.0.options', 2)
        );
    });
});

describe('Product Deletion Prevention', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $permission = Permission::firstOrCreate(
            ['name' => 'menu.products.delete', 'display_name' => 'Eliminar Productos', 'group' => 'menu']
        );
        $role = Role::firstOrCreate(
            ['name' => 'product_manager'],
            ['description' => 'Product Manager', 'is_system' => false]
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $this->user->roles()->attach($role);
        $this->actingAs($this->user);

        $this->category = Category::factory()->create([
            'is_active' => true,
            'is_combo_category' => true,
        ]);
    });

    test('cannot delete product used in choice group', function () {
        $product = Product::factory()->create(['is_active' => true]);
        $product2 = Product::factory()->create(['is_active' => true]);

        $combo = Combo::factory()->create(['category_id' => $this->category->id]);
        $groupItem = ComboItem::create([
            'combo_id' => $combo->id,
            'is_choice_group' => true,
            'choice_label' => 'Test Group',
            'quantity' => 1,
            'sort_order' => 0,
        ]);

        ComboItemOption::create([
            'combo_item_id' => $groupItem->id,
            'product_id' => $product->id,
            'sort_order' => 0,
        ]);

        $response = $this->deleteJson(route('menu.products.destroy', $product));

        $response->assertUnprocessable();

        $responseData = $response->json();
        expect($responseData['message'])->toContain('Está usado');

        // Verificar que el producto no fue eliminado
        expect(Product::find($product->id))->not->toBeNull();
    });

    test('mensaje de error incluye nombre del combo', function () {
        $product = Product::factory()->create(['is_active' => true]);
        $combo = Combo::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Combo Especial',
        ]);

        $groupItem = ComboItem::create([
            'combo_id' => $combo->id,
            'is_choice_group' => true,
            'choice_label' => 'Test Group',
            'quantity' => 1,
            'sort_order' => 0,
        ]);

        ComboItemOption::create([
            'combo_item_id' => $groupItem->id,
            'product_id' => $product->id,
            'sort_order' => 0,
        ]);

        $response = $this->from(route('menu.products.index'))
            ->deleteJson(route('menu.products.destroy', $product));

        $response->assertUnprocessable();

        $responseData = $response->json();
        $errorMessage = $responseData['message'];

        expect($errorMessage)->toContain('Combo Especial');
        expect($errorMessage)->toContain('1 combo(s)');
    });

    test('can delete product not used in groups', function () {
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->deleteJson(route('menu.products.destroy', $product));

        $response->assertStatus(200);

        // Verificar que el producto fue eliminado
        expect(Product::find($product->id))->toBeNull();
    });
});
