<?php

use App\Models\Menu\BadgeType;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\ComboItem;
use App\Models\Menu\ComboItemOption;
use App\Models\Menu\Product;
use App\Models\Menu\ProductBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('index', function () {
    test('returns list of active and available combos', function () {
        // Create combos with a product so they pass availability check
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        // Create 3 active combos with items
        for ($i = 0; $i < 3; $i++) {
            $combo = Combo::factory()->create(['is_active' => true]);
            ComboItem::factory()->create([
                'combo_id' => $combo->id,
                'product_id' => $product->id,
                'is_choice_group' => false,
            ]);
        }

        // Create inactive combo
        Combo::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/menu/combos');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'combos' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'image_url',
                            'prices',
                            'is_available',
                        ],
                    ],
                ],
            ]);

        expect($response->json('data.combos'))->toHaveCount(3);
    });

    test('excludes combos without active products', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $activeProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        $inactiveProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $availableCombo = Combo::factory()->create([
            'name' => 'Available Combo',
            'is_active' => true,
        ]);
        ComboItem::factory()->create([
            'combo_id' => $availableCombo->id,
            'product_id' => $activeProduct->id,
            'is_choice_group' => false,
        ]);

        $unavailableCombo = Combo::factory()->create([
            'name' => 'Unavailable Combo',
            'is_active' => true,
        ]);
        ComboItem::factory()->create([
            'combo_id' => $unavailableCombo->id,
            'product_id' => $inactiveProduct->id,
            'is_choice_group' => false,
        ]);

        $response = $this->getJson('/api/v1/menu/combos');

        $response->assertOk();
        $comboNames = collect($response->json('data.combos'))->pluck('name');

        expect($comboNames)->toContain('Available Combo');
        expect($comboNames)->not->toContain('Unavailable Combo');
    });

    test('includes combo items with products', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $combo = Combo::factory()->create(['is_active' => true]);
        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
            'is_choice_group' => false,
        ]);

        $response = $this->getJson('/api/v1/menu/combos');

        $response->assertOk();
        $combos = $response->json('data.combos');

        expect($combos[0]['items'])->not->toBeEmpty();
    });

    test('includes badges for combos', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $combo = Combo::factory()->create(['is_active' => true]);
        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
            'is_choice_group' => false,
        ]);

        $badgeType = BadgeType::factory()->create(['is_active' => true]);
        ProductBadge::factory()->create([
            'badge_type_id' => $badgeType->id,
            'badgeable_type' => Combo::class,
            'badgeable_id' => $combo->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/menu/combos');

        $response->assertOk();
        $combos = $response->json('data.combos');

        expect($combos[0]['badges'])->not->toBeEmpty();
    });

    test('orders combos by sort_order', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $third = Combo::factory()->create([
            'name' => 'Third',
            'is_active' => true,
            'sort_order' => 3,
        ]);
        ComboItem::factory()->create([
            'combo_id' => $third->id,
            'product_id' => $product->id,
            'is_choice_group' => false,
        ]);

        $first = Combo::factory()->create([
            'name' => 'First',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        ComboItem::factory()->create([
            'combo_id' => $first->id,
            'product_id' => $product->id,
            'is_choice_group' => false,
        ]);

        $second = Combo::factory()->create([
            'name' => 'Second',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        ComboItem::factory()->create([
            'combo_id' => $second->id,
            'product_id' => $product->id,
            'is_choice_group' => false,
        ]);

        $response = $this->getJson('/api/v1/menu/combos');

        $response->assertOk();
        $combos = $response->json('data.combos');

        expect($combos[0]['name'])->toBe('First');
        expect($combos[1]['name'])->toBe('Second');
        expect($combos[2]['name'])->toBe('Third');
    });
});

describe('show', function () {
    test('returns combo details', function () {
        $combo = Combo::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/v1/menu/combos/{$combo->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'combo' => [
                        'id',
                        'name',
                        'description',
                        'image_url',
                        'prices',
                        'is_available',
                        'items',
                        'badges',
                    ],
                ],
            ]);

        expect($response->json('data.combo.id'))->toBe($combo->id);
    });

    test('includes items with options', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        $optionProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $combo = Combo::factory()->create(['is_active' => true]);
        $item = ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
            'is_choice_group' => true,
        ]);

        ComboItemOption::factory()->create([
            'combo_item_id' => $item->id,
            'product_id' => $optionProduct->id,
        ]);

        $response = $this->getJson("/api/v1/menu/combos/{$combo->id}");

        $response->assertOk();
        $items = $response->json('data.combo.items');

        expect($items[0]['options'])->not->toBeEmpty();
    });

    test('returns 404 for inactive combo', function () {
        $combo = Combo::factory()->create(['is_active' => false]);

        $response = $this->getJson("/api/v1/menu/combos/{$combo->id}");

        $response->assertNotFound();
    });

    test('returns 404 for non-existent combo', function () {
        $response = $this->getJson('/api/v1/menu/combos/999999');

        $response->assertNotFound();
    });

    test('includes prices for all modalities', function () {
        $combo = Combo::factory()->create([
            'is_active' => true,
            'precio_pickup_capital' => 100.00,
            'precio_domicilio_capital' => 110.00,
            'precio_pickup_interior' => 95.00,
            'precio_domicilio_interior' => 105.00,
        ]);

        $response = $this->getJson("/api/v1/menu/combos/{$combo->id}");

        $response->assertOk();
        $prices = $response->json('data.combo.prices');

        expect((float) $prices['pickup_capital'])->toEqual(100.0);
        expect((float) $prices['delivery_capital'])->toEqual(110.0);
        expect((float) $prices['pickup_interior'])->toEqual(95.0);
        expect((float) $prices['delivery_interior'])->toEqual(105.0);
    });
});
