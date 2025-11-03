<?php

use App\Models\Menu\BundlePromotionItem;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Promotion;

test('puede crear item fijo con producto', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create(['has_variants' => false]);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => false,
        'product_id' => $product->id,
        'variant_id' => null,
        'quantity' => 2,
        'sort_order' => 1,
    ]);

    expect($item->is_choice_group)->toBeFalse();
    expect($item->product_id)->toBe($product->id);
    expect($item->quantity)->toBe(2);
});

test('puede crear item fijo con variante', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create(['has_variants' => true]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => false,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    expect($item->variant_id)->toBe($variant->id);
    expect($item->variant->id)->toBe($variant->id);
});

test('puede crear grupo de elección', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige tu bebida',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    expect($item->is_choice_group)->toBeTrue();
    expect($item->choice_label)->toBe('Elige tu bebida');
    expect($item->product_id)->toBeNull();
});

test('item fijo puede tener product_id null temporalmente', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => false,
        'product_id' => null,
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    expect($item->exists)->toBeTrue();
    expect($item->product_id)->toBeNull();
});

test('grupo de elección no requiere product_id', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    expect($item->exists)->toBeTrue();
    expect($item->product_id)->toBeNull();
});

test('pertenece a una promoción', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create();

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => false,
        'product_id' => $product->id,
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    expect($item->promotion->id)->toBe($promotion->id);
    expect($item->promotion->type)->toBe('bundle_special');
});

test('tiene relación con producto', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create(['name' => 'Sub Italiano']);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => false,
        'product_id' => $product->id,
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    expect($item->product->name)->toBe('Sub Italiano');
});

test('tiene relación con variante', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create(['has_variants' => true]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'name' => '15cm',
    ]);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => false,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    expect($item->variant->name)->toBe('15cm');
});

test('tiene relación con opciones', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    $item->options()->create([
        'product_id' => $product1->id,
        'sort_order' => 1,
    ]);

    $item->options()->create([
        'product_id' => $product2->id,
        'sort_order' => 2,
    ]);

    $item->refresh();
    expect($item->options)->toHaveCount(2);
});

test('se elimina en cascada cuando se elimina la promoción', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create();

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => false,
        'product_id' => $product->id,
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    $itemId = $item->id;
    $promotion->forceDelete(); // Force delete para evitar soft delete

    expect(BundlePromotionItem::find($itemId))->toBeNull();
});

test('respeta el orden sort_order', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create();

    BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => false,
        'product_id' => $product->id,
        'quantity' => 1,
        'sort_order' => 3,
    ]);

    BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => false,
        'product_id' => $product->id,
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => false,
        'product_id' => $product->id,
        'quantity' => 1,
        'sort_order' => 2,
    ]);

    $items = $promotion->bundleItems()->orderBy('sort_order')->get();

    expect($items[0]->sort_order)->toBe(1);
    expect($items[1]->sort_order)->toBe(2);
    expect($items[2]->sort_order)->toBe(3);
});

test('puede establecer quantity', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create();

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => false,
        'product_id' => $product->id,
        'quantity' => 3,
        'sort_order' => 1,
    ]);

    expect($item->quantity)->toBe(3);
});

test('casts is_choice_group a boolean', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create();

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => 0, // Guardado como integer
        'product_id' => $product->id,
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    expect($item->is_choice_group)->toBeBool();
    expect($item->is_choice_group)->toBeFalse();
});
