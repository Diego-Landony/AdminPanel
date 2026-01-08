<?php

use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Promotion;

beforeEach(function () {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Bundle Creation', function () {
    test('can create bundle with fixed items', function () {
        $product1 = Product::factory()->create(['has_variants' => false, 'is_active' => true]);
        $product2 = Product::factory()->create(['has_variants' => false, 'is_active' => true]);

        $data = [
            'name' => 'Combinado Test '.now()->timestamp,
            'description' => 'Oferta especial de navidad',
            'is_active' => true,
            'type' => 'bundle_special',
            'special_bundle_price_pickup_capital' => 45.00,
            'special_bundle_price_delivery_capital' => 48.00,
            'special_bundle_price_pickup_interior' => 48.00,
            'special_bundle_price_delivery_interior' => 51.00,
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

        $response->assertRedirect();

        expect(Promotion::count())->toBe($countBefore + 1);

        $promotion = Promotion::where('name', 'LIKE', 'Combinado Test%')->latest()->first();

        expect($promotion)->not->toBeNull();
        expect($promotion->type)->toBe('bundle_special');
        expect($promotion->bundleItems()->count())->toBe(2);
        expect((float) $promotion->special_bundle_price_pickup_capital)->toBe(45.00);
    });

    test('can create bundle with choice groups', function () {
        $product1 = Product::factory()->create(['has_variants' => false]);
        $product2 = Product::factory()->create(['has_variants' => false]);
        $product3 = Product::factory()->create(['has_variants' => false]);

        $data = [
            'name' => 'Combinado con Elección',
            'description' => 'Elige tu producto favorito',
            'is_active' => true,
            'special_bundle_price_pickup_capital' => 35.00,
            'special_bundle_price_delivery_capital' => 38.00,
            'special_bundle_price_pickup_interior' => 38.00,
            'special_bundle_price_delivery_interior' => 41.00,
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

    test('can create bundle with variants', function () {
        $product = Product::factory()->create(['has_variants' => true]);
        $variant1 = ProductVariant::factory()->create(['product_id' => $product->id]);
        $variant2 = ProductVariant::factory()->create(['product_id' => $product->id]);
        $product2 = Product::factory()->create(['has_variants' => false]);

        $data = [
            'name' => 'Combinado con Variantes',
            'is_active' => true,
            'special_bundle_price_pickup_capital' => 40.00,
            'special_bundle_price_delivery_capital' => 43.00,
            'special_bundle_price_pickup_interior' => 43.00,
            'special_bundle_price_delivery_interior' => 46.00,
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
});

describe('Validity Validation', function () {
    test('validates bundle validity rules', function (array $invalidData) {
        $product = Product::factory()->create();
        $baseData = [
            'name' => 'Test Bundle',
            'is_active' => true,
            'special_bundle_price_pickup_capital' => 45.00,
            'special_bundle_price_delivery_capital' => 48.00,
            'special_bundle_price_pickup_interior' => 48.00,
            'special_bundle_price_delivery_interior' => 51.00,
            'items' => [
                ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
                ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
            ],
        ];

        $response = $this->post(route('menu.promotions.bundle-specials.store'), array_merge($baseData, $invalidData));
        $response->assertSessionHasErrors();
    })->with([
        'valid_until before valid_from' => [
            ['valid_from' => '2024-12-25', 'valid_until' => '2024-12-01'],
        ],
        'time_until before time_from' => [
            ['time_from' => '18:00', 'time_until' => '12:00'],
        ],
        'weekdays out of range' => [
            ['weekdays' => [0, 8, 10]],
        ],
    ]);

    test('accepts valid weekdays from 1 to 7', function () {
        $product = Product::factory()->create();

        $data = [
            'name' => 'Combinado Weekdays Válidos',
            'is_active' => true,
            'special_bundle_price_pickup_capital' => 45.00,
            'special_bundle_price_delivery_capital' => 48.00,
            'special_bundle_price_pickup_interior' => 48.00,
            'special_bundle_price_delivery_interior' => 51.00,
            'validity_type' => 'permanent',
            'weekdays' => [1, 2, 3, 4, 5],
            'items' => [
                ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
                ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
            ],
        ];

        $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('promotions', ['name' => 'Combinado Weekdays Válidos']);
    });

    test('accepts date range validity', function () {
        $product = Product::factory()->create();

        $validFrom = now()->addDays(10)->format('Y-m-d');
        $validUntil = now()->addDays(35)->format('Y-m-d');

        $data = [
            'name' => 'Combinado Solo Fechas',
            'is_active' => true,
            'special_bundle_price_pickup_capital' => 45.00,
            'special_bundle_price_delivery_capital' => 48.00,
            'special_bundle_price_pickup_interior' => 48.00,
            'special_bundle_price_delivery_interior' => 51.00,
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

    test('accepts time range validity', function () {
        $product = Product::factory()->create();

        $data = [
            'name' => 'Combinado Solo Horarios',
            'is_active' => true,
            'special_bundle_price_pickup_capital' => 45.00,
            'special_bundle_price_delivery_capital' => 48.00,
            'special_bundle_price_pickup_interior' => 48.00,
            'special_bundle_price_delivery_interior' => 51.00,
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

    test('accepts null validity for always valid', function () {
        $product = Product::factory()->create();

        $data = [
            'name' => 'Combinado Siempre Válido',
            'is_active' => true,
            'special_bundle_price_pickup_capital' => 45.00,
            'special_bundle_price_delivery_capital' => 48.00,
            'special_bundle_price_pickup_interior' => 48.00,
            'special_bundle_price_delivery_interior' => 51.00,
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

    test('accepts weekdays only validity', function () {
        $product = Product::factory()->create();

        $data = [
            'name' => 'Combinado Solo Días',
            'is_active' => true,
            'special_bundle_price_pickup_capital' => 45.00,
            'special_bundle_price_delivery_capital' => 48.00,
            'special_bundle_price_pickup_interior' => 48.00,
            'special_bundle_price_delivery_interior' => 51.00,
            'validity_type' => 'permanent',
            'weekdays' => [6, 7],
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
});

describe('Bundle Updates', function () {
    test('can update bundle and change items', function () {
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
            'special_bundle_price_pickup_capital' => 50.00,
            'special_bundle_price_delivery_capital' => 53.00,
            'special_bundle_price_pickup_interior' => 53.00,
            'special_bundle_price_delivery_interior' => 56.00,
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

    test('can change temporary validity when updating', function () {
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
            'special_bundle_price_pickup_capital' => 45.00,
            'special_bundle_price_delivery_capital' => 48.00,
            'special_bundle_price_pickup_interior' => 48.00,
            'special_bundle_price_delivery_interior' => 51.00,
            'validity_type' => 'date_time_range',
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'time_from' => '17:00',
            'time_until' => '23:00',
            'weekdays' => [6, 7],
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
});

describe('Bundle Deletion', function () {
    test('can delete bundle with soft delete', function () {
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
});

describe('Item Validation', function () {
    test('validates bundle items and prices', function (callable $dataProvider) {
        $product = Product::factory()->create();
        $data = $dataProvider($product);

        $response = $this->post(route('menu.promotions.bundle-specials.store'), $data);
        $response->assertSessionHasErrors();
    })->with([
        'less than 2 items' => [
            fn ($product) => [
                'name' => 'Combinado con 1 Item',
                'is_active' => true,
                'special_bundle_price_capital' => 45.00,
                'special_bundle_price_interior' => 48.00,
                'items' => [
                    ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
                ],
            ],
        ],
        'choice group with less than 2 options' => [
            fn ($product) => [
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
            ],
        ],
        'negative prices' => [
            fn ($product) => [
                'name' => 'Combinado Precio Negativo',
                'is_active' => true,
                'special_bundle_price_pickup_capital' => -10.00,
                'special_bundle_price_delivery_capital' => 48.00,
                'special_bundle_price_pickup_interior' => 48.00,
                'special_bundle_price_delivery_interior' => 51.00,
                'items' => [
                    ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
                    ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
                ],
            ],
        ],
        'zero price' => [
            fn ($product) => [
                'name' => 'Combinado Precio Cero',
                'is_active' => true,
                'special_bundle_price_pickup_capital' => 45.00,
                'special_bundle_price_delivery_capital' => 48.00,
                'special_bundle_price_pickup_interior' => 0,
                'special_bundle_price_delivery_interior' => 51.00,
                'items' => [
                    ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 1],
                    ['is_choice_group' => false, 'product_id' => $product->id, 'quantity' => 1, 'sort_order' => 2],
                ],
            ],
        ],
    ]);
});

describe('Index and Stats', function () {
    test('index shows only bundles', function () {
        Promotion::factory()->count(3)->create(['type' => 'bundle_special']);
        Promotion::factory()->count(2)->create(['type' => 'daily_special']);

        $response = $this->get(route('menu.promotions.bundle-specials.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/promotions/bundle-specials/index')
            ->has('combinados', 3)
        );
    });

    test('stats calculate correct values', function () {
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
});
