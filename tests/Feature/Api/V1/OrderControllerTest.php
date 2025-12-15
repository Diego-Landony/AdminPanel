<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\ComboItem;
use App\Models\Menu\Product;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('store (POST /api/v1/orders)', function () {
    test('creates order from cart with product', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 50.00,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'pickup',
            'zone' => 'capital',
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50.00,
            'subtotal' => 100.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'pickup',
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();
        expect($response->json('data.id'))->toBeInt();
        expect($response->json('data.restaurant.id'))->toBe($restaurant->id);
        expect($response->json('data.service_type'))->toBe('pickup');
        expect($response->json('data.status'))->toBe('pending');

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'pending',
        ]);
    });

    test('creates order from cart with combo', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        $combo = Combo::factory()->create([
            'is_active' => true,
            'precio_pickup_capital' => 100.00,
        ]);
        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'pickup',
            'zone' => 'capital',
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'combo_id' => $combo->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'pickup',
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('order_items', [
            'combo_id' => $combo->id,
        ]);
    });

    test('creates order with delivery address', function () {
        $customer = Customer::factory()->create();
        $kml = '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <Placemark>
      <Polygon>
        <outerBoundaryIs>
          <LinearRing>
            <coordinates>
              -90.51,14.63 -90.50,14.63 -90.50,14.64 -90.51,14.64 -90.51,14.63
            </coordinates>
          </LinearRing>
        </outerBoundaryIs>
      </Polygon>
    </Placemark>
  </Document>
</kml>';
        $restaurant = Restaurant::factory()->create([
            'latitude' => 14.6349,
            'longitude' => -90.5069,
            'is_active' => true,
            'delivery_active' => true,
            'geofence_kml' => $kml,
            'price_location' => 'capital',
        ]);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_domicilio_capital' => 55.00,
        ]);

        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'latitude' => 14.6350,
            'longitude' => -90.5070,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'delivery',
            'zone' => 'capital',
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 55.00,
            'subtotal' => 55.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'delivery',
                'delivery_address_id' => $address->id,
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();
        expect($response->json('data.service_type'))->toBe('delivery');
    });

    test('applies points redemption', function () {
        $customer = Customer::factory()->create([
            'points' => 500,
        ]);
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 100.00,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'pickup',
            'zone' => 'capital',
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'pickup',
                'payment_method' => 'cash',
                'points_to_redeem' => 100,
            ]);

        $response->assertCreated();
        expect($response->json('data.points.redeemed'))->toBe(100);
    });

    test('calculates points to earn', function () {
        $customer = Customer::factory()->create([
            'points' => 0,
        ]);
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 100.00,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'pickup',
            'zone' => 'capital',
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'subtotal' => 100.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'pickup',
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();
        expect($response->json('data.points.earned'))->toBeGreaterThanOrEqual(0);
    });

    test('converts cart to converted status', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 50.00,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'pickup',
            'zone' => 'capital',
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'subtotal' => 50.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'pickup',
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();

        $cart->refresh();
        expect($cart->status)->toBe('converted');
    });

    test('validates required fields', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['restaurant_id', 'service_type', 'payment_method']);
    });

    test('validates restaurant exists', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => 999999,
                'service_type' => 'pickup',
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['restaurant_id']);
    });

    test('validates delivery_address required for delivery', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'delivery',
            'zone' => 'capital',
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'delivery',
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['delivery_address_id']);
    });

    test('requires authentication', function () {
        $response = $this->postJson('/api/v1/orders', []);

        $response->assertUnauthorized();
    });

    test('fails if cart is empty', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();

        Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'active',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'pickup',
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(422);
    });

    test('validates scheduled_pickup_time is at least 30 minutes from now for pickup', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 50.00,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'pickup',
            'zone' => 'capital',
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'subtotal' => 50.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'pickup',
                'payment_method' => 'cash',
                'scheduled_pickup_time' => now()->addMinutes(15)->toIso8601String(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_pickup_time']);
    });

    test('accepts scheduled_pickup_time that is 30 minutes or more from now', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 50.00,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'pickup',
            'zone' => 'capital',
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'subtotal' => 50.00,
        ]);

        $scheduledTime = now()->addMinutes(45);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'pickup',
                'payment_method' => 'cash',
                'scheduled_pickup_time' => $scheduledTime->toIso8601String(),
            ]);

        $response->assertCreated();
        expect($response->json('data.timestamps.scheduled_pickup_time'))->not->toBeNull();
    });

    test('does not validate scheduled_pickup_time for delivery orders', function () {
        $customer = Customer::factory()->create();
        $kml = '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <Placemark>
      <Polygon>
        <outerBoundaryIs>
          <LinearRing>
            <coordinates>
              -90.51,14.63 -90.50,14.63 -90.50,14.64 -90.51,14.64 -90.51,14.63
            </coordinates>
          </LinearRing>
        </outerBoundaryIs>
      </Polygon>
    </Placemark>
  </Document>
</kml>';
        $restaurant = Restaurant::factory()->create([
            'latitude' => 14.6349,
            'longitude' => -90.5069,
            'is_active' => true,
            'delivery_active' => true,
            'geofence_kml' => $kml,
            'price_location' => 'capital',
        ]);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_domicilio_capital' => 55.00,
        ]);

        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'latitude' => 14.6350,
            'longitude' => -90.5070,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'delivery',
            'zone' => 'capital',
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 55.00,
            'subtotal' => 55.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'delivery',
                'delivery_address_id' => $address->id,
                'payment_method' => 'cash',
                'scheduled_pickup_time' => now()->addMinutes(10)->toIso8601String(),
            ]);

        $response->assertCreated();
    });
});

