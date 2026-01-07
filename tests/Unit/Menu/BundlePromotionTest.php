<?php

use App\Models\Menu\BundlePromotionItem;
use App\Models\Menu\BundlePromotionItemOption;
use App\Models\Menu\Product;
use App\Models\Menu\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('BundlePromotionItem', function () {
    test('can create fixed item with product', function () {
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

    test('belongs to promotion', function () {
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
});

describe('BundlePromotionItemOption', function () {
    test('can create option with product', function () {
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

    test('belongs to bundle item', function () {
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
});

describe('Cascade Deletion', function () {
    test('deletes items and options when promotion is deleted', function () {
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

        $itemId = $item->id;
        $optionId = $option->id;
        $promotion->forceDelete();

        expect(BundlePromotionItem::find($itemId))->toBeNull();
        expect(BundlePromotionItemOption::find($optionId))->toBeNull();
    });
});
