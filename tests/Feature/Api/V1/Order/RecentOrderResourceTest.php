<?php

use App\Http\Resources\Api\V1\Order\RecentOrderResource;
use App\Models\Customer;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('RecentOrderResource', function () {
    test('transforms order with basic fields', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create(['name' => 'Test Restaurant']);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'order_number' => 'ORD-12345',
            'total' => 50.00,
            'created_at' => now()->subHours(2),
        ]);

        $order->load('restaurant');

        $resource = new RecentOrderResource($order);
        $data = $resource->toArray(request());

        expect($data)->toHaveKeys([
            'id',
            'order_number',
            'ordered_at',
            'restaurant',
            'total',
            'items_summary',
            'items',
            'can_reorder',
        ]);

        expect($data['id'])->toBe($order->id);
        expect($data['order_number'])->toBe('ORD-12345');
        expect($data['total'])->toBe(50.00);
        expect($data['restaurant']['id'])->toBe($restaurant->id);
        expect($data['restaurant']['name'])->toBe('Test Restaurant');
    });

    test('includes restaurant only when loaded', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $resource = new RecentOrderResource($order);
        $data = $resource->toArray(request());

        expect($data)->not->toHaveKey('restaurant');
    });

    test('generates items summary from product names', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'product_snapshot' => ['name' => 'Pizza Margherita'],
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'product_snapshot' => ['name' => 'Coca Cola'],
        ]);

        $order->load('items');

        $resource = new RecentOrderResource($order);
        $data = $resource->toArray(request());

        expect($data['items_summary'])->toBe('Pizza Margherita, Coca Cola');
    });

    test('truncates items summary to 50 characters with ellipsis', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 1,
            'product_snapshot' => ['name' => 'Pizza Margherita Extra Large'],
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'product_snapshot' => ['name' => 'Coca Cola 2L'],
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product3->id,
            'quantity' => 1,
            'product_snapshot' => ['name' => 'Chicken Wings'],
        ]);

        $order->load('items');

        $resource = new RecentOrderResource($order);
        $data = $resource->toArray(request());

        expect($data['items_summary'])->toEndWith('...');
        expect(mb_strlen($data['items_summary']))->toBe(50);
    });

    test('transforms items with availability check for products', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $activeProduct = Product::factory()->create(['is_active' => true]);
        $inactiveProduct = Product::factory()->create(['is_active' => false]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $activeProduct->id,
            'quantity' => 2,
            'product_snapshot' => ['name' => 'Active Product'],
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $inactiveProduct->id,
            'quantity' => 1,
            'product_snapshot' => ['name' => 'Inactive Product'],
        ]);

        $order->load(['items.product']);

        $resource = new RecentOrderResource($order);
        $data = $resource->toArray(request());

        expect($data['items'])->toHaveCount(2);
        expect($data['items'][0]['name'])->toBe('Active Product');
        expect($data['items'][0]['quantity'])->toBe(2);
        expect($data['items'][0]['is_available'])->toBeTrue();
        expect($data['items'][1]['name'])->toBe('Inactive Product');
        expect($data['items'][1]['quantity'])->toBe(1);
        expect($data['items'][1]['is_available'])->toBeFalse();
    });

    test('transforms items with availability check for combos', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $activeCombo = Combo::factory()->create(['is_active' => true]);
        $inactiveCombo = Combo::factory()->create(['is_active' => false]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'combo_id' => $activeCombo->id,
            'quantity' => 1,
            'product_snapshot' => ['name' => 'Active Combo'],
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'combo_id' => $inactiveCombo->id,
            'quantity' => 1,
            'product_snapshot' => ['name' => 'Inactive Combo'],
        ]);

        $order->load(['items.combo']);

        $resource = new RecentOrderResource($order);
        $data = $resource->toArray(request());

        expect($data['items'])->toHaveCount(2);
        expect($data['items'][0]['is_available'])->toBeTrue();
        expect($data['items'][1]['is_available'])->toBeFalse();
    });

    test('can_reorder is true when all items are available', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $product1 = Product::factory()->create(['is_active' => true]);
        $product2 = Product::factory()->create(['is_active' => true]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 1,
            'product_snapshot' => ['name' => 'Product 1'],
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'product_snapshot' => ['name' => 'Product 2'],
        ]);

        $order->load(['items.product']);

        $resource = new RecentOrderResource($order);
        $data = $resource->toArray(request());

        expect($data['can_reorder'])->toBeTrue();
    });

    test('can_reorder is false when any item is unavailable', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $activeProduct = Product::factory()->create(['is_active' => true]);
        $inactiveProduct = Product::factory()->create(['is_active' => false]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $activeProduct->id,
            'quantity' => 1,
            'product_snapshot' => ['name' => 'Active Product'],
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $inactiveProduct->id,
            'quantity' => 1,
            'product_snapshot' => ['name' => 'Inactive Product'],
        ]);

        $order->load(['items.product']);

        $resource = new RecentOrderResource($order);
        $data = $resource->toArray(request());

        expect($data['can_reorder'])->toBeFalse();
    });

    test('handles empty items collection', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $order->load('items');

        $resource = new RecentOrderResource($order);
        $data = $resource->toArray(request());

        expect($data['items_summary'])->toBe('');
        expect($data['items'])->toBeEmpty();
        expect($data['can_reorder'])->toBeTrue();
    });

    test('handles items without loaded relationships', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $product = Product::factory()->create(['is_active' => true]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'product_snapshot' => ['name' => 'Test Product'],
        ]);

        $order->load('items');

        $resource = new RecentOrderResource($order);
        $data = $resource->toArray(request());

        expect($data['items'][0]['is_available'])->toBeFalse();
        expect($data['can_reorder'])->toBeFalse();
    });

    test('handles mixed product and combo items', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $product = Product::factory()->create(['is_active' => true]);
        $combo = Combo::factory()->create(['is_active' => true]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'product_snapshot' => ['name' => 'Pizza'],
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'combo_id' => $combo->id,
            'quantity' => 1,
            'product_snapshot' => ['name' => 'Combo Deal'],
        ]);

        $order->load(['items.product', 'items.combo']);

        $resource = new RecentOrderResource($order);
        $data = $resource->toArray(request());

        expect($data['items'])->toHaveCount(2);
        expect($data['items'][0]['name'])->toBe('Pizza');
        expect($data['items'][0]['is_available'])->toBeTrue();
        expect($data['items'][1]['name'])->toBe('Combo Deal');
        expect($data['items'][1]['is_available'])->toBeTrue();
        expect($data['can_reorder'])->toBeTrue();
    });
});
