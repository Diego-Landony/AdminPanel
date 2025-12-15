<?php

use App\Models\Customer;
use App\Models\CustomerPointsTransaction;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Order;
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

describe('rewards (GET /api/v1/points/rewards)', function () {
    test('returns list of redeemable items', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);

        Product::factory()->create([
            'category_id' => $category->id,
            'is_redeemable' => true,
            'points_cost' => 100,
            'is_active' => true,
        ]);

        Combo::factory()->create([
            'category_id' => $category->id,
            'is_redeemable' => true,
            'points_cost' => 200,
            'is_active' => true,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/points/rewards');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'total',
                    'products_count',
                    'variants_count',
                    'combos_count',
                ],
            ]);

        expect($response->json('meta.total'))->toBeGreaterThanOrEqual(2);
    });

    test('excludes non-redeemable items', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);

        Product::factory()->create([
            'category_id' => $category->id,
            'is_redeemable' => false,
            'points_cost' => 100,
            'is_active' => true,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/points/rewards');

        $response->assertOk();
        expect($response->json('meta.products_count'))->toBe(0);
    });

    test('requires authentication', function () {
        $response = $this->getJson('/api/v1/points/rewards');

        $response->assertUnauthorized();
    });
});

describe('redeem (POST /api/v1/points/redeem)', function () {
    test('successfully redeems points on an order', function () {
        $customer = Customer::factory()->create([
            'points' => 200,
        ]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/points/redeem', [
                'order_id' => $order->id,
                'points_to_redeem' => 50,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'transaction',
                    'new_balance',
                ],
            ]);

        expect($customer->fresh()->points)->toBe(150);
    });

    test('creates redemption transaction record', function () {
        $customer = Customer::factory()->create([
            'points' => 200,
        ]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        actingAs($customer, 'sanctum')
            ->postJson('/api/v1/points/redeem', [
                'order_id' => $order->id,
                'points_to_redeem' => 50,
            ]);

        expect(CustomerPointsTransaction::where('customer_id', $customer->id)
            ->where('type', 'redeemed')
            ->count())->toBe(1);
    });

    test('fails when customer has insufficient points', function () {
        $customer = Customer::factory()->create([
            'points' => 10,
        ]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/points/redeem', [
                'order_id' => $order->id,
                'points_to_redeem' => 50,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['points_to_redeem']);
    });

    test('fails when order does not belong to customer', function () {
        $customer = Customer::factory()->create([
            'points' => 200,
        ]);

        $otherCustomer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $otherCustomer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/points/redeem', [
                'order_id' => $order->id,
                'points_to_redeem' => 50,
            ]);

        $response->assertNotFound();
    });

    test('requires order_id', function () {
        $customer = Customer::factory()->create([
            'points' => 200,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/points/redeem', [
                'points_to_redeem' => 50,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    });

    test('requires points_to_redeem', function () {
        $customer = Customer::factory()->create([
            'points' => 200,
        ]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/points/redeem', [
                'order_id' => $order->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['points_to_redeem']);
    });

    test('requires authentication', function () {
        $response = $this->postJson('/api/v1/points/redeem', [
            'order_id' => 1,
            'points_to_redeem' => 50,
        ]);

        $response->assertUnauthorized();
    });
});
