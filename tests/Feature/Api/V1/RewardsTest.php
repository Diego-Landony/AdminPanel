<?php

use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;

beforeEach(function () {
    $this->category = Category::factory()->create(['uses_variants' => false]);
    $this->variantCategory = Category::factory()->create([
        'uses_variants' => true,
        'variant_definitions' => ['6"', '12"'],
    ]);
});

it('returns redeemable products without variants', function () {
    // Create redeemable product without variants
    $redeemable = Product::factory()->create([
        'category_id' => $this->category->id,
        'name' => 'Cookie Gratis',
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 50,
    ]);

    // Create non-redeemable product
    Product::factory()->create([
        'category_id' => $this->category->id,
        'name' => 'Sub Normal',
        'is_active' => true,
        'is_redeemable' => false,
        'points_cost' => null,
    ]);

    $response = $this->getJson('/api/v1/rewards');

    $response->assertSuccessful()
        ->assertJsonPath('data.total_count', 1)
        ->assertJsonCount(1, 'data.products')
        ->assertJsonCount(0, 'data.variants')
        ->assertJsonCount(0, 'data.combos')
        ->assertJsonPath('data.products.0.id', $redeemable->id)
        ->assertJsonPath('data.products.0.points_cost', 50)
        ->assertJsonPath('data.products.0.is_redeemable', true);
});

it('returns redeemable variants for products with variants', function () {
    // Create product with variants
    $product = Product::factory()->create([
        'category_id' => $this->variantCategory->id,
        'name' => 'Italian B.M.T.',
        'is_active' => true,
        'is_redeemable' => false, // Product level should be false when using variants
        'points_cost' => null,
    ]);

    // Create redeemable variant (6")
    $variant6 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'name' => '6"',
        'size' => 'sixinch',
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 100,
    ]);

    // Create redeemable variant (12")
    $variant12 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'name' => '12"',
        'size' => 'footlong',
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 185,
    ]);

    $response = $this->getJson('/api/v1/rewards');

    $response->assertSuccessful()
        ->assertJsonPath('data.total_count', 2)
        ->assertJsonCount(0, 'data.products') // Product itself shouldn't appear
        ->assertJsonCount(2, 'data.variants')
        ->assertJsonPath('data.variants.0.id', $variant6->id) // Ordered by points_cost
        ->assertJsonPath('data.variants.0.points_cost', 100)
        ->assertJsonPath('data.variants.0.product_name', 'Italian B.M.T.')
        ->assertJsonPath('data.variants.0.type', 'variant')
        ->assertJsonPath('data.variants.1.id', $variant12->id)
        ->assertJsonPath('data.variants.1.points_cost', 185);
});

it('returns redeemable combos', function () {
    $comboCategory = Category::factory()->create(['is_combo_category' => true]);

    // Create redeemable combo
    $redeemable = Combo::factory()->create([
        'category_id' => $comboCategory->id,
        'name' => 'Combo Recompensa',
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 150,
    ]);

    // Create non-redeemable combo
    Combo::factory()->create([
        'category_id' => $comboCategory->id,
        'name' => 'Combo Normal',
        'is_active' => true,
        'is_redeemable' => false,
        'points_cost' => null,
    ]);

    $response = $this->getJson('/api/v1/rewards');

    $response->assertSuccessful()
        ->assertJsonPath('data.total_count', 1)
        ->assertJsonCount(0, 'data.products')
        ->assertJsonCount(0, 'data.variants')
        ->assertJsonCount(1, 'data.combos')
        ->assertJsonPath('data.combos.0.id', $redeemable->id)
        ->assertJsonPath('data.combos.0.points_cost', 150);
});

