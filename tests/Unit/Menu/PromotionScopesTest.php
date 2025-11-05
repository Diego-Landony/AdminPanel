<?php

use App\Models\Menu\BundlePromotionItem;
use App\Models\Menu\Product;
use App\Models\Menu\Promotion;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Bundle Special Scopes', function () {
    test('filters only bundle_special type', function () {
        Promotion::factory()->count(3)->create(['type' => 'bundle_special']);
        Promotion::factory()->count(2)->create(['type' => 'daily_special']);
        Promotion::factory()->count(1)->create(['type' => 'two_for_one']);

        $combinados = Promotion::combinados()->get();

        expect($combinados)->toHaveCount(3);
        expect($combinados->every(fn ($p) => $p->type === 'bundle_special'))->toBeTrue();
    });
});

describe('Date and Time Filtering', function () {
    test('filters by current date', function () {
        $today = Carbon::today();

        // Active and valid now
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => $today->copy()->subDays(5)->format('Y-m-d'),
            'valid_until' => $today->copy()->addDays(5)->format('Y-m-d'),
        ]);

        // Not started yet
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => $today->copy()->addDays(10)->format('Y-m-d'),
            'valid_until' => $today->copy()->addDays(20)->format('Y-m-d'),
        ]);

        // Already expired
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => $today->copy()->subDays(20)->format('Y-m-d'),
            'valid_until' => $today->copy()->subDays(10)->format('Y-m-d'),
        ]);

        $validNow = Promotion::validNowCombinados()->get();

        expect($validNow)->toHaveCount(1);
    });

    test('filters by current time', function () {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        // Valid now (correct time)
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => $today,
            'valid_until' => $today,
            'time_from' => $now->copy()->subHours(2)->format('H:i:s'),
            'time_until' => $now->copy()->addHours(2)->format('H:i:s'),
        ]);

        // Not time yet
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => $today,
            'valid_until' => $today,
            'time_from' => $now->copy()->addHours(3)->format('H:i:s'),
            'time_until' => $now->copy()->addHours(5)->format('H:i:s'),
        ]);

        // Time already passed
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => $today,
            'valid_until' => $today,
            'time_from' => $now->copy()->subHours(5)->format('H:i:s'),
            'time_until' => $now->copy()->subHours(3)->format('H:i:s'),
        ]);

        $validNow = Promotion::validNowCombinados()->get();

        expect($validNow)->toHaveCount(1);
    });

    test('filters by day of week', function () {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $currentWeekday = $now->dayOfWeekIso; // 1 (Monday) to 7 (Sunday)

        // Valid today
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => $today,
            'valid_until' => $today,
            'weekdays' => [$currentWeekday],
        ]);

        // Not valid today (different day)
        $differentDay = $currentWeekday === 1 ? 2 : 1;
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => $today,
            'valid_until' => $today,
            'weekdays' => [$differentDay],
        ]);

        $validNow = Promotion::validNowCombinados()->get();

        expect($validNow)->toHaveCount(1);
    });

    test('accepts null for always valid', function () {
        // No temporal restrictions
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => null,
            'valid_until' => null,
            'time_from' => null,
            'time_until' => null,
            'weekdays' => null,
        ]);

        $validNow = Promotion::validNowCombinados()->get();

        expect($validNow)->toHaveCount(1);
    });

    test('accepts custom datetime', function () {
        $futureDate = Carbon::create(2025, 12, 15, 14, 30, 0);

        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => '2025-12-10',
            'valid_until' => '2025-12-20',
            'time_from' => '12:00:00',
            'time_until' => '18:00:00',
        ]);

        $validNow = Promotion::validNowCombinados($futureDate)->get();

        expect($validNow)->toHaveCount(1);
    });

    test('works with multiple conditions', function () {
        $now = Carbon::create(2025, 12, 15, 14, 30, 0); // Monday
        $currentWeekday = $now->dayOfWeekIso; // 1 = Monday

        // Valid in all aspects
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => '2025-12-10',
            'valid_until' => '2025-12-20',
            'time_from' => '12:00:00',
            'time_until' => '18:00:00',
            'weekdays' => [$currentWeekday],
        ]);

        // Correct date but incorrect time
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => '2025-12-10',
            'valid_until' => '2025-12-20',
            'time_from' => '20:00:00',
            'time_until' => '23:00:00',
            'weekdays' => [$currentWeekday],
        ]);

        // Correct date and time but incorrect day
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => '2025-12-10',
            'valid_until' => '2025-12-20',
            'time_from' => '12:00:00',
            'time_until' => '18:00:00',
            'weekdays' => [6, 7], // Saturday and Sunday
        ]);

        $validNow = Promotion::validNowCombinados($now)->get();

        expect($validNow)->toHaveCount(1);
    });
});

describe('Status Filtering', function () {
    test('requires is_active to be true', function () {
        $today = Carbon::today();

        // Inactive even though in date range
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => false,
            'valid_from' => $today->copy()->subDays(5)->format('Y-m-d'),
            'valid_until' => $today->copy()->addDays(5)->format('Y-m-d'),
        ]);

        $validNow = Promotion::validNowCombinados()->get();

        expect($validNow)->toHaveCount(0);
    });

    test('filters upcoming promotions that have not started', function () {
        $today = Carbon::today();

        // Not started yet
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => $today->copy()->addDays(10)->format('Y-m-d'),
        ]);

        // Already started
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => $today->copy()->subDays(5)->format('Y-m-d'),
        ]);

        // No valid_from
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
            'valid_from' => null,
        ]);

        $upcoming = Promotion::upcoming()->get();

        expect($upcoming)->toHaveCount(1);
    });

    test('filters expired promotions', function () {
        $today = Carbon::today();

        // Already expired
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'valid_until' => $today->copy()->subDays(5)->format('Y-m-d'),
        ]);

        // Still valid
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'valid_until' => $today->copy()->addDays(5)->format('Y-m-d'),
        ]);

        // No valid_until
        Promotion::factory()->create([
            'type' => 'bundle_special',
            'valid_until' => null,
        ]);

        $expired = Promotion::expired()->get();

        expect($expired)->toHaveCount(1);
    });
});

describe('Availability Filtering', function () {
    test('filters promotions with available items', function () {
        $activeProduct = Product::factory()->create(['is_active' => true]);
        $inactiveProduct = Product::factory()->create(['is_active' => false]);

        // Promotion with active products
        $availablePromo = Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
        ]);
        BundlePromotionItem::create([
            'promotion_id' => $availablePromo->id,
            'is_choice_group' => false,
            'product_id' => $activeProduct->id,
            'quantity' => 1,
            'sort_order' => 1,
        ]);

        // Promotion with inactive product in fixed item
        $unavailablePromo = Promotion::factory()->create([
            'type' => 'bundle_special',
            'is_active' => true,
        ]);
        BundlePromotionItem::create([
            'promotion_id' => $unavailablePromo->id,
            'is_choice_group' => false,
            'product_id' => $inactiveProduct->id,
            'quantity' => 1,
            'sort_order' => 1,
        ]);

        $available = Promotion::available()->get();

        expect($available)->toHaveCount(1);
        expect($available->first()->id)->toBe($availablePromo->id);
    });
});
