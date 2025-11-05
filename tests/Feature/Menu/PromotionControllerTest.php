<?php

use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Promotion;

beforeEach(function () {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Daily Special Operations', function () {
    test('can list daily_special promotions', function () {
        Promotion::factory()->count(2)->create(['type' => 'daily_special']);
        Promotion::factory()->create(['type' => 'two_for_one']);

        $response = $this->get(route('menu.promotions.daily-special.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/promotions/daily-special/index')
            ->has('promotions.data', 2)
        );
    });

    test('can list promotions with status filter', function () {
        Promotion::factory()->create(['is_active' => true, 'type' => 'daily_special']);
        Promotion::factory()->create(['is_active' => false, 'type' => 'daily_special']);

        $response = $this->get(route('menu.promotions.daily-special.index', ['is_active' => 1]));

        $response->assertSuccessful();
    });

    test('can create daily_special promotion with weekdays', function () {
        $product = Product::factory()->create();

        $promotionData = [
            'name' => 'Sub del Día Lunes',
            'description' => 'Promoción especial de lunes',
            'type' => 'daily_special',
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'category_id' => $product->category_id,
                    'special_price_capital' => 35.00,
                    'special_price_interior' => 30.00,
                    'service_type' => 'both',
                    'validity_type' => 'weekdays',
                    'weekdays' => [1, 2],
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
            'category_id' => $product->category_id,
            'special_price_capital' => 35.00,
            'validity_type' => 'weekdays',
        ]);
    });

    test('can create promotion with date_range validity_type', function () {
        $product = Product::factory()->create();

        $promotionData = [
            'name' => 'Promoción Navideña',
            'type' => 'daily_special',
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'category_id' => $product->category_id,
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
            'category_id' => $product->category_id,
            'validity_type' => 'date_range',
            'valid_from' => '2025-12-20',
            'valid_until' => '2025-12-31',
        ]);
    });

    test('can create daily special with variant_id', function () {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['size' => '15cm']);

        $promotionData = [
            'name' => 'Sub 15cm del Día',
            'description' => 'Promoción para sub de 15cm',
            'type' => 'daily_special',
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'variant_id' => $variant->id,
                    'special_price_capital' => 35.00,
                    'special_price_interior' => 30.00,
                    'service_type' => 'both',
                    'validity_type' => 'weekdays',
                    'weekdays' => [1, 2],
                ],
            ],
        ];

        $response = $this->post(route('menu.promotions.store'), $promotionData);

        $response->assertRedirect();

        $this->assertDatabaseHas('promotion_items', [
            'product_id' => $product->id,
            'category_id' => $product->category_id,
            'variant_id' => $variant->id,
            'special_price_capital' => 35.00,
        ]);
    });

    test('daily special create loads products with variants', function () {
        $product = Product::factory()->create();
        $variant1 = ProductVariant::factory()->for($product)->create([
            'name' => 'Sub 15cm',
            'size' => '15cm',
            'sort_order' => 1,
        ]);
        $variant2 = ProductVariant::factory()->for($product)->create([
            'name' => 'Sub 30cm',
            'size' => '30cm',
            'sort_order' => 2,
        ]);

        $response = $this->get(route('menu.promotions.daily-special.create'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/promotions/daily-special/create')
            ->has('products', 1)
            ->where('products.0.id', $product->id)
            ->has('products.0.variants', 2)
            ->where('products.0.variants.0.id', $variant1->id)
            ->where('products.0.variants.1.id', $variant2->id)
        );
    });

    test('daily special edit loads products with variants', function () {
        $promotion = Promotion::factory()->create(['type' => 'daily_special']);
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['size' => '15cm', 'sort_order' => 1]);
        ProductVariant::factory()->for($product)->create(['size' => '30cm', 'sort_order' => 2]);

        $response = $this->get(route('menu.promotions.edit', $promotion));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/promotions/daily-special/edit')
            ->has('products', 1)
            ->has('products.0.variants', 2)
        );
    });
});

