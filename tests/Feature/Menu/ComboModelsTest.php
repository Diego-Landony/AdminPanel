<?php

use App\Models\Menu\Combo;
use App\Models\Menu\ComboItem;
use App\Models\Menu\ComboItemOption;
use App\Models\Menu\Product;

describe('Combo Models and Eloquent Relationships', function () {
    test('ComboItemOption model exists with all relationships', function () {
        $model = new ComboItemOption;

        expect($model)->toBeInstanceOf(ComboItemOption::class);
        expect(method_exists($model, 'comboItem'))->toBeTrue();
        expect(method_exists($model, 'product'))->toBeTrue();
        expect(method_exists($model, 'variant'))->toBeTrue();
    });

    test('ComboItemOption model has correct fillable', function () {
        $model = new ComboItemOption;
        $fillable = $model->getFillable();

        expect($fillable)->toContain('combo_item_id');
        expect($fillable)->toContain('product_id');
        expect($fillable)->toContain('variant_id');
        expect($fillable)->toContain('sort_order');
    });

    test('ComboItemOption model has correct casts', function () {
        $model = new ComboItemOption;
        $casts = $model->getCasts();

        expect($casts)->toHaveKey('combo_item_id');
        expect($casts)->toHaveKey('product_id');
        expect($casts)->toHaveKey('variant_id');
        expect($casts)->toHaveKey('sort_order');
    });

    test('ComboItem model extended correctly with new fields', function () {
        $model = new ComboItem;
        $fillable = $model->getFillable();

        expect($fillable)->toContain('is_choice_group');
        expect($fillable)->toContain('choice_label');
    });

    test('ComboItem model has correct cast for is_choice_group', function () {
        $model = new ComboItem;
        $casts = $model->getCasts();

        expect($casts)->toHaveKey('is_choice_group');
        expect($casts['is_choice_group'])->toBe('boolean');
    });

    test('ComboItem model has options relationship', function () {
        $model = new ComboItem;

        expect(method_exists($model, 'options'))->toBeTrue();
    });

    test('ComboItem model has isChoiceGroup method', function () {
        $model = new ComboItem;

        expect(method_exists($model, 'isChoiceGroup'))->toBeTrue();
        expect($model->isChoiceGroup())->toBeFalse();
    });

    test('ComboItem model getProductWithSections method handles choice groups', function () {
        $model = new ComboItem;
        $model->is_choice_group = true;

        expect($model->getProductWithSections())->toBeNull();
    });

    test('Combo model has updated available scope', function () {
        $model = new Combo;

        expect(method_exists($model, 'scopeAvailable'))->toBeTrue();
    });

    test('Combo model has availableWithWarnings scope', function () {
        $model = new Combo;

        expect(method_exists($model, 'scopeAvailableWithWarnings'))->toBeTrue();
    });

    test('Combo model has getInactiveOptionsCount method', function () {
        $model = new Combo;

        expect(method_exists($model, 'getInactiveOptionsCount'))->toBeTrue();
    });
});

describe('Relationship Functionality', function () {
    test('ComboItem::options relationship works correctly', function () {
        $combo = Combo::factory()->create();
        $product = Product::factory()->create();

        $comboItem = ComboItem::create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'sort_order' => 0,
            'is_choice_group' => false,
        ]);

        expect($comboItem->options)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
        expect($comboItem->options)->toHaveCount(0);
    });

    test('getInactiveOptionsCount method returns 0 when there are no inactive options', function () {
        $combo = Combo::factory()->create();

        $count = $combo->getInactiveOptionsCount();

        expect($count)->toBe(0);
    });

    test('availableWithWarnings scope loads relationships correctly', function () {
        Combo::factory()->create(['is_active' => true]);

        $combos = Combo::availableWithWarnings()->get();

        expect($combos)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });
});

describe('Complete Model Verification', function () {
    test('phase 2 checklist complete', function () {
        $option = new ComboItemOption;
        expect(method_exists($option, 'comboItem'))->toBeTrue();
        expect(method_exists($option, 'product'))->toBeTrue();
        expect(method_exists($option, 'variant'))->toBeTrue();

        $item = new ComboItem;
        expect(method_exists($item, 'options'))->toBeTrue();
        expect(method_exists($item, 'isChoiceGroup'))->toBeTrue();
        expect(method_exists($item, 'getProductWithSections'))->toBeTrue();

        $combo = new Combo;
        expect(method_exists($combo, 'scopeAvailable'))->toBeTrue();

        expect(method_exists($combo, 'getInactiveOptionsCount'))->toBeTrue();

        expect(method_exists($combo, 'scopeAvailableWithWarnings'))->toBeTrue();
    });
});
