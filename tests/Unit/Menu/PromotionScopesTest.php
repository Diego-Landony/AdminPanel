<?php

use App\Models\Menu\BundlePromotionItem;
use App\Models\Menu\Product;
use App\Models\Menu\Promotion;
use Carbon\Carbon;

// ============================================================================
// Tests de Scopes para Bundle Promotions
// ============================================================================

test('scope combinados filtra solo bundle_special', function () {
    Promotion::factory()->count(3)->create(['type' => 'bundle_special']);
    Promotion::factory()->count(2)->create(['type' => 'daily_special']);
    Promotion::factory()->count(1)->create(['type' => 'two_for_one']);

    $combinados = Promotion::combinados()->get();

    expect($combinados)->toHaveCount(3);
    expect($combinados->every(fn ($p) => $p->type === 'bundle_special'))->toBeTrue();
});

test('scope validNowCombinados filtra por fecha actual', function () {
    $today = Carbon::today();

    // Activo y válido ahora
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => $today->copy()->subDays(5)->format('Y-m-d'),
        'valid_until' => $today->copy()->addDays(5)->format('Y-m-d'),
    ]);

    // Aún no inicia
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => $today->copy()->addDays(10)->format('Y-m-d'),
        'valid_until' => $today->copy()->addDays(20)->format('Y-m-d'),
    ]);

    // Ya expiró
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => $today->copy()->subDays(20)->format('Y-m-d'),
        'valid_until' => $today->copy()->subDays(10)->format('Y-m-d'),
    ]);

    $validNow = Promotion::validNowCombinados()->get();

    expect($validNow)->toHaveCount(1);
});

test('scope validNowCombinados filtra por hora actual', function () {
    $now = Carbon::now();
    $today = $now->format('Y-m-d');

    // Válido ahora (hora correcta)
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => $today,
        'valid_until' => $today,
        'time_from' => $now->copy()->subHours(2)->format('H:i:s'),
        'time_until' => $now->copy()->addHours(2)->format('H:i:s'),
    ]);

    // Aún no es la hora
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => $today,
        'valid_until' => $today,
        'time_from' => $now->copy()->addHours(3)->format('H:i:s'),
        'time_until' => $now->copy()->addHours(5)->format('H:i:s'),
    ]);

    // Ya pasó la hora
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

test('scope validNowCombinados filtra por día de la semana', function () {
    $now = Carbon::now();
    $today = $now->format('Y-m-d');
    $currentWeekday = $now->dayOfWeekIso; // 1 (lunes) a 7 (domingo)

    // Válido hoy
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => $today,
        'valid_until' => $today,
        'weekdays' => [$currentWeekday],
    ]);

    // No válido hoy (día diferente)
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

test('scope validNowCombinados acepta null para siempre válido', function () {
    // Sin restricciones temporales
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

test('scope validNowCombinados requiere is_active true', function () {
    $today = Carbon::today();

    // Inactivo aunque esté en rango de fechas
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => false,
        'valid_from' => $today->copy()->subDays(5)->format('Y-m-d'),
        'valid_until' => $today->copy()->addDays(5)->format('Y-m-d'),
    ]);

    $validNow = Promotion::validNowCombinados()->get();

    expect($validNow)->toHaveCount(0);
});

test('scope validNowCombinados acepta datetime personalizado', function () {
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

test('scope upcoming filtra combinados que aún no inician', function () {
    $today = Carbon::today();

    // Aún no inicia
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => $today->copy()->addDays(10)->format('Y-m-d'),
    ]);

    // Ya inició
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => $today->copy()->subDays(5)->format('Y-m-d'),
    ]);

    // Sin valid_from
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => null,
    ]);

    $upcoming = Promotion::upcoming()->get();

    expect($upcoming)->toHaveCount(1);
});

test('scope expired filtra combinados que ya expiraron', function () {
    $today = Carbon::today();

    // Ya expiró
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'valid_until' => $today->copy()->subDays(5)->format('Y-m-d'),
    ]);

    // Aún vigente
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'valid_until' => $today->copy()->addDays(5)->format('Y-m-d'),
    ]);

    // Sin valid_until
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'valid_until' => null,
    ]);

    $expired = Promotion::expired()->get();

    expect($expired)->toHaveCount(1);
});

test('scope available filtra combinados con items disponibles', function () {
    $activeProduct = Product::factory()->create(['is_active' => true]);
    $inactiveProduct = Product::factory()->create(['is_active' => false]);

    // Combinado con productos activos
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

    // Combinado con producto inactivo en item fijo
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

test('scope validNowCombinados funciona con múltiples condiciones', function () {
    $now = Carbon::create(2025, 12, 15, 14, 30, 0); // Lunes
    $currentWeekday = $now->dayOfWeekIso; // 1 = Lunes

    // Válido en todos los aspectos
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => '2025-12-10',
        'valid_until' => '2025-12-20',
        'time_from' => '12:00:00',
        'time_until' => '18:00:00',
        'weekdays' => [$currentWeekday],
    ]);

    // Fecha correcta pero hora incorrecta
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => '2025-12-10',
        'valid_until' => '2025-12-20',
        'time_from' => '20:00:00',
        'time_until' => '23:00:00',
        'weekdays' => [$currentWeekday],
    ]);

    // Fecha y hora correctas pero día incorrecto
    Promotion::factory()->create([
        'type' => 'bundle_special',
        'is_active' => true,
        'valid_from' => '2025-12-10',
        'valid_until' => '2025-12-20',
        'time_from' => '12:00:00',
        'time_until' => '18:00:00',
        'weekdays' => [6, 7], // Sábado y Domingo
    ]);

    $validNow = Promotion::validNowCombinados($now)->get();

    expect($validNow)->toHaveCount(1);
});