describe('Percentage Operations', function () {
    test('can create percentage with variant_id', function () {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['size' => '30cm']);

        $promotionData = [
            'name' => 'Descuento Sub 30cm',
            'description' => 'Descuento para sub de 30cm',
            'type' => 'percentage_discount',
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'variant_id' => $variant->id,
                    'discount_percentage' => 15.00,
                    'service_type' => 'both',
                    'validity_type' => 'permanent',
                ],
            ],
        ];

        $response = $this->post(route('menu.promotions.store'), $promotionData);

        $response->assertRedirect();

        $this->assertDatabaseHas('promotion_items', [
            'product_id' => $product->id,
            'category_id' => $product->category_id,
            'variant_id' => $variant->id,
            'discount_percentage' => 15.00,
        ]);
    });

    test('percentage create loads products with variants', function () {
        $product = Product::factory()->create();
        $variant1 = ProductVariant::factory()->for($product)->create([
            'name' => 'Sub 15cm',
            'size' => '15cm',
            'sort_order' => 1,
        ]);
        $variant2 = ProductVariant::factory()->for($product)->create([
            'name' => 'Sub 30cm',
            'size' => '30cm',
            'sort_order' => 2,
        ]);

        $response = $this->get(route('menu.promotions.percentage.create'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/promotions/percentage/create')
            ->has('products', 1)
            ->where('products.0.id', $product->id)
            ->has('products.0.variants', 2)
            ->where('products.0.variants.0.id', $variant1->id)
            ->where('products.0.variants.1.id', $variant2->id)
        );
    });

    test('percentage edit loads products with variants', function () {
        $promotion = Promotion::factory()->create(['type' => 'percentage_discount']);
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['size' => '15cm', 'sort_order' => 1]);
        ProductVariant::factory()->for($product)->create(['size' => '30cm', 'sort_order' => 2]);

        $response = $this->get(route('menu.promotions.edit', $promotion));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/promotions/percentage/edit')
            ->has('products', 1)
            ->has('products.0.variants', 2)
        );
    });

    test('percentage expands 1 item to multiple promotion_items', function () {
        $category = \App\Models\Menu\Category::factory()->create(['name' => 'Subs']);
        $product1 = Product::factory()->create(['category_id' => $category->id]);
        $product2 = Product::factory()->create(['category_id' => $category->id]);
        $product3 = Product::factory()->create(['category_id' => $category->id]);

        $variant = ProductVariant::factory()->for($product1)->create(['name' => 'Sub 15cm', 'size' => '15cm']);
        ProductVariant::factory()->for($product2)->create(['name' => 'Sub 15cm', 'size' => '15cm']);
        ProductVariant::factory()->for($product3)->create(['name' => 'Sub 15cm', 'size' => '15cm']);

        $promotionData = [
            'name' => 'Descuento Subs 15cm',
            'type' => 'percentage_discount',
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $product1->id,
                    'category_id' => $category->id,
                    'variant_id' => $variant->id,
                    'discount_percentage' => 15.00,
                    'service_type' => 'both',
                    'validity_type' => 'permanent',
                ],
                [
                    'product_id' => $product2->id,
                    'category_id' => $category->id,
                    'variant_id' => $variant->id,
                    'discount_percentage' => 15.00,
                    'service_type' => 'both',
                    'validity_type' => 'permanent',
                ],
                [
                    'product_id' => $product3->id,
                    'category_id' => $category->id,
                    'variant_id' => $variant->id,
                    'discount_percentage' => 15.00,
                    'service_type' => 'both',
                    'validity_type' => 'permanent',
                ],
            ],
        ];

        $response = $this->post(route('menu.promotions.store'), $promotionData);

        $response->assertRedirect();

        $promotion = Promotion::latest()->first();
        expect($promotion->items)->toHaveCount(3);

        foreach ($promotion->items as $item) {
            expect($item->category_id)->toBe($category->id);
            expect($item->variant_id)->toBe($variant->id);
            expect($item->discount_percentage)->toBe('15.00');
            expect($item->service_type)->toBe('both');
        }
    });

    test('percentage creates multiple items with different configurations', function () {
        $category1 = \App\Models\Menu\Category::factory()->create(['name' => 'Subs']);
        $category2 = \App\Models\Menu\Category::factory()->create(['name' => 'Bebidas']);

        $product1 = Product::factory()->create(['category_id' => $category1->id]);
        $product2 = Product::factory()->create(['category_id' => $category1->id]);
        $product3 = Product::factory()->create(['category_id' => $category2->id]);
        $product4 = Product::factory()->create(['category_id' => $category2->id]);
        $product5 = Product::factory()->create(['category_id' => $category2->id]);

        $variant1 = ProductVariant::factory()->for($product1)->create(['size' => '15cm']);
        ProductVariant::factory()->for($product2)->create(['size' => '15cm']);

        $promotionData = [
            'name' => 'Descuento Múltiple',
            'type' => 'percentage_discount',
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $product1->id,
                    'category_id' => $category1->id,
                    'variant_id' => $variant1->id,
                    'discount_percentage' => 15.00,
                    'service_type' => 'both',
                    'validity_type' => 'permanent',
                ],
                [
                    'product_id' => $product2->id,
                    'category_id' => $category1->id,
                    'variant_id' => $variant1->id,
                    'discount_percentage' => 15.00,
                    'service_type' => 'both',
                    'validity_type' => 'permanent',
                ],
                [
                    'product_id' => $product3->id,
                    'category_id' => $category2->id,
                    'variant_id' => null,
                    'discount_percentage' => 20.00,
                    'service_type' => 'delivery_only',
                    'validity_type' => 'permanent',
                ],
                [
                    'product_id' => $product4->id,
                    'category_id' => $category2->id,
                    'variant_id' => null,
                    'discount_percentage' => 20.00,
                    'service_type' => 'delivery_only',
                    'validity_type' => 'permanent',
                ],
                [
                    'product_id' => $product5->id,
                    'category_id' => $category2->id,
                    'variant_id' => null,
                    'discount_percentage' => 20.00,
                    'service_type' => 'delivery_only',
                    'validity_type' => 'permanent',
                ],
            ],
        ];

        $response = $this->post(route('menu.promotions.store'), $promotionData);

        $response->assertRedirect();

        $promotion = Promotion::latest()->first();
        expect($promotion->items)->toHaveCount(5);

        $subsItems = $promotion->items->where('category_id', $category1->id);
        expect($subsItems)->toHaveCount(2);
        foreach ($subsItems as $item) {
            expect($item->variant_id)->toBe($variant1->id);
            expect($item->discount_percentage)->toBe('15.00');
            expect($item->service_type)->toBe('both');
        }

        $bebidasItems = $promotion->items->where('category_id', $category2->id);
        expect($bebidasItems)->toHaveCount(3);
        foreach ($bebidasItems as $item) {
            expect($item->variant_id)->toBeNull();
            expect($item->discount_percentage)->toBe('20.00');
            expect($item->service_type)->toBe('delivery_only');
        }
    });

    test('percentage allows category without variants', function () {
        $category = \App\Models\Menu\Category::factory()->create(['name' => 'Postres']);
        $product = Product::factory()->create(['category_id' => $category->id, 'has_variants' => false]);

        $promotionData = [
            'name' => 'Descuento Postres',
            'type' => 'percentage_discount',
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'category_id' => $category->id,
                    'variant_id' => null,
                    'discount_percentage' => 10.00,
                    'service_type' => 'both',
                    'validity_type' => 'permanent',
                ],
            ],
        ];

        $response = $this->post(route('menu.promotions.store'), $promotionData);

        $response->assertRedirect();

        $this->assertDatabaseHas('promotion_items', [
            'product_id' => $product->id,
            'category_id' => $category->id,
            'variant_id' => null,
            'discount_percentage' => 10.00,
        ]);
    });
});

