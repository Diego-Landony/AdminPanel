<?php

use App\Models\Menu\BundlePromotionItem;
use App\Models\Menu\BundlePromotionItemOption;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Promotion;

test('puede crear opción con producto', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create(['has_variants' => false]);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    $option = BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'sort_order' => 1,
    ]);

    expect($option->product_id)->toBe($product->id);
    expect($option->variant_id)->toBeNull();
});

test('puede crear opción con variante', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create(['has_variants' => true]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige tamaño',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    $option = BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'sort_order' => 1,
    ]);

    expect($option->variant_id)->toBe($variant->id);
    expect($option->variant->id)->toBe($variant->id);
});

test('pertenece a un bundle item', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create();

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    $option = BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product->id,
        'sort_order' => 1,
    ]);

    expect($option->bundleItem->id)->toBe($item->id);
    expect($option->bundleItem->choice_label)->toBe('Elige');
});

test('tiene relación con producto', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create(['name' => 'Sub Teriyaki']);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    $option = BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product->id,
        'sort_order' => 1,
    ]);

    expect($option->product->name)->toBe('Sub Teriyaki');
});

test('tiene relación con variante', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create(['has_variants' => true]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'name' => '30cm',
    ]);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    $option = BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'sort_order' => 1,
    ]);

    expect($option->variant->name)->toBe('30cm');
});

test('se elimina en cascada cuando se elimina el bundle item', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create();

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    $option = BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product->id,
        'sort_order' => 1,
    ]);

    $optionId = $option->id;
    $item->delete();

    expect(BundlePromotionItemOption::find($optionId))->toBeNull();
});

test('respeta el orden sort_order', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product1 = Product::factory()->create(['name' => 'Producto A']);
    $product2 = Product::factory()->create(['name' => 'Producto B']);
    $product3 = Product::factory()->create(['name' => 'Producto C']);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product1->id,
        'sort_order' => 3,
    ]);

    BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product2->id,
        'sort_order' => 1,
    ]);

    BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product3->id,
        'sort_order' => 2,
    ]);

    $options = $item->options()->orderBy('sort_order')->get();

    expect($options[0]->product->name)->toBe('Producto B');
    expect($options[1]->product->name)->toBe('Producto C');
    expect($options[2]->product->name)->toBe('Producto A');
});

test('requiere product_id', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        // product_id no especificado, debería lanzar error
        'sort_order' => 1,
    ]);
});

test('puede tener múltiples opciones del mismo producto con diferentes variantes', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create(['has_variants' => true]);
    $variant1 = ProductVariant::factory()->create(['product_id' => $product->id, 'name' => '15cm']);
    $variant2 = ProductVariant::factory()->create(['product_id' => $product->id, 'name' => '30cm']);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige tamaño',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product->id,
        'variant_id' => $variant1->id,
        'sort_order' => 1,
    ]);

    BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product->id,
        'variant_id' => $variant2->id,
        'sort_order' => 2,
    ]);

    $options = $item->options;
    expect($options)->toHaveCount(2);
    expect($options[0]->variant->name)->toBe('15cm');
    expect($options[1]->variant->name)->toBe('30cm');
});

test('puede tener variantes de productos diferentes', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product1 = Product::factory()->create(['has_variants' => true]);
    $product2 = Product::factory()->create(['has_variants' => true]);
    $variant1 = ProductVariant::factory()->create(['product_id' => $product1->id]);
    $variant2 = ProductVariant::factory()->create(['product_id' => $product2->id]);

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product1->id,
        'variant_id' => $variant1->id,
        'sort_order' => 1,
    ]);

    BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product2->id,
        'variant_id' => $variant2->id,
        'sort_order' => 2,
    ]);

    expect($item->options)->toHaveCount(2);
});

test('puede establecer sort_order', function () {
    $promotion = Promotion::factory()->create(['type' => 'bundle_special']);
    $product = Product::factory()->create();

    $item = BundlePromotionItem::create([
        'promotion_id' => $promotion->id,
        'is_choice_group' => true,
        'choice_label' => 'Elige',
        'quantity' => 1,
        'sort_order' => 1,
    ]);

    $option = BundlePromotionItemOption::create([
        'bundle_item_id' => $item->id,
        'product_id' => $product->id,
        'sort_order' => 5,
    ]);

    expect($option->sort_order)->toBe(5);
});
