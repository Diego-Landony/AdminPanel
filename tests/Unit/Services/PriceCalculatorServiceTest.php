<?php

use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Section;
use App\Models\Menu\SectionOption;
use App\Services\PriceCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new PriceCalculatorService;

    // Create category without variants
    $this->category = Category::factory()->create(['uses_variants' => false]);

    // Create product with prices (category_id already associates product to category)
    $this->product = Product::factory()->create([
        'category_id' => $this->category->id,
        'precio_pickup_capital' => 50.00,
        'precio_domicilio_capital' => 55.00,
        'precio_pickup_interior' => 45.00,
        'precio_domicilio_interior' => 50.00,
    ]);

    // Create category with variants
    $this->categoryWithVariants = Category::factory()->create(['uses_variants' => true]);
    $this->productWithVariants = Product::factory()->create([
        'category_id' => $this->categoryWithVariants->id,
    ]);

    // Create variant
    $this->variant = ProductVariant::factory()->create([
        'product_id' => $this->productWithVariants->id,
        'precio_pickup_capital' => 50.00,
        'precio_domicilio_capital' => 55.00,
        'precio_pickup_interior' => 45.00,
        'precio_domicilio_interior' => 50.00,
        'is_daily_special' => false,
    ]);
});

describe('Price Calculation', function () {
    test('calculates base price without options', function () {
        $result = $this->service->calculatePrice(
            $this->product,
            $this->category->id,
            null,
            'precio_pickup_capital',
            1
        );

        expect($result)->toHaveKey('unit_price', 50.0)
            ->and($result)->toHaveKey('subtotal', 50.0)
            ->and($result)->toHaveKey('discount', 0.0)
            ->and($result)->toHaveKey('is_daily_special', false);
    });

    test('calculates price with multiple quantity', function () {
        $result = $this->service->calculatePrice(
            $this->product,
            $this->category->id,
            null,
            'precio_pickup_capital',
            3
        );

        expect($result)->toHaveKey('unit_price', 50.0)
            ->and($result)->toHaveKey('quantity', 3)
            ->and($result)->toHaveKey('subtotal', 150.0);
    });

    test('calculates price with option modifiers', function () {
        $section = Section::factory()->create();
        $option1 = SectionOption::factory()->create([
            'section_id' => $section->id,
            'price_modifier' => 25.00,
            'is_extra' => true,
        ]);

        $result = $this->service->calculatePrice(
            $this->product,
            $this->category->id,
            null,
            'precio_pickup_capital',
            1,
            [$option1->id]
        );

        expect($result)->toHaveKey('unit_price', 75.0)
            ->and($result)->toHaveKey('options_modifier', 25.0);
    });
});

describe('Special Handling', function () {
    test('detects daily special correctly', function () {
        // Create daily special variant for today
        $today = Carbon::now()->dayOfWeek;
        $this->variant->update([
            'is_daily_special' => true,
            'daily_special_days' => [$today],
            'daily_special_precio_pickup_capital' => 35.00,
            'daily_special_precio_domicilio_capital' => 40.00,
        ]);

        $result = $this->service->calculatePrice(
            $this->productWithVariants,
            $this->categoryWithVariants->id,
            $this->variant->id,
            'precio_pickup_capital',
            1
        );

        expect($result)->toHaveKey('is_daily_special', true)
            ->and($result)->toHaveKey('unit_price', 35.0);
    });

    test('does not apply promotions to daily special', function () {
        $today = Carbon::now()->dayOfWeek;
        $this->variant->update([
            'is_daily_special' => true,
            'daily_special_days' => [$today],
            'daily_special_precio_pickup_capital' => 35.00,
        ]);

        $result = $this->service->calculatePrice(
            $this->productWithVariants,
            $this->categoryWithVariants->id,
            $this->variant->id,
            'precio_pickup_capital',
            1
        );

        // Daily special should not have additional promotions
        expect($result)->toHaveKey('is_daily_special', true)
            ->and($result)->toHaveKey('discount', 0.0)
            ->and($result)->toHaveKey('promotion', null);
    });
});

describe('Price Types', function () {
    test('uses correct price type for each modality', function () {
        $results = [
            'precio_pickup_capital' => $this->service->calculatePrice($this->product, $this->category->id, null, 'precio_pickup_capital', 1),
            'precio_domicilio_capital' => $this->service->calculatePrice($this->product, $this->category->id, null, 'precio_domicilio_capital', 1),
            'precio_pickup_interior' => $this->service->calculatePrice($this->product, $this->category->id, null, 'precio_pickup_interior', 1),
            'precio_domicilio_interior' => $this->service->calculatePrice($this->product, $this->category->id, null, 'precio_domicilio_interior', 1),
        ];

        expect($results['precio_pickup_capital']['unit_price'])->toBe(50.0)
            ->and($results['precio_domicilio_capital']['unit_price'])->toBe(55.0)
            ->and($results['precio_pickup_interior']['unit_price'])->toBe(45.0)
            ->and($results['precio_domicilio_interior']['unit_price'])->toBe(50.0);
    });
});