it('excludes inactive redeemable items', function () {
    // Create inactive redeemable product
    Product::factory()->create([
        'category_id' => $this->category->id,
        'is_active' => false,
        'is_redeemable' => true,
        'points_cost' => 50,
    ]);

    // Create inactive redeemable variant
    $product = Product::factory()->create([
        'category_id' => $this->variantCategory->id,
        'is_active' => true,
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_active' => false,
        'is_redeemable' => true,
        'points_cost' => 100,
    ]);

    $response = $this->getJson('/api/v1/rewards');

    $response->assertSuccessful()
        ->assertJsonPath('data.total_count', 0)
        ->assertJsonCount(0, 'data.products')
        ->assertJsonCount(0, 'data.variants')
        ->assertJsonCount(0, 'data.combos');
});

it('excludes items with zero or null points_cost', function () {
    // Create redeemable but with zero points
    Product::factory()->create([
        'category_id' => $this->category->id,
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 0,
    ]);

    // Create redeemable but with null points
    Product::factory()->create([
        'category_id' => $this->category->id,
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => null,
    ]);

    $response = $this->getJson('/api/v1/rewards');

    $response->assertSuccessful()
        ->assertJsonPath('data.total_count', 0);
});

it('orders items by points_cost ascending', function () {
    Product::factory()->create([
        'category_id' => $this->category->id,
        'name' => 'Expensive',
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 200,
    ]);

    Product::factory()->create([
        'category_id' => $this->category->id,
        'name' => 'Cheap',
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 50,
    ]);

    Product::factory()->create([
        'category_id' => $this->category->id,
        'name' => 'Medium',
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 100,
    ]);

    $response = $this->getJson('/api/v1/rewards');

    $response->assertSuccessful()
        ->assertJsonPath('data.products.0.points_cost', 50)
        ->assertJsonPath('data.products.1.points_cost', 100)
        ->assertJsonPath('data.products.2.points_cost', 200);
});

it('is publicly accessible without authentication', function () {
    $response = $this->getJson('/api/v1/rewards');

    $response->assertSuccessful();
});

it('returns products, variants, and combos together', function () {
    $comboCategory = Category::factory()->create(['is_combo_category' => true]);

    // Product without variants
    Product::factory()->create([
        'category_id' => $this->category->id,
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 50,
    ]);

    // Product with redeemable variants
    $productWithVariants = Product::factory()->create([
        'category_id' => $this->variantCategory->id,
        'is_active' => true,
        'is_redeemable' => false,
        'points_cost' => null,
    ]);

    ProductVariant::factory()->create([
        'product_id' => $productWithVariants->id,
        'name' => '6"',
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 100,
    ]);

    // Combo
    Combo::factory()->create([
        'category_id' => $comboCategory->id,
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 150,
    ]);

    $response = $this->getJson('/api/v1/rewards');

    $response->assertSuccessful()
        ->assertJsonPath('data.total_count', 3)
        ->assertJsonCount(1, 'data.products')
        ->assertJsonCount(1, 'data.variants')
        ->assertJsonCount(1, 'data.combos');
});

it('excludes variants when product is inactive', function () {
    // Create inactive product with active redeemable variant
    $product = Product::factory()->create([
        'category_id' => $this->variantCategory->id,
        'is_active' => false, // Product is inactive
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 100,
    ]);

    $response = $this->getJson('/api/v1/rewards');

    $response->assertSuccessful()
        ->assertJsonPath('data.total_count', 0)
        ->assertJsonCount(0, 'data.variants');
});

it('does not include product with variants in products array', function () {
    // Create product with active variants
    $product = Product::factory()->create([
        'category_id' => $this->variantCategory->id,
        'name' => 'Italian B.M.T.',
        'is_active' => true,
        'is_redeemable' => true, // Even if set to true at product level
        'points_cost' => 100,
    ]);

    // Add active variant
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_active' => true,
        'is_redeemable' => false,
        'points_cost' => null,
    ]);

    $response = $this->getJson('/api/v1/rewards');

    // Product should not appear in products array because it has active variants
    $response->assertSuccessful()
        ->assertJsonCount(0, 'data.products');
});
