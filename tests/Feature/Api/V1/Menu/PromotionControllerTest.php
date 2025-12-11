<?php

use App\Models\Menu\BundlePromotionItem;
use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\Promotion;
use App\Models\Menu\PromotionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('index', function () {
    test('returns list of active promotions', function () {
        Promotion::factory()->count(3)->create(['is_active' => true]);
        Promotion::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/menu/promotions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'promotions' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'type',
                            'prices',
                        ],
                    ],
                ],
            ]);

        expect($response->json('data.promotions'))->toHaveCount(3);
    });

    test('excludes inactive promotions', function () {
        Promotion::factory()->create([
            'name' => 'Active Promotion',
            'is_active' => true,
        ]);

        Promotion::factory()->create([
            'name' => 'Inactive Promotion',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/menu/promotions');

        $response->assertOk();
        $promotionNames = collect($response->json('data.promotions'))->pluck('name');

        expect($promotionNames)->toContain('Active Promotion');
        expect($promotionNames)->not->toContain('Inactive Promotion');
    });

    test('includes promotion items with products', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $promotion = Promotion::factory()->create([
            'is_active' => true,
            'type' => 'daily_special',
        ]);

        PromotionItem::factory()->create([
            'promotion_id' => $promotion->id,
            'product_id' => $product->id,
        ]);

        $response = $this->getJson('/api/v1/menu/promotions');

        $response->assertOk();
        $promotions = $response->json('data.promotions');

        expect($promotions[0]['items'])->not->toBeEmpty();
    });

    test('includes bundle items for bundle special promotions', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $promotion = Promotion::factory()->create([
            'is_active' => true,
            'type' => 'bundle_special',
        ]);

        BundlePromotionItem::factory()->create([
            'promotion_id' => $promotion->id,
            'product_id' => $product->id,
        ]);

        $response = $this->getJson('/api/v1/menu/promotions');

        $response->assertOk();
        $promotions = $response->json('data.promotions');

        expect($promotions[0]['bundle_items'])->not->toBeEmpty();
    });

    test('orders promotions by sort_order', function () {
        Promotion::factory()->create([
            'name' => 'Third',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        Promotion::factory()->create([
            'name' => 'First',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Promotion::factory()->create([
            'name' => 'Second',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/v1/menu/promotions');

        $response->assertOk();
        $promotions = $response->json('data.promotions');

        expect($promotions[0]['name'])->toBe('First');
        expect($promotions[1]['name'])->toBe('Second');
        expect($promotions[2]['name'])->toBe('Third');
    });
});

describe('daily', function () {
    test('returns active daily special promotion', function () {
        Promotion::factory()->create([
            'name' => 'Sub del Día',
            'is_active' => true,
            'type' => 'daily_special',
        ]);

        $response = $this->getJson('/api/v1/menu/promotions/daily');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'promotion' => [
                        'id',
                        'name',
                        'description',
                        'type',
                        'prices',
                    ],
                ],
            ]);

        expect($response->json('data.promotion.name'))->toBe('Sub del Día');
    });

    test('returns 404 when no daily special is available', function () {
        Promotion::factory()->create([
            'is_active' => true,
            'type' => 'bundle_special',
        ]);

        $response = $this->getJson('/api/v1/menu/promotions/daily');

        $response->assertNotFound()
            ->assertJson([
                'message' => 'No hay Sub del Día disponible.',
            ]);
    });

    test('returns 404 when daily special is inactive', function () {
        Promotion::factory()->create([
            'is_active' => false,
            'type' => 'daily_special',
        ]);

        $response = $this->getJson('/api/v1/menu/promotions/daily');

        $response->assertNotFound();
    });

    test('includes items with products', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $promotion = Promotion::factory()->create([
            'is_active' => true,
            'type' => 'daily_special',
        ]);

        PromotionItem::factory()->create([
            'promotion_id' => $promotion->id,
            'product_id' => $product->id,
        ]);

        $response = $this->getJson('/api/v1/menu/promotions/daily');

        $response->assertOk();
        $items = $response->json('data.promotion.items');

        expect($items)->not->toBeEmpty();
        expect($items[0]['product'])->not->toBeNull();
    });
});

describe('combinados', function () {
    test('returns active bundle special promotions valid now', function () {
        Promotion::factory()->create([
            'name' => 'Combinado Test',
            'is_active' => true,
            'type' => 'bundle_special',
            'valid_from' => now()->subDay()->format('Y-m-d'),
            'valid_until' => now()->addDay()->format('Y-m-d'),
        ]);

        $response = $this->getJson('/api/v1/menu/promotions/combinados');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'promotions' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'type',
                            'prices',
                        ],
                    ],
                ],
            ]);

        expect(collect($response->json('data.promotions'))->pluck('name'))->toContain('Combinado Test');
    });

    test('excludes expired bundle specials', function () {
        Promotion::factory()->create([
            'name' => 'Active Combinado',
            'is_active' => true,
            'type' => 'bundle_special',
            'valid_from' => now()->subDay()->format('Y-m-d'),
            'valid_until' => now()->addDay()->format('Y-m-d'),
        ]);

        Promotion::factory()->create([
            'name' => 'Expired Combinado',
            'is_active' => true,
            'type' => 'bundle_special',
            'valid_from' => now()->subMonth()->format('Y-m-d'),
            'valid_until' => now()->subDay()->format('Y-m-d'),
        ]);

        $response = $this->getJson('/api/v1/menu/promotions/combinados');

        $response->assertOk();
        $promotionNames = collect($response->json('data.promotions'))->pluck('name');

        expect($promotionNames)->toContain('Active Combinado');
        expect($promotionNames)->not->toContain('Expired Combinado');
    });

    test('excludes daily specials from combinados endpoint', function () {
        Promotion::factory()->create([
            'name' => 'Bundle Promo',
            'is_active' => true,
            'type' => 'bundle_special',
            'valid_from' => now()->subDay()->format('Y-m-d'),
            'valid_until' => now()->addDay()->format('Y-m-d'),
        ]);

        Promotion::factory()->create([
            'name' => 'Daily Promo',
            'is_active' => true,
            'type' => 'daily_special',
        ]);

        $response = $this->getJson('/api/v1/menu/promotions/combinados');

        $response->assertOk();
        $promotionNames = collect($response->json('data.promotions'))->pluck('name');

        expect($promotionNames)->toContain('Bundle Promo');
        expect($promotionNames)->not->toContain('Daily Promo');
    });

    test('includes bundle items with options', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $promotion = Promotion::factory()->create([
            'is_active' => true,
            'type' => 'bundle_special',
            'valid_from' => now()->subDay()->format('Y-m-d'),
            'valid_until' => now()->addDay()->format('Y-m-d'),
        ]);

        BundlePromotionItem::factory()->create([
            'promotion_id' => $promotion->id,
            'product_id' => $product->id,
        ]);

        $response = $this->getJson('/api/v1/menu/promotions/combinados');

        $response->assertOk();
        $promotions = $response->json('data.promotions');

        expect($promotions[0]['bundle_items'])->not->toBeEmpty();
    });
});