describe('Percentage Validation', function () {
    test('percentage validates maximum percentage 100', function () {
        $product = Product::factory()->create();

        $promotionData = [
            'name' => 'Descuento Inválido',
            'type' => 'percentage_discount',
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'variant_id' => null,
                    'discount_percentage' => 150.00,
                    'service_type' => 'both',
                    'validity_type' => 'permanent',
                ],
            ],
        ];

        $response = $this->post(route('menu.promotions.store'), $promotionData);

        $response->assertSessionHasErrors(['items.0.discount_percentage']);
    });

    test('percentage validates minimum percentage 1', function () {
        $product = Product::factory()->create();

        $promotionData = [
            'name' => 'Descuento Inválido',
            'type' => 'percentage_discount',
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'variant_id' => null,
                    'discount_percentage' => 0.5,
                    'service_type' => 'both',
                    'validity_type' => 'permanent',
                ],
            ],
        ];

        $response = $this->post(route('menu.promotions.store'), $promotionData);

        $response->assertSessionHasErrors(['items.0.discount_percentage']);
    });

    test('percentage validates duplicates of product_id and variant_id', function () {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $promotionData = [
            'name' => 'Promoción Duplicada',
            'type' => 'percentage_discount',
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'variant_id' => $variant->id,
                    'discount_percentage' => 15.00,
                    'service_type' => 'both',
                    'validity_type' => 'permanent',
                ],
                [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'variant_id' => $variant->id,
                    'discount_percentage' => 20.00,
                    'service_type' => 'both',
                    'validity_type' => 'permanent',
                ],
            ],
        ];

        $response = $this->post(route('menu.promotions.store'), $promotionData);

        $response->assertSessionHasErrors(['items.1.product_id']);
    });

    test('percentage validates category_id required', function () {
        $product = Product::factory()->create();

        $promotionData = [
            'name' => 'Sin Categoría',
            'type' => 'percentage_discount',
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'discount_percentage' => 15.00,
                    'service_type' => 'both',
                    'validity_type' => 'permanent',
                ],
            ],
        ];

        $response = $this->post(route('menu.promotions.store'), $promotionData);

        $response->assertSessionHasErrors(['items.0.category_id']);
    });
});

