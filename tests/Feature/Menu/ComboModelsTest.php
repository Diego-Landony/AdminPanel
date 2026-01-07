<?php

use App\Models\Menu\Combo;
use App\Models\Menu\ComboItem;
use App\Models\Menu\Product;

describe('Combo Models and Eloquent Relationships', function () {
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
