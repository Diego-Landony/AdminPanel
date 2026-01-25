<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\ComboItem;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Section;
use App\Models\Menu\SectionOption;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('show (GET /api/v1/cart)', function () {
    test('returns empty cart for new customer', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/cart');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'restaurant',
                    'service_type',
                    'zone',
                    'items',
                    'summary',
                ],
            ]);

        expect($response->json('data.items'))->toBeArray()->toBeEmpty();
        expect($response->json('data.summary.subtotal'))->toBe('0.00');
    });

    test('returns cart with items', function () {
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
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50.00,
            'subtotal' => 100.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/cart');

        $response->assertOk();
        expect($response->json('data.items'))->toHaveCount(1);
        expect($response->json('data.summary.subtotal'))->toBe('100.00');
    });

    test('requires authentication', function () {
        $response = $this->getJson('/api/v1/cart');

        $response->assertUnauthorized();
    });
});

describe('addItem (POST /api/v1/cart/items)', function () {
    test('adds product to cart', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'has_variants' => false,
            'precio_pickup_capital' => 50.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $response->assertCreated();
        expect($response->json('data.item.quantity'))->toBe(2);
        expect($response->json('data.item.unit_price'))->toEqual(50.00);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    });

    test('adds product with variant to cart', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create([
            'is_active' => true,
            'uses_variants' => true,
        ]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'has_variants' => true,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
            'precio_pickup_capital' => 75.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $response->assertCreated();
        expect($response->json('data.item.unit_price'))->toEqual(75.00);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
        ]);
    });

    test('adds combo to cart', function () {
        $customer = Customer::factory()->create();
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
            'is_choice_group' => false,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'combo_id' => $combo->id,
                'quantity' => 1,
            ]);

        $response->assertCreated();
        expect($response->json('data.item.unit_price'))->toEqual(100.00);

        $this->assertDatabaseHas('cart_items', [
            'combo_id' => $combo->id,
        ]);
    });

    test('adds product with selected options', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 50.00,
        ]);
        $section = Section::factory()->create();
        $product->sections()->attach($section->id);
        $option = SectionOption::factory()->create([
            'section_id' => $section->id,
            'price_modifier' => 8.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
                'selected_options' => [
                    [
                        'section_id' => $section->id,
                        'option_id' => $option->id,
                        'name' => $option->name,
                        'price' => 8.00,
                    ],
                ],
            ]);

        $response->assertCreated();
        expect($response->json('data.item.selected_options'))->toHaveCount(1);
    });

    test('calculates correct prices based on cart zone and service_type', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 45.00,
            'precio_domicilio_interior' => 48.00,
        ]);

        // Create cart with delivery/interior
        Cart::factory()->create([
            'customer_id' => $customer->id,
            'service_type' => 'delivery',
            'zone' => 'interior',
            'status' => 'active',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ]);

        $response->assertCreated();
        expect($response->json('data.item.unit_price'))->toEqual(48.00);
    });

    test('validates required fields', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/cart/items', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    });

    test('validates product exists', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => 999999,
                'quantity' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    });

    test('requires authentication', function () {
        $response = $this->postJson('/api/v1/cart/items', []);

        $response->assertUnauthorized();
    });
});

describe('updateItem (PUT /api/v1/cart/items/{id})', function () {
    test('updates item quantity', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 50.00,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'service_type' => 'pickup',
            'zone' => 'capital',
        ]);

        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'subtotal' => 50.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->putJson("/api/v1/cart/items/{$item->id}", [
                'quantity' => 3,
            ]);

        $response->assertOk();
        expect($response->json('data.item.quantity'))->toBe(3);
        expect($response->json('data.item.subtotal'))->toEqual(150.00);
    });

    test('updates item options', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 50.00,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'service_type' => 'pickup',
            'zone' => 'capital',
        ]);

        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'subtotal' => 50.00,
        ]);

        $newOptions = [
            ['section_id' => 1, 'option_id' => 5, 'name' => 'Extra Queso', 'price' => 8.00],
        ];

        $response = actingAs($customer, 'sanctum')
            ->putJson("/api/v1/cart/items/{$item->id}", [
                'selected_options' => $newOptions,
            ]);

        $response->assertOk();
        expect($response->json('data.item.selected_options'))->toHaveCount(1);
    });

    test('recalculates subtotal on quantity change', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 50.00,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'service_type' => 'pickup',
            'zone' => 'capital',
        ]);

        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'subtotal' => 50.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->putJson("/api/v1/cart/items/{$item->id}", [
                'quantity' => 4,
            ]);

        $response->assertOk();
        expect($response->json('data.item.subtotal'))->toEqual(200.00);
    });

    test('returns 404 for non-existent item', function () {
        $customer = Customer::factory()->create();

        // Create a cart for the customer
        Cart::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->putJson('/api/v1/cart/items/999999', [
                'quantity' => 2,
            ]);

        $response->assertNotFound();
    });

    test('requires authentication', function () {
        $response = $this->putJson('/api/v1/cart/items/1', []);

        $response->assertUnauthorized();
    });
});

