<?php

use App\Models\Customer;
use App\Models\CustomerPointsTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('balance (GET /api/v1/points/balance)', function () {
    test('returns customer points balance', function () {
        $customer = Customer::factory()->create([
            'points' => 150,
            'points_updated_at' => now(),
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/points/balance');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'points_balance',
                    'points_updated_at',
                    'points_value_in_currency',
                    'conversion_rate',
                ],
            ]);

        expect($response->json('data.points_balance'))->toBe(150);
        expect($response->json('data.points_value_in_currency'))->toBe(15);
    });

    test('requires authentication', function () {
        $response = $this->getJson('/api/v1/points/balance');

        $response->assertUnauthorized();
    });
});

describe('history (GET /api/v1/points/history)', function () {
    test('returns paginated transaction history', function () {
        $customer = Customer::factory()->create();

        CustomerPointsTransaction::factory()->count(5)->create([
            'customer_id' => $customer->id,
            'points' => 10,
            'type' => 'earned',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/points/history');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        expect($response->json('data'))->toHaveCount(5);
        expect($response->json('meta.total'))->toBe(5);
    });

    test('orders transactions by most recent first', function () {
        $customer = Customer::factory()->create();

        $oldTransaction = CustomerPointsTransaction::factory()->create([
            'customer_id' => $customer->id,
            'points' => 10,
            'type' => 'earned',
            'created_at' => now()->subDays(5),
        ]);

        $newTransaction = CustomerPointsTransaction::factory()->create([
            'customer_id' => $customer->id,
            'points' => 20,
            'type' => 'earned',
            'created_at' => now(),
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/points/history');

        $response->assertOk();
        expect($response->json('data.0.id'))->toBe($newTransaction->id);
        expect($response->json('data.1.id'))->toBe($oldTransaction->id);
    });

    test('requires authentication', function () {
        $response = $this->getJson('/api/v1/points/history');

        $response->assertUnauthorized();
    });
});

// Note: rewards and redeem endpoints were removed
// Points redemption only happens in-store, not in the app
