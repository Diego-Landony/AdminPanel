<?php

use App\Models\Menu\Combo;
use App\Models\Menu\ComboItem;
use App\Models\Menu\ComboItemOption;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('combo item option pertenece a combo item', function () {
    $combo = Combo::factory()->create();
    $item = ComboItem::create([
        'combo_id' => $combo->id,
        'is_choice_group' => true,
        'choice_label' => 'Test Group',
        'quantity' => 1,
        'sort_order' => 0,
    ]);

    $product = Product::factory()->create(['is_active' => true]);

    $option = ComboItemOption::create([
        'combo_item_id' => $item->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'sort_order' => 0,
    ]);

    expect($option->comboItem)->toBeInstanceOf(ComboItem::class);
    expect($option->comboItem->id)->toBe($item->id);
});

test('combo item option pertenece a product', function () {
    $combo = Combo::factory()->create();
    $item = ComboItem::create([
        'combo_id' => $combo->id,
        'is_choice_group' => true,
        'choice_label' => 'Test Group',
        'quantity' => 1,
        'sort_order' => 0,
    ]);

    $product = Product::factory()->create(['is_active' => true]);

    $option = ComboItemOption::create([
        'combo_item_id' => $item->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'sort_order' => 0,
    ]);

    expect($option->product)->toBeInstanceOf(Product::class);
    expect($option->product->id)->toBe($product->id);
});

test('combo item option puede pertenecer a variant', function () {
    $combo = Combo::factory()->create();
    $item = ComboItem::create([
        'combo_id' => $combo->id,
        'is_choice_group' => true,
        'choice_label' => 'Test Group',
        'quantity' => 1,
        'sort_order' => 0,
    ]);

    $product = Product::factory()->create(['has_variants' => true, 'is_active' => true]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'name' => '15cm',
        'size' => '15cm',
    ]);

    $option = ComboItemOption::create([
        'combo_item_id' => $item->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'sort_order' => 0,
    ]);

    expect($option->variant)->toBeInstanceOf(ProductVariant::class);
    expect($option->variant->id)->toBe($variant->id);
});

test('opciones se eliminan en cascada cuando se elimina combo item', function () {
    $combo = Combo::factory()->create();
    $item = ComboItem::create([
        'combo_id' => $combo->id,
        'is_choice_group' => true,
        'choice_label' => 'Test Group',
        'quantity' => 1,
        'sort_order' => 0,
    ]);

    $product1 = Product::factory()->create(['is_active' => true]);
    $product2 = Product::factory()->create(['is_active' => true]);

    ComboItemOption::create([
        'combo_item_id' => $item->id,
        'product_id' => $product1->id,
        'variant_id' => null,
        'sort_order' => 0,
    ]);

    ComboItemOption::create([
        'combo_item_id' => $item->id,
        'product_id' => $product2->id,
        'variant_id' => null,
        'sort_order' => 1,
    ]);

    expect(ComboItemOption::count())->toBe(2);

    $item->delete();

    expect(ComboItemOption::count())->toBe(0);
});

test('combo item option tiene sort_order correcto', function () {
    $combo = Combo::factory()->create();
    $item = ComboItem::create([
        'combo_id' => $combo->id,
        'is_choice_group' => true,
        'choice_label' => 'Test Group',
        'quantity' => 1,
        'sort_order' => 0,
    ]);

    $product = Product::factory()->create(['is_active' => true]);

    $option = ComboItemOption::create([
        'combo_item_id' => $item->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'sort_order' => 5,
    ]);

    expect($option->sort_order)->toBe(5);
});

test('puede tener múltiples opciones en un combo item', function () {
    $combo = Combo::factory()->create();
    $item = ComboItem::create([
        'combo_id' => $combo->id,
        'is_choice_group' => true,
        'choice_label' => 'Test Group',
        'quantity' => 1,
        'sort_order' => 0,
    ]);

    $products = Product::factory()->count(5)->create(['is_active' => true]);

    foreach ($products as $index => $product) {
        ComboItemOption::create([
            'combo_item_id' => $item->id,
            'product_id' => $product->id,
            'variant_id' => null,
            'sort_order' => $index,
        ]);
    }

    expect($item->options)->toHaveCount(5);
    expect($item->options->pluck('product_id')->toArray())->toBe($products->pluck('id')->toArray());
});
