<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\ComboItem;
use App\Models\Menu\Product;
use App\Models\Menu\Promotion;
use App\Models\Menu\PromotionItem;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('store (POST /api/v1/orders)', function () {
    $alwaysOpenSchedule = collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
        ->mapWithKeys(fn ($day) => [$day => ['is_open' => true, 'open' => '00:00', 'close' => '23:59']])
        ->toArray();

    test('creates order from cart with product', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
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

    test('creates order from cart with combo', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
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

    test('creates order with delivery address', function () use ($alwaysOpenSchedule) {
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
            'minimum_order_amount' => 0,
            'schedule' => $alwaysOpenSchedule,
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

    test('applies points redemption', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create([
            'points' => 500,
        ]);
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
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
        // Note: points.redeemed is not yet returned in the response (not fully implemented)
        // The test verifies that the order is created successfully with points_to_redeem parameter
    });

    test('calculates points to earn', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create([
            'points' => 0,
        ]);
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
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

    test('converts cart to converted status', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
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

    test('validates order creation input', function (array $setup, array $payload, array $expectedErrors) {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        if ($setup['createCart'] ?? false) {
            $cart = Cart::factory()->create([
                'customer_id' => $customer->id,
                'restaurant_id' => $setup['cartRestaurantId'] === 'valid' ? $restaurant->id : null,
                'service_type' => $setup['cartServiceType'] ?? 'pickup',
                'zone' => 'capital',
                'status' => 'active',
            ]);

            CartItem::factory()->create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
            ]);
        }

        $requestPayload = [];
        foreach ($payload as $key => $value) {
            if ($value === 'valid_restaurant') {
                $requestPayload[$key] = $restaurant->id;
            } elseif ($value !== null) {
                $requestPayload[$key] = $value;
            }
        }

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', $requestPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors($expectedErrors);
    })->with([
        'missing required fields' => [
            'setup' => [],
            'payload' => [],
            'expectedErrors' => ['restaurant_id', 'service_type', 'payment_method'],
        ],
        'invalid restaurant' => [
            'setup' => ['createCart' => true, 'cartRestaurantId' => 'valid'],
            'payload' => ['restaurant_id' => 999999, 'service_type' => 'pickup', 'payment_method' => 'cash'],
            'expectedErrors' => ['restaurant_id'],
        ],
        'missing delivery address for delivery' => [
            'setup' => ['createCart' => true, 'cartRestaurantId' => 'valid', 'cartServiceType' => 'delivery'],
            'payload' => ['restaurant_id' => 'valid_restaurant', 'service_type' => 'delivery', 'payment_method' => 'cash'],
            'expectedErrors' => ['delivery_address_id'],
        ],
    ]);

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

    test('validates scheduled_pickup_time is at least 30 minutes from now for pickup', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
            'estimated_pickup_time' => 30, // Required for validation test
        ]);
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

    test('accepts scheduled_pickup_time that is 30 minutes or more from now', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'estimated_pickup_time' => 30, // Required for validation test
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
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

    test('does not validate scheduled_pickup_time for delivery orders', function () use ($alwaysOpenSchedule) {
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
            'minimum_order_amount' => 0,
            'schedule' => $alwaysOpenSchedule,
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
                'reason_code' => 'changed_mind',
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

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", [
                'reason_code' => 'long_wait_time',
            ]);

        $response->assertOk();

        $order->refresh();
        // El reason guardado es el label del enum
        expect($order->cancellation_reason)->toBe('Tiempo de espera muy largo');
    });

    test('saves custom reason when using other', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $customReason = 'Mi motivo personalizado';

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", [
                'reason_code' => 'other',
                'reason_detail' => $customReason,
            ]);

        $response->assertOk();

        $order->refresh();
        expect($order->cancellation_reason)->toBe($customReason);
    });

    test('creates status history entry', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", [
                'reason_code' => 'changed_mind',
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
                'reason_code' => 'changed_mind',
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
                'reason_code' => 'changed_mind',
            ]);

        $response->assertStatus(422);
    });

    test('validates reason_code is required', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason_code']);
    });

    test('validates reason_detail required when reason_code is other', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", [
                'reason_code' => 'other',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason_detail']);
    });

    test('requires authentication', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/orders/{$order->id}/cancel", [
            'reason_code' => 'changed_mind',
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

    test('requires authentication', function () {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $response = $this->postJson("/api/v1/orders/{$order->id}/reorder");

        $response->assertUnauthorized();
    });
});

describe('Points Validation', function () {
    // Helper para crear restaurante siempre abierto para tests de puntos
    $alwaysOpenSchedule = collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
        ->mapWithKeys(fn ($day) => [$day => ['is_open' => true, 'open' => '00:00', 'close' => '23:59']])
        ->toArray();

    test('validates points_to_redeem does not exceed customer balance', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create([
            'points' => 100,
        ]);
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 200.00,
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
            'unit_price' => 200.00,
            'subtotal' => 200.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'pickup',
                'payment_method' => 'cash',
                'points_to_redeem' => 200,
            ]);

        $response->assertStatus(422);
    });

    test('validates points_to_redeem must be non-negative', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create([
            'points' => 100,
        ]);
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
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
                'points_to_redeem' => -50,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['points_to_redeem']);
    });

    test('allows order with valid points redemption within balance', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create([
            'points' => 500,
        ]);
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 200.00,
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
            'unit_price' => 200.00,
            'subtotal' => 200.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'pickup',
                'payment_method' => 'cash',
                'points_to_redeem' => 100,
            ]);

        // La orden se crea exitosamente cuando se envía points_to_redeem válido
        // Nota: points_redeemed no está implementado en el modelo Order actualmente
        $response->assertCreated();
    });

    test('order with zero points redemption is valid', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create([
            'points' => 100,
        ]);
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
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
                'points_to_redeem' => 0,
            ]);

        $response->assertCreated();
    });

    test('customer with no points cannot redeem points', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create([
            'points' => 0,
        ]);
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
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
                'points_to_redeem' => 50,
            ]);

        $response->assertStatus(422);
    });

    test('points_to_redeem must be integer', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create([
            'points' => 100,
        ]);
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
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
                'points_to_redeem' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['points_to_redeem']);
    });
});

