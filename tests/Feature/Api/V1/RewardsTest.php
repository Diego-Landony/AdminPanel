<?php

use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;

beforeEach(function () {
    $this->category = Category::factory()->create();
});

it('returns redeemable products', function () {
    // Create redeemable product
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
        ->assertJsonCount(0, 'data.combos')
        ->assertJsonPath('data.products.0.id', $redeemable->id)
        ->assertJsonPath('data.products.0.points_cost', 50)
        ->assertJsonPath('data.products.0.is_redeemable', true);
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

    $response = $this->getJson('/api/v1/rewards');

    $response->assertSuccessful()
        ->assertJsonPath('data.total_count', 0)
        ->assertJsonCount(0, 'data.products')
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

it('returns both products and combos together', function () {
    $comboCategory = Category::factory()->create(['is_combo_category' => true]);

    Product::factory()->create([
        'category_id' => $this->category->id,
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 50,
    ]);

    Product::factory()->create([
        'category_id' => $this->category->id,
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 75,
    ]);

    Combo::factory()->create([
        'category_id' => $comboCategory->id,
        'is_active' => true,
        'is_redeemable' => true,
        'points_cost' => 150,
    ]);

    $response = $this->getJson('/api/v1/rewards');

    $response->assertSuccessful()
        ->assertJsonPath('data.total_count', 3)
        ->assertJsonCount(2, 'data.products')
        ->assertJsonCount(1, 'data.combos');
});