describe('removeItem (DELETE /api/v1/cart/items/{id})', function () {
    test('removes item from cart', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/cart/items/{$item->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);
    });

    test('returns 404 for non-existent item', function () {
        $customer = Customer::factory()->create();

        Cart::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->deleteJson('/api/v1/cart/items/999999');

        $response->assertNotFound();
    });

    test('requires authentication', function () {
        $response = $this->deleteJson('/api/v1/cart/items/1');

        $response->assertUnauthorized();
    });
});

describe('clear (DELETE /api/v1/cart)', function () {
    test('empties cart', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
        ]);

        CartItem::factory()->count(3)->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->deleteJson('/api/v1/cart');

        $response->assertOk();
        expect(CartItem::where('cart_id', $cart->id)->count())->toBe(0);
    });

    test('requires authentication', function () {
        $response = $this->deleteJson('/api/v1/cart');

        $response->assertUnauthorized();
    });
});

describe('updateServiceType (PUT /api/v1/cart/service-type)', function () {
    test('clears restaurant when changing from delivery to pickup', function () {
        $customer = Customer::factory()->create();

        $restaurant = Restaurant::factory()->create([
            'is_active' => true,
            'price_location' => 'interior',
        ]);

        Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'delivery',
            'zone' => 'capital',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->putJson('/api/v1/cart/service-type', [
                'service_type' => 'pickup',
            ]);

        $response->assertOk();
        expect($response->json('data.service_type'))->toBe('pickup');
        // Restaurant should be cleared when changing from delivery to pickup
        expect($response->json('data.restaurant'))->toBeNull();
        // Zone defaults to capital until user selects a restaurant
        expect($response->json('data.zone'))->toBe('capital');
    });

    test('keeps restaurant zone when already in pickup', function () {
        $customer = Customer::factory()->create();

        $restaurant = Restaurant::factory()->create([
            'is_active' => true,
            'price_location' => 'interior',
        ]);

        Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'pickup',
            'zone' => 'interior',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->putJson('/api/v1/cart/service-type', [
                'service_type' => 'pickup',
            ]);

        $response->assertOk();
        expect($response->json('data.service_type'))->toBe('pickup');
        expect($response->json('data.restaurant.id'))->toBe($restaurant->id);
        expect($response->json('data.zone'))->toBe('interior');
    });

    test('requires delivery address for delivery', function () {
        $customer = Customer::factory()->create();

        $restaurant = Restaurant::factory()->create([
            'is_active' => true,
        ]);

        Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'delivery_address_id' => null,
            'service_type' => 'pickup',
            'zone' => 'capital',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->putJson('/api/v1/cart/service-type', [
                'service_type' => 'delivery',
            ]);

        $response->assertStatus(422);
        expect($response->json('error_code'))->toBe('DELIVERY_ADDRESS_REQUIRED');
    });

    test('recalculates item prices with default zone when changing from delivery to pickup', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_pickup_capital' => 50.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 45.00,
            'precio_domicilio_interior' => 60.00,
        ]);

        $restaurant = Restaurant::factory()->create([
            'is_active' => true,
            'price_location' => 'interior',
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'delivery',
            'zone' => 'interior',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 60.00, // precio_domicilio_interior
            'subtotal' => 120.00,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->putJson('/api/v1/cart/service-type', [
                'service_type' => 'pickup',
            ]);

        $response->assertOk();
        // Restaurant is cleared, zone defaults to capital
        expect($response->json('data.restaurant'))->toBeNull();
        expect($response->json('data.zone'))->toBe('capital');

        $updatedItem = $response->json('data.items')[0] ?? null;
        expect($updatedItem)->not->toBeNull();
        // Price should be precio_pickup_capital = 50.00 (default zone)
        expect($updatedItem['unit_price'])->toEqual(50.00);
        expect($updatedItem['subtotal'])->toEqual(100.00);
    });

    test('validates service_type is required', function () {
        $customer = Customer::factory()->create();

        $restaurant = Restaurant::factory()->create([
            'is_active' => true,
        ]);

        Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->putJson('/api/v1/cart/service-type', [
                'service_type' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['service_type']);
    });

    test('requires authentication', function () {
        $response = $this->putJson('/api/v1/cart/service-type', [
            'service_type' => 'pickup',
        ]);

        $response->assertUnauthorized();
    });
});

describe('validate (POST /api/v1/cart/validate)', function () {
    test('returns valid for available items', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/cart/validate');

        $response->assertOk();
        expect($response->json('data.is_valid'))->toBeTrue();
        expect($response->json('data.errors'))->toBeEmpty();
    });

    test('returns errors for inactive products', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/cart/validate');

        $response->assertOk();
        expect($response->json('data.is_valid'))->toBeFalse();
        expect($response->json('data.errors'))->not->toBeEmpty();
    });

    test('requires authentication', function () {
        $response = $this->postJson('/api/v1/cart/validate');

        $response->assertUnauthorized();
    });
});