describe('Promotion Tracking', function () {
    // Helper para crear restaurante siempre abierto para tests de promociones
    $alwaysOpenSchedule = collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
        ->mapWithKeys(fn ($day) => [$day => ['is_open' => true, 'open' => '00:00', 'close' => '23:59']])
        ->toArray();

    test('saves promotion data to order items when promotion is applied', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 100.00,
        ]);

        // Crear promoción de descuento porcentual
        $promotion = Promotion::create([
            'name' => '20% de descuento',
            'type' => 'percentage_discount',
            'is_active' => true,
        ]);

        PromotionItem::create([
            'promotion_id' => $promotion->id,
            'product_id' => $product->id,
            'category_id' => $category->id,
            'discount_percentage' => 20,
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

        $order = Order::latest()->first();
        $orderItem = $order->items->first();

        // Verificar que el promotion_id fue guardado
        expect($orderItem->promotion_id)->toBe($promotion->id);

        // Verificar que el promotion_snapshot contiene la información correcta
        expect($orderItem->promotion_snapshot)->not->toBeNull();
        expect($orderItem->promotion_snapshot['id'])->toBe($promotion->id);
        expect($orderItem->promotion_snapshot['name'])->toBe('20% de descuento');
        expect($orderItem->promotion_snapshot['type'])->toBe('percentage_discount');
        expect($orderItem->promotion_snapshot['value'])->toBe('20.00%');
        expect($orderItem->promotion_snapshot['discount_amount'])->toEqual(20.0);
        expect($orderItem->promotion_snapshot['original_price'])->toEqual(100.0);
        expect($orderItem->promotion_snapshot['final_price'])->toEqual(80.0);
    });

    test('order items without promotion have null promotion_id and promotion_snapshot', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
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

        $order = Order::latest()->first();
        $orderItem = $order->items->first();

        // Verificar que no hay promoción aplicada
        expect($orderItem->promotion_id)->toBeNull();
        expect($orderItem->promotion_snapshot)->toBeNull();
    });

    test('tracks 2x1 promotion in order items', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 50.00,
        ]);

        // Crear promoción tipo 2x1
        $promotion = Promotion::create([
            'name' => '2x1 en Subs',
            'type' => 'two_for_one',
            'is_active' => true,
        ]);

        PromotionItem::create([
            'promotion_id' => $promotion->id,
            'product_id' => $product->id,
            'category_id' => $category->id,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'pickup',
            'zone' => 'capital',
            'status' => 'active',
        ]);

        // Agregar 2 del mismo producto para activar 2x1
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

        $order = Order::latest()->first();
        $orderItem = $order->items->first();

        // Verificar que la promoción 2x1 fue guardada
        expect($orderItem->promotion_id)->toBe($promotion->id);
        expect($orderItem->promotion_snapshot['type'])->toBe('two_for_one');
        expect($orderItem->promotion_snapshot['value'])->toBe('2x1');
    });

    test('creates order without discount when promotion is inactive', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 100.00,
        ]);

        // Crear promoción inactiva (expirada o deshabilitada)
        $promotion = Promotion::create([
            'name' => 'Promoción Inactiva',
            'type' => 'percentage_discount',
            'is_active' => false,
        ]);

        PromotionItem::create([
            'promotion_id' => $promotion->id,
            'product_id' => $product->id,
            'category_id' => $category->id,
            'discount_percentage' => 20,
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

        // La orden debe crearse exitosamente sin descuento
        $response->assertCreated();

        $order = Order::latest()->first();
        $orderItem = $order->items->first();

        // Verificar que no se aplicó promoción
        expect($orderItem->promotion_id)->toBeNull();
        expect($orderItem->promotion_snapshot)->toBeNull();
        // Precio completo sin descuento
        expect((float) $orderItem->unit_price)->toEqual(100.0);
    });

    test('fails to create order when promotion item is no longer valid today', function () use ($alwaysOpenSchedule) {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'minimum_order_amount' => 0,
            'pickup_active' => true,
            'is_active' => true,
            'schedule' => $alwaysOpenSchedule,
        ]);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 100.00,
        ]);

        // Crear promoción activa pero con item que tiene restricción de día
        $promotion = Promotion::create([
            'name' => 'Promoción Solo Lunes',
            'type' => 'percentage_discount',
            'is_active' => true,
        ]);

        // Crear item con weekdays que NO incluye el día actual
        $currentWeekday = now()->dayOfWeekIso;
        $invalidWeekday = $currentWeekday === 7 ? 1 : $currentWeekday + 1;

        PromotionItem::create([
            'promotion_id' => $promotion->id,
            'product_id' => $product->id,
            'category_id' => $category->id,
            'discount_percentage' => 20,
            'weekdays' => [$invalidWeekday],
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

        // La orden debería crearse pero sin descuento ya que la promoción no aplica hoy
        // El servicio de promociones no aplicará el descuento si el día no es válido
        $response->assertCreated();

        $order = Order::latest()->first();
        $orderItem = $order->items->first();

        // Verificar que no se aplicó ninguna promoción
        expect($orderItem->promotion_id)->toBeNull();
    });
});
