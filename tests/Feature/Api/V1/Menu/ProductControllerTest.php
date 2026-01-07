<?php

use App\Models\Menu\BadgeType;
use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductBadge;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Section;
use App\Models\Menu\SectionOption;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('index', function () {
    test('returns list of active products', function () {
        $category = Category::factory()->create(['is_active' => true]);
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/menu/products');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'products' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'image_url',
                            'category_id',
                            'has_variants',
                            'prices',
                        ],
                    ],
                ],
            ]);

        expect($response->json('data.products'))->toHaveCount(3);
    });

    test('filters products by category_id', function () {
        $category1 = Category::factory()->create(['is_active' => true]);
        $category2 = Category::factory()->create(['is_active' => true]);

        Product::factory()->count(2)->create([
            'category_id' => $category1->id,
            'is_active' => true,
        ]);
        Product::factory()->count(3)->create([
            'category_id' => $category2->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/menu/products?category_id={$category1->id}");

        $response->assertOk();
        expect($response->json('data.products'))->toHaveCount(2);
    });

    test('filters products by search term', function () {
        $category = Category::factory()->create(['is_active' => true]);

        Product::factory()->create([
            'name' => 'Submarine Sandwich',
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'name' => 'Pizza Slice',
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'name' => 'Italian Sub',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/menu/products?search=Sub');

        $response->assertOk();
        $productNames = collect($response->json('data.products'))->pluck('name');

        expect($productNames)->toContain('Submarine Sandwich');
        expect($productNames)->toContain('Italian Sub');
        expect($productNames)->not->toContain('Pizza Slice');
    });

    test('filters products by has_variants', function () {
        $category = Category::factory()->create(['is_active' => true]);

        Product::factory()->count(2)->create([
            'category_id' => $category->id,
            'is_active' => true,
            'has_variants' => true,
        ]);
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'is_active' => true,
            'has_variants' => false,
        ]);

        $response = $this->getJson('/api/v1/menu/products?has_variants=true');

        $response->assertOk();
        expect($response->json('data.products'))->toHaveCount(2);
    });

    test('includes variants for products', function () {
        $category = Category::factory()->create([
            'is_active' => true,
            'uses_variants' => true,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'has_variants' => true,
        ]);

        ProductVariant::factory()->count(2)->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/menu/products');

        $response->assertOk();
        $products = $response->json('data.products');

        expect($products[0]['variants'])->toHaveCount(2);
    });

    test('includes sections and options for products', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $section = Section::factory()->create();
        $product->sections()->attach($section->id);

        SectionOption::factory()->count(3)->create([
            'section_id' => $section->id,
        ]);

        $response = $this->getJson('/api/v1/menu/products');

        $response->assertOk();
        $products = $response->json('data.products');

        expect($products[0]['sections'])->not->toBeEmpty();
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

        $response = $this->getJson('/api/v1/menu/products');

        $response->assertOk();
        $products = $response->json('data.products');

        expect($products[0]['badges'])->not->toBeEmpty();
    });

    test('orders products by sort_order', function () {
        $category = Category::factory()->create(['is_active' => true]);

        Product::factory()->create([
            'name' => 'Third',
            'category_id' => $category->id,
            'is_active' => true,
            'sort_order' => 3,
        ]);
        Product::factory()->create([
            'name' => 'First',
            'category_id' => $category->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Product::factory()->create([
            'name' => 'Second',
            'category_id' => $category->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/v1/menu/products');

        $response->assertOk();
        $products = $response->json('data.products');

        expect($products[0]['name'])->toBe('First');
        expect($products[1]['name'])->toBe('Second');
        expect($products[2]['name'])->toBe('Third');
    });
});

describe('show', function () {
    test('returns product details', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/menu/products/{$product->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'product' => [
                        'id',
                        'name',
                        'description',
                        'image_url',
                        'category_id',
                        'has_variants',
                        'prices',
                        'variants',
                        'sections',
                        'badges',
                    ],
                ],
            ]);

        expect($response->json('data.product.id'))->toBe($product->id);
    });

    test('includes active variants only', function () {
        $category = Category::factory()->create([
            'is_active' => true,
            'uses_variants' => true,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'has_variants' => true,
        ]);

        ProductVariant::factory()->create([
            'name' => 'Active Variant',
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        ProductVariant::factory()->create([
            'name' => 'Inactive Variant',
            'product_id' => $product->id,
            'is_active' => false,
        ]);

        $response = $this->getJson("/api/v1/menu/products/{$product->id}");

        $response->assertOk();
        $variantNames = collect($response->json('data.product.variants'))->pluck('name');

        expect($variantNames)->toContain('Active Variant');
        expect($variantNames)->not->toContain('Inactive Variant');
    });

    test('returns 404 for inactive product', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $response = $this->getJson("/api/v1/menu/products/{$product->id}");

        $response->assertNotFound();
    });

    test('returns 404 for non-existent product', function () {
        $response = $this->getJson('/api/v1/menu/products/999999');

        $response->assertNotFound();
    });

    test('includes prices for all modalities', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 45.00,
            'precio_domicilio_interior' => 48.00,
        ]);

        $response = $this->getJson("/api/v1/menu/products/{$product->id}");

        $response->assertOk();
        $prices = $response->json('data.product.prices');

        expect((float) $prices['pickup_capital'])->toEqual(50.0);
        expect((float) $prices['delivery_capital'])->toEqual(55.0);
        expect((float) $prices['pickup_interior'])->toEqual(45.0);
        expect((float) $prices['delivery_interior'])->toEqual(48.0);
    });
});
