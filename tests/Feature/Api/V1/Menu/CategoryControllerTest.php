<?php

use App\Models\Menu\BadgeType;
use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductBadge;
use App\Models\Menu\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('index', function () {
    test('returns list of active categories', function () {
        Category::factory()->count(3)->create(['is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/menu/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'categories' => [
                        '*' => [
                            'id',
                            'name',
                            'uses_variants',
                            'is_combo_category',
                            'sort_order',
                        ],
                    ],
                ],
            ]);

        expect($response->json('data.categories'))->toHaveCount(3);
    });

    test('orders categories by sort_order', function () {
        Category::factory()->create(['name' => 'Zebra', 'is_active' => true, 'sort_order' => 3]);
        Category::factory()->create(['name' => 'Alpha', 'is_active' => true, 'sort_order' => 1]);
        Category::factory()->create(['name' => 'Beta', 'is_active' => true, 'sort_order' => 2]);

        $response = $this->getJson('/api/v1/menu/categories');

        $response->assertOk();
        $categories = $response->json('data.categories');

        expect($categories[0]['name'])->toBe('Alpha');
        expect($categories[1]['name'])->toBe('Beta');
        expect($categories[2]['name'])->toBe('Zebra');
    });

    test('excludes inactive categories', function () {
        Category::factory()->create(['name' => 'Active', 'is_active' => true]);
        Category::factory()->create(['name' => 'Inactive', 'is_active' => false]);

        $response = $this->getJson('/api/v1/menu/categories');

        $response->assertOk();
        $categoryNames = collect($response->json('data.categories'))->pluck('name');

        expect($categoryNames)->toContain('Active');
        expect($categoryNames)->not->toContain('Inactive');
    });
});

describe('show', function () {
    test('returns category with products', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/menu/categories/{$category->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'category' => [
                        'id',
                        'name',
                        'uses_variants',
                        'is_combo_category',
                        'sort_order',
                        'products',
                    ],
                ],
            ]);

        expect($response->json('data.category.id'))->toBe($category->id);
        expect($response->json('data.category.products'))->not->toBeEmpty();
    });

    test('includes active variants for products', function () {
        $category = Category::factory()->create([
            'is_active' => true,
            'uses_variants' => true,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'has_variants' => true,
        ]);

        $activeVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Active Variant',
            'is_active' => true,
        ]);

        $inactiveVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Inactive Variant',
            'is_active' => false,
        ]);

        $response = $this->getJson("/api/v1/menu/categories/{$category->id}");

        $response->assertOk();
        $products = $response->json('data.category.products');
        $variantNames = collect($products[0]['variants'] ?? [])->pluck('name');

        expect($variantNames)->toContain('Active Variant');
        expect($variantNames)->not->toContain('Inactive Variant');
    });

    test('includes badges for products', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $badgeType = BadgeType::factory()->create(['is_active' => true]);
        ProductBadge::factory()->create([
            'badge_type_id' => $badgeType->id,
            'badgeable_type' => Product::class,
            'badgeable_id' => $product->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/menu/categories/{$category->id}");

        $response->assertOk();
        $products = $response->json('data.category.products');

        expect($products[0]['badges'])->not->toBeEmpty();
    });

    test('returns 404 for inactive category', function () {
        $category = Category::factory()->create(['is_active' => false]);

        $response = $this->getJson("/api/v1/menu/categories/{$category->id}");

        $response->assertNotFound();
    });

    test('returns 404 for non-existent category', function () {
        $response = $this->getJson('/api/v1/menu/categories/999999');

        $response->assertNotFound();
    });

    test('excludes inactive products from category', function () {
        $category = Category::factory()->create(['is_active' => true]);

        Product::factory()->create([
            'name' => 'Active Product',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name' => 'Inactive Product',
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $response = $this->getJson("/api/v1/menu/categories/{$category->id}");

        $response->assertOk();
        $productNames = collect($response->json('data.category.products'))->pluck('name');

        expect($productNames)->toContain('Active Product');
        expect($productNames)->not->toContain('Inactive Product');
    });
});
