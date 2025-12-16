<?php

use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\ComboItem;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('index', function () {
    test('returns complete menu with categories, combos and promotions', function () {
        $category = Category::factory()->create([
            'is_active' => true,
            'is_combo_category' => false,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        // Create combo with active product so it passes availability check
        $combo = Combo::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
            'is_choice_group' => false,
        ]);

        Promotion::factory()->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/menu');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'categories',
                    'combos',
                ],
            ]);
    });

    test('excludes combo categories from main menu', function () {
        Category::factory()->create([
            'name' => 'Regular Category',
            'is_active' => true,
            'is_combo_category' => false,
        ]);

        Category::factory()->create([
            'name' => 'Combo Category',
            'is_active' => true,
            'is_combo_category' => true,
        ]);

        $response = $this->getJson('/api/v1/menu');

        $response->assertOk();
        $categoryNames = collect($response->json('data.categories'))->pluck('name');

        expect($categoryNames)->toContain('Regular Category');
        expect($categoryNames)->not->toContain('Combo Category');
    });

    test('excludes inactive categories', function () {
        Category::factory()->create([
            'name' => 'Active Category',
            'is_active' => true,
            'is_combo_category' => false,
        ]);

        Category::factory()->create([
            'name' => 'Inactive Category',
            'is_active' => false,
            'is_combo_category' => false,
        ]);

        $response = $this->getJson('/api/v1/menu');

        $response->assertOk();
        $categoryNames = collect($response->json('data.categories'))->pluck('name');

        expect($categoryNames)->toContain('Active Category');
        expect($categoryNames)->not->toContain('Inactive Category');
    });

    test('loads products with variants and sections', function () {
        $category = Category::factory()->create([
            'is_active' => true,
            'is_combo_category' => false,
            'uses_variants' => true,
        ]);

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'has_variants' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/menu');

        $response->assertOk();
        $categories = $response->json('data.categories');

        expect($categories)->not->toBeEmpty();
    });

    test('only returns active combos with available products', function () {
        $category = Category::factory()->create(['is_active' => true]);
        $activeProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        $inactiveProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $activeCombo = Combo::factory()->create([
            'name' => 'Active Combo',
            'is_active' => true,
        ]);
        ComboItem::factory()->create([
            'combo_id' => $activeCombo->id,
            'product_id' => $activeProduct->id,
            'is_choice_group' => false,
        ]);

        $inactiveCombo = Combo::factory()->create([
            'name' => 'Inactive Combo',
            'is_active' => false,
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

        $response = $this->getJson('/api/v1/menu');

        $response->assertOk();
        $comboNames = collect($response->json('data.combos'))->pluck('name');

        expect($comboNames)->toContain('Active Combo');
        expect($comboNames)->not->toContain('Inactive Combo');
        expect($comboNames)->not->toContain('Unavailable Combo');
    });

    // Nota: Las promotions se obtienen desde /api/v1/menu/promotions
    // Este endpoint solo devuelve categories y combos

    test('orders categories by sort_order', function () {
        Category::factory()->create([
            'name' => 'Third',
            'is_active' => true,
            'is_combo_category' => false,
            'sort_order' => 3,
        ]);

        Category::factory()->create([
            'name' => 'First',
            'is_active' => true,
            'is_combo_category' => false,
            'sort_order' => 1,
        ]);

        Category::factory()->create([
            'name' => 'Second',
            'is_active' => true,
            'is_combo_category' => false,
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/v1/menu');

        $response->assertOk();
        $categories = $response->json('data.categories');

        expect($categories[0]['name'])->toBe('First');
        expect($categories[1]['name'])->toBe('Second');
        expect($categories[2]['name'])->toBe('Third');
    });
});