describe('General Promotion Operations', function () {
    test('can edit promotion', function () {
        $promotion = Promotion::factory()->create([
            'name' => 'Nombre Original',
            'type' => 'daily_special',
        ]);

        $product = Product::factory()->create();
        $promotion->items()->create([
            'product_id' => $product->id,
            'category_id' => $product->category_id,
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
                    'category_id' => $product->category_id,
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

    test('can update promotion with variant_id', function () {
        $promotion = Promotion::factory()->create(['type' => 'daily_special']);
        $product = Product::factory()->create();
        $oldVariant = ProductVariant::factory()->for($product)->create(['size' => '15cm']);
        $newVariant = ProductVariant::factory()->for($product)->create(['size' => '30cm']);

        $promotion->items()->create([
            'product_id' => $product->id,
            'category_id' => $product->category_id,
            'variant_id' => $oldVariant->id,
            'special_price_capital' => 30.00,
            'special_price_interior' => 25.00,
            'service_type' => 'both',
            'validity_type' => 'weekdays',
            'weekdays' => [1],
        ]);

        $response = $this->put(route('menu.promotions.update', $promotion), [
            'name' => 'Promoción Actualizada',
            'type' => 'daily_special',
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'variant_id' => $newVariant->id,
                    'special_price_capital' => 40.00,
                    'special_price_interior' => 35.00,
                    'service_type' => 'both',
                    'validity_type' => 'weekdays',
                    'weekdays' => [1, 2],
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('promotion_items', [
            'promotion_id' => $promotion->id,
            'product_id' => $product->id,
            'category_id' => $product->category_id,
            'variant_id' => $newVariant->id,
            'special_price_capital' => 40.00,
        ]);
    });

    test('can delete promotion', function () {
        $promotion = Promotion::factory()->create();

        $response = $this->delete(route('menu.promotions.destroy', $promotion));

        $response->assertRedirect();

        $this->assertSoftDeleted('promotions', [
            'id' => $promotion->id,
        ]);
    });

    test('can activate or deactivate promotion', function () {
        $promotion = Promotion::factory()->create(['is_active' => true]);

        $response = $this->post(route('menu.promotions.toggle', $promotion));

        $response->assertSuccessful();

        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'is_active' => false,
        ]);
    });

    test('validates promotion_items have weekdays when validity_type is weekdays', function () {
        $product = Product::factory()->create();

        $promotionData = [
            'name' => 'Promoción Sin Días',
            'type' => 'daily_special',
            'is_active' => true,
            'items' => [
                [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'special_price_capital' => 35.00,
                    'special_price_interior' => 30.00,
                    'service_type' => 'both',
                    'validity_type' => 'weekdays',
                    'weekdays' => [],
                ],
            ],
        ];

        $response = $this->post(route('menu.promotions.store'), $promotionData);

        $response->assertSessionHasErrors(['items.0.weekdays']);
    });
});
