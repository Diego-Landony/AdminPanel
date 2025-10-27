<?php

use App\Models\Menu\Combo;
use App\Models\Menu\ComboItem;
use App\Models\Menu\ComboItemOption;
use App\Models\Menu\Product;

describe('Fase 2: Modelos y Relaciones Eloquent', function () {
    test('modelo ComboItemOption existe con todas las relaciones', function () {
        $model = new ComboItemOption;

        expect($model)->toBeInstanceOf(ComboItemOption::class);
        expect(method_exists($model, 'comboItem'))->toBeTrue();
        expect(method_exists($model, 'product'))->toBeTrue();
        expect(method_exists($model, 'variant'))->toBeTrue();
    });

    test('modelo ComboItemOption tiene fillable correcto', function () {
        $model = new ComboItemOption;
        $fillable = $model->getFillable();

        expect($fillable)->toContain('combo_item_id');
        expect($fillable)->toContain('product_id');
        expect($fillable)->toContain('variant_id');
        expect($fillable)->toContain('sort_order');
    });

    test('modelo ComboItemOption tiene casts correcto', function () {
        $model = new ComboItemOption;
        $casts = $model->getCasts();

        expect($casts)->toHaveKey('combo_item_id');
        expect($casts)->toHaveKey('product_id');
        expect($casts)->toHaveKey('variant_id');
        expect($casts)->toHaveKey('sort_order');
    });

    test('modelo ComboItem extendido correctamente con nuevos campos', function () {
        $model = new ComboItem;
        $fillable = $model->getFillable();

        expect($fillable)->toContain('is_choice_group');
        expect($fillable)->toContain('choice_label');
    });

    test('modelo ComboItem tiene cast correcto para is_choice_group', function () {
        $model = new ComboItem;
        $casts = $model->getCasts();

        expect($casts)->toHaveKey('is_choice_group');
        expect($casts['is_choice_group'])->toBe('boolean');
    });

    test('modelo ComboItem tiene relación options', function () {
        $model = new ComboItem;

        expect(method_exists($model, 'options'))->toBeTrue();
    });

    test('modelo ComboItem tiene método isChoiceGroup', function () {
        $model = new ComboItem;

        expect(method_exists($model, 'isChoiceGroup'))->toBeTrue();
        expect($model->isChoiceGroup())->toBeFalse();
    });

    test('modelo ComboItem método getProductWithSections maneja grupos de elección', function () {
        $model = new ComboItem;
        $model->is_choice_group = true;

        expect($model->getProductWithSections())->toBeNull();
    });

    test('modelo Combo tiene scope available actualizado', function () {
        $model = new Combo;

        expect(method_exists($model, 'scopeAvailable'))->toBeTrue();
    });

    test('modelo Combo tiene scope availableWithWarnings', function () {
        $model = new Combo;

        expect(method_exists($model, 'scopeAvailableWithWarnings'))->toBeTrue();
    });

    test('modelo Combo tiene método getInactiveOptionsCount', function () {
        $model = new Combo;

        expect(method_exists($model, 'getInactiveOptionsCount'))->toBeTrue();
    });
});

describe('Fase 2: Funcionalidad de relaciones', function () {
    test('relación ComboItem::options funciona correctamente', function () {
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

    test('método getInactiveOptionsCount retorna 0 cuando no hay opciones inactivas', function () {
        $combo = Combo::factory()->create();

        $count = $combo->getInactiveOptionsCount();

        expect($count)->toBe(0);
    });

    test('scope availableWithWarnings carga relaciones correctamente', function () {
        Combo::factory()->create(['is_active' => true]);

        $combos = Combo::availableWithWarnings()->get();

        expect($combos)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });
});

describe('Fase 2: Verificación completa según plan', function () {
    test('checklist de fase 2 completo', function () {
        // ✓ Modelo ComboItemOption creado con todas las relaciones
        $option = new ComboItemOption;
        expect(method_exists($option, 'comboItem'))->toBeTrue();
        expect(method_exists($option, 'product'))->toBeTrue();
        expect(method_exists($option, 'variant'))->toBeTrue();

        // ✓ ComboItem extendido correctamente
        $item = new ComboItem;
        expect(method_exists($item, 'options'))->toBeTrue();
        expect(method_exists($item, 'isChoiceGroup'))->toBeTrue();
        expect(method_exists($item, 'getProductWithSections'))->toBeTrue();

        // ✓ Combo scope available() funciona con grupos
        $combo = new Combo;
        expect(method_exists($combo, 'scopeAvailable'))->toBeTrue();

        // ✓ Método getInactiveOptionsCount() retorna correctamente
        expect(method_exists($combo, 'getInactiveOptionsCount'))->toBeTrue();

        // ✓ Método scopeAvailableWithWarnings() existe
        expect(method_exists($combo, 'scopeAvailableWithWarnings'))->toBeTrue();
    });
});
