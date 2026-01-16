<?php

use App\Models\Menu\Section;
use App\Models\Menu\SectionOption;

describe('Section Bundle Pricing', function () {
    it('applies bundle discount to extras of same price', function () {
        // Crear sección con bundle pricing: descuento Q5 por cada 2 del mismo precio
        $section = Section::factory()->withBundlePricing(5.00, 2)->create();

        // Crear 4 opciones con precio Q11 cada una
        $options = [];
        for ($i = 0; $i < 4; $i++) {
            $options[] = SectionOption::factory()->create([
                'section_id' => $section->id,
                'is_extra' => true,
                'price_modifier' => 11.00,
            ]);
        }

        // 2 extras del mismo precio → Q22 - Q5 = Q17
        $result2 = $section->calculateOptionsPrice([$options[0]->id, $options[1]->id]);
        expect($result2['total'])->toBe(17.00);
        expect($result2['savings'])->toBe(5.00);

        // 3 extras → Q33, 1 bundle → Q33 - Q5 = Q28
        $result3 = $section->calculateOptionsPrice([
            $options[0]->id, $options[1]->id, $options[2]->id,
        ]);
        expect($result3['total'])->toBe(28.00);
        expect($result3['savings'])->toBe(5.00);

        // 4 extras → Q44, 2 bundles → Q44 - Q10 = Q34
        $result4 = $section->calculateOptionsPrice(collect($options)->pluck('id')->toArray());
        expect($result4['total'])->toBe(34.00);
        expect($result4['savings'])->toBe(10.00);
    });

    it('only applies bundle to extras of same price', function () {
        $section = Section::factory()->withBundlePricing(5.00, 2)->create();

        // Doble carne Q16
        $option1 = SectionOption::factory()->create([
            'section_id' => $section->id,
            'is_extra' => true,
            'price_modifier' => 16.00,
        ]);

        // Queso y Tocino Q11 cada uno
        $option2 = SectionOption::factory()->create([
            'section_id' => $section->id,
            'is_extra' => true,
            'price_modifier' => 11.00,
        ]);
        $option3 = SectionOption::factory()->create([
            'section_id' => $section->id,
            'is_extra' => true,
            'price_modifier' => 11.00,
        ]);

        // Seleccionar: Doble carne (Q16), Queso (Q11), Tocino (Q11)
        // Grupo Q16: 1 item, sin bundle
        // Grupo Q11: 2 items, 1 bundle → Q22 - Q5 = Q17
        // Total: Q16 + Q17 = Q33
        $result = $section->calculateOptionsPrice([
            $option1->id, $option2->id, $option3->id,
        ]);

        expect($result['total'])->toBe(33.00);
        expect($result['savings'])->toBe(5.00);  // Solo del grupo Q11
    });

    it('does not apply bundle when disabled', function () {
        $section = Section::factory()->create([
            'bundle_discount_enabled' => false,
        ]);

        $options = [];
        for ($i = 0; $i < 2; $i++) {
            $options[] = SectionOption::factory()->create([
                'section_id' => $section->id,
                'is_extra' => true,
                'price_modifier' => 11.00,
            ]);
        }

        $result = $section->calculateOptionsPrice(collect($options)->pluck('id')->toArray());

        expect($result['total'])->toBe(22.00);
        expect($result['savings'])->toBe(0.00);
    });

    it('does not apply bundle when count is less than bundle_size', function () {
        $section = Section::factory()->withBundlePricing(5.00, 2)->create();

        $option = SectionOption::factory()->create([
            'section_id' => $section->id,
            'is_extra' => true,
            'price_modifier' => 11.00,
        ]);

        // Solo 1 extra, no aplica bundle
        $result = $section->calculateOptionsPrice([$option->id]);

        expect($result['total'])->toBe(11.00);
        expect($result['savings'])->toBe(0.00);
    });

    it('handles non-extra options correctly', function () {
        $section = Section::factory()->withBundlePricing(5.00, 2)->create();

        // Opción sin extra (is_extra = false)
        $nonExtraOption = SectionOption::factory()->create([
            'section_id' => $section->id,
            'is_extra' => false,
            'price_modifier' => 0,
        ]);

        // 2 extras con precio
        $extra1 = SectionOption::factory()->create([
            'section_id' => $section->id,
            'is_extra' => true,
            'price_modifier' => 11.00,
        ]);
        $extra2 = SectionOption::factory()->create([
            'section_id' => $section->id,
            'is_extra' => true,
            'price_modifier' => 11.00,
        ]);

        $result = $section->calculateOptionsPrice([
            $nonExtraOption->id, $extra1->id, $extra2->id,
        ]);

        // 2 extras del mismo precio = Q22 - Q5 = Q17 + Q0 (non-extra) = Q17
        expect($result['total'])->toBe(17.00);
        expect($result['savings'])->toBe(5.00);
    });

    it('calculates bundle with different bundle sizes', function () {
        // Bundle size de 3 en lugar de 2
        $section = Section::factory()->withBundlePricing(8.00, 3)->create();

        $options = [];
        for ($i = 0; $i < 6; $i++) {
            $options[] = SectionOption::factory()->create([
                'section_id' => $section->id,
                'is_extra' => true,
                'price_modifier' => 10.00,
            ]);
        }

        // 6 extras de Q10 = Q60
        // 2 bundles de 3 = 2 * Q8 descuento = Q16 descuento
        // Total: Q60 - Q16 = Q44
        $result = $section->calculateOptionsPrice(collect($options)->pluck('id')->toArray());

        expect($result['total'])->toBe(44.00);
        expect($result['savings'])->toBe(16.00);
    });

    it('returns details about bundle calculation', function () {
        $section = Section::factory()->withBundlePricing(5.00, 2)->create();

        $option1 = SectionOption::factory()->create([
            'section_id' => $section->id,
            'is_extra' => true,
            'price_modifier' => 11.00,
        ]);
        $option2 = SectionOption::factory()->create([
            'section_id' => $section->id,
            'is_extra' => true,
            'price_modifier' => 11.00,
        ]);

        $result = $section->calculateOptionsPrice([$option1->id, $option2->id]);

        expect($result)->toHaveKey('details');
        expect($result['details'])->toHaveCount(1);
        expect($result['details'][0])->toMatchArray([
            'price' => 11.00,
            'count' => 2,
            'bundles' => 1,
            'savings' => 5.00,
        ]);
    });

    it('handles multiple price groups correctly', function () {
        $section = Section::factory()->withBundlePricing(5.00, 2)->create();

        // Grupo 1: 4 extras de Q10
        for ($i = 0; $i < 4; $i++) {
            $options[] = SectionOption::factory()->create([
                'section_id' => $section->id,
                'is_extra' => true,
                'price_modifier' => 10.00,
            ]);
        }

        // Grupo 2: 3 extras de Q15
        for ($i = 0; $i < 3; $i++) {
            $options[] = SectionOption::factory()->create([
                'section_id' => $section->id,
                'is_extra' => true,
                'price_modifier' => 15.00,
            ]);
        }

        $result = $section->calculateOptionsPrice(collect($options)->pluck('id')->toArray());

        // Grupo Q10: 4 items = Q40, 2 bundles = -Q10
        // Grupo Q15: 3 items = Q45, 1 bundle = -Q5
        // Total: (40 - 10) + (45 - 5) = 30 + 40 = Q70
        // Savings: 10 + 5 = Q15
        expect($result['total'])->toBe(70.00);
        expect($result['savings'])->toBe(15.00);
    });
});
