<?php

use App\Models\Customer;
use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('recentOrders (GET /api/v1/me/recent-orders)', function () {
    test('returns recent completed orders for authenticated customer', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $orders = Order::factory()
            ->count(3)
            ->completed()
            ->create([
                'customer_id' => $customer->id,
                'restaurant_id' => $restaurant->id,
            ]);

        foreach ($orders as $order) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
            ]);
        }

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/me/recent-orders');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'order_number',
                        'ordered_at',
                        'restaurant',
                        'total',
                        'items_summary',
                        'items',
                        'can_reorder',
                    ],
                ],
            ]);

        expect($response->json('data'))->toHaveCount(3);
    });

    test('returns maximum 5 orders', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $orders = Order::factory()
            ->count(7)
            ->completed()
            ->create([
                'customer_id' => $customer->id,
                'restaurant_id' => $restaurant->id,
            ]);

        foreach ($orders as $order) {
            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
            ]);
        }

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/me/recent-orders');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(5);
    });

    test('only returns completed or delivered orders', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $completedOrder = Order::factory()->completed()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $completedOrder->id,
            'product_id' => $product->id,
        ]);

        $deliveredOrder = Order::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => Order::STATUS_DELIVERED,
        ]);

        OrderItem::factory()->create([
            'order_id' => $deliveredOrder->id,
            'product_id' => $product->id,
        ]);

        $pendingOrder = Order::factory()->pending()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $pendingOrder->id,
            'product_id' => $product->id,
        ]);

        $preparingOrder = Order::factory()->preparing()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $preparingOrder->id,
            'product_id' => $product->id,
        ]);

        $cancelledOrder = Order::factory()->cancelled()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $cancelledOrder->id,
            'product_id' => $product->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/me/recent-orders');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(2);

        $orderIds = collect($response->json('data'))->pluck('id')->toArray();
        expect($orderIds)->toContain($completedOrder->id);
        expect($orderIds)->toContain($deliveredOrder->id);
        expect($orderIds)->not->toContain($pendingOrder->id);
        expect($orderIds)->not->toContain($preparingOrder->id);
        expect($orderIds)->not->toContain($cancelledOrder->id);
    });

    test('orders by most recent first', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $oldestOrder = Order::factory()->completed()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'created_at' => now()->subDays(10),
        ]);

        OrderItem::factory()->create([
            'order_id' => $oldestOrder->id,
            'product_id' => $product->id,
        ]);

        $middleOrder = Order::factory()->completed()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'created_at' => now()->subDays(5),
        ]);

        OrderItem::factory()->create([
            'order_id' => $middleOrder->id,
            'product_id' => $product->id,
        ]);

        $newestOrder = Order::factory()->completed()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'created_at' => now()->subDay(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $newestOrder->id,
            'product_id' => $product->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/me/recent-orders');

        $response->assertOk();
        expect($response->json('data.0.id'))->toBe($newestOrder->id);
        expect($response->json('data.1.id'))->toBe($middleOrder->id);
        expect($response->json('data.2.id'))->toBe($oldestOrder->id);
    });

    test('includes items with availability status', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);

        $activeProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $inactiveProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $order = Order::factory()->completed()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $activeProduct->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $inactiveProduct->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/me/recent-orders');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1);

        $items = $response->json('data.0.items');
        expect($items)->toHaveCount(2);

        // Items have name, quantity, is_available - find by availability status
        $availableItems = collect($items)->where('is_available', true);
        $unavailableItems = collect($items)->where('is_available', false);

        expect($availableItems)->toHaveCount(1);
        expect($unavailableItems)->toHaveCount(1);
    });

    test('sets can_reorder to false when item unavailable', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);

        $activeProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $inactiveProduct = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $orderWithActiveItems = Order::factory()->completed()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $orderWithActiveItems->id,
            'product_id' => $activeProduct->id,
        ]);

        $orderWithInactiveItem = Order::factory()->completed()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $orderWithInactiveItem->id,
            'product_id' => $inactiveProduct->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/me/recent-orders');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(2);

        $orderWithAllAvailable = collect($response->json('data'))->firstWhere('id', $orderWithActiveItems->id);
        $orderWithUnavailable = collect($response->json('data'))->firstWhere('id', $orderWithInactiveItem->id);

        expect($orderWithAllAvailable['can_reorder'])->toBeTrue();
        expect($orderWithUnavailable['can_reorder'])->toBeFalse();
    });

    test('requires authentication', function () {
        $response = $this->getJson('/api/v1/me/recent-orders');

        $response->assertUnauthorized();
    });

    test('returns empty array when no orders', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/me/recent-orders');

        $response->assertOk();
        expect($response->json('data'))->toBeArray()->toBeEmpty();
    });
});