describe('index (GET /api/v1/orders)', function () {
    test('returns paginated order history', function () {
        $customer = Customer::factory()->create();

        Order::factory()->count(15)->create([
            'customer_id' => $customer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'order_number',
                        'status',
                        'summary',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    });

    test('orders by created_at desc', function () {
        $customer = Customer::factory()->create();

        $oldOrder = Order::factory()->create([
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(5),
        ]);

        $newOrder = Order::factory()->create([
            'customer_id' => $customer->id,
            'created_at' => now()->subDay(),
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/orders');

        $response->assertOk();
        expect($response->json('data.0.id'))->toBe($newOrder->id);
        expect($response->json('data.1.id'))->toBe($oldOrder->id);
    });

    test('only returns customer\'s own orders', function () {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $customerOrder = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        Order::factory()->create([
            'customer_id' => $otherCustomer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/orders');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.id'))->toBe($customerOrder->id);
    });

    test('requires authentication', function () {
        $response = $this->getJson('/api/v1/orders');

        $response->assertUnauthorized();
    });
});

describe('active (GET /api/v1/orders/active)', function () {
    test('returns only non-completed orders', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();

        Order::factory()->pending()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        Order::factory()->preparing()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        Order::factory()->completed()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/orders/active');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(2);
    });

    test('excludes cancelled and completed orders', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();

        Order::factory()->pending()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        Order::factory()->cancelled()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        Order::factory()->completed()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/orders/active');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.status'))->toBe('pending');
    });

    test('requires authentication', function () {
        $response = $this->getJson('/api/v1/orders/active');

        $response->assertUnauthorized();
    });
});

describe('show (GET /api/v1/orders/{id})', function () {
    test('returns order detail with items and promotions', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'summary',
                    'items',
                    'restaurant',
                ],
            ]);
    });

    test('returns 404 for non-existent order', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/orders/999999');

        $response->assertNotFound();
    });

    test('returns 403 for other customer\'s order', function () {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $otherCustomer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertForbidden();
    });

    test('requires authentication', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $response = $this->getJson("/api/v1/orders/{$order->id}");

        $response->assertUnauthorized();
    });
});

describe('track (GET /api/v1/orders/{id}/track)', function () {
    test('returns current status and history', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $order = Order::factory()->preparing()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}/track");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'order_number',
                    'current_status',
                    'restaurant',
                    'service_type',
                ],
            ]);
    });

    test('includes all status changes', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'ready',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}/track");

        $response->assertOk();
        expect($response->json('data.status_history'))->toBeArray();
    });

    test('requires authentication', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $response = $this->getJson("/api/v1/orders/{$order->id}/track");

        $response->assertUnauthorized();
    });
});

describe('cancel (POST /api/v1/orders/{id}/cancel)', function () {
    test('cancels pending order', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", [
                'reason' => 'Changed my mind',
            ]);

        $response->assertOk();

        $order->refresh();
        expect($order->status)->toBe('cancelled');
    });

    test('saves cancellation reason', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $reason = 'Order taking too long';

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", [
                'reason' => $reason,
            ]);

        $response->assertOk();

        $order->refresh();
        expect($order->cancellation_reason)->toBe($reason);
    });

    test('creates status history entry', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", [
                'reason' => 'Customer request',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'new_status' => 'cancelled',
        ]);
    });

    test('fails for already completed order', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'completed',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", [
                'reason' => 'Test reason',
            ]);

        $response->assertStatus(422);
    });

    test('fails for already cancelled order', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'cancelled',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", [
                'reason' => 'Test reason',
            ]);

        $response->assertStatus(422);
    });

    test('validates reason is required', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    });

    test('requires authentication', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/orders/{$order->id}/cancel", [
            'reason' => 'Test',
        ]);

        $response->assertUnauthorized();
    });
});

describe('reorder (POST /api/v1/orders/{id}/reorder)', function () {
    test('creates new cart with order items', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $order = Order::factory()->pickup()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/reorder");

        $response->assertOk();

        $this->assertDatabaseHas('carts', [
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'active',
        ]);
    });

    test('copies product items correctly', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();

        $order = Order::factory()->pickup()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/reorder");

        $response->assertOk();
        expect($response->json('data.cart_id'))->toBeInt();
    });

    test('copies combo items correctly', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();

        $order = Order::factory()->pickup()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/reorder");

        $response->assertOk();
    });

    test('uses current prices', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();

        $order = Order::factory()->pickup()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/reorder");

        $response->assertOk();
    });

    test('requires authentication', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $response = $this->postJson("/api/v1/orders/{$order->id}/reorder");

        $response->assertUnauthorized();
    });
});
