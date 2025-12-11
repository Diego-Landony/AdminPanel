<?php

use App\Http\Resources\Api\V1\Cart\CartItemResource;
use App\Http\Resources\Api\V1\Cart\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CartResource', function () {
    test('transforms cart with basic fields', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create();

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'delivery',
            'zone' => 'capital',
            'expires_at' => now()->addHours(2),
        ]);

        $resource = new CartResource($cart);
        $data = $resource->toArray(request());

        expect($data)->toHaveKeys([
            'id',
            'service_type',
            'zone',
            'expires_at',
            'created_at',
        ]);

        expect($data['id'])->toBe($cart->id);
        expect($data['service_type'])->toBe('delivery');
        expect($data['zone'])->toBe('capital');
    });

    test('includes restaurant when loaded', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'name' => 'Test Restaurant',
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
        ]);

        $cart->load('restaurant');

        $resource = new CartResource($cart);
        $data = $resource->toArray(request());

        expect($data)->toHaveKey('restaurant');
        expect($data['restaurant'])->not->toBeNull();
        expect($data['restaurant']->resource->name)->toBe('Test Restaurant');
    });

    test('includes items when loaded', function () {
        $customer = Customer::factory()->create();
        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $product = Product::factory()->create();

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10.50,
            'subtotal' => 21.00,
        ]);

        $cart->load('items');

        $resource = new CartResource($cart);
        $data = $resource->toArray(request());

        expect($data)->toHaveKey('items');
        expect($data['items'])->not->toBeNull();
        expect($data['items']->collection)->toHaveCount(1);
    });
});

describe('CartItemResource', function () {
    test('transforms product cart item', function () {
        $customer = Customer::factory()->create();
        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $product = Product::factory()->create([
            'name' => 'Test Product',
            'image' => 'product.jpg',
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '30cm',
        ]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 15.50,
            'subtotal' => 31.00,
            'notes' => 'Extra cheese',
        ]);

        $cartItem->load(['product', 'variant']);

        $resource = new CartItemResource($cartItem);
        $data = $resource->toArray(request());

        expect($data['type'])->toBe('product');
        expect($data['quantity'])->toBe(2);
        expect($data['unit_price'])->toBe(15.50);
        expect($data['subtotal'])->toBe(31.00);
        expect($data['notes'])->toBe('Extra cheese');
        expect($data['product'])->toBeArray();
        expect($data['product']['name'])->toBe('Test Product');
        expect($data['product']['variant']['name'])->toBe('30cm');
    });

    test('transforms combo cart item', function () {
        $customer = Customer::factory()->create();
        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $combo = Combo::factory()->create([
            'name' => 'Test Combo',
            'image' => 'combo.jpg',
        ]);

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => null,
            'combo_id' => $combo->id,
            'quantity' => 1,
            'unit_price' => 25.00,
            'subtotal' => 25.00,
        ]);

        $cartItem->load('combo');

        $resource = new CartItemResource($cartItem);
        $data = $resource->toArray(request());

        expect($data['type'])->toBe('combo');
        expect($data['combo'])->toBeArray();
        expect($data['combo']['name'])->toBe('Test Combo');
    });

    test('formats selected options correctly', function () {
        $customer = Customer::factory()->create();
        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $product = Product::factory()->create();

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10.00,
            'subtotal' => 10.00,
            'selected_options' => [
                [
                    'section_id' => 1,
                    'option_id' => 2,
                    'name' => 'Extra Cheese',
                    'price' => 2.50,
                ],
                [
                    'section_id' => 2,
                    'option_id' => 5,
                    'name' => 'Bacon',
                    'price' => 3.00,
                ],
            ],
        ]);

        $resource = new CartItemResource($cartItem);
        $data = $resource->toArray(request());

        expect($data['selected_options'])->toBeArray();
        expect($data['selected_options'])->toHaveCount(2);
        expect($data['selected_options'][0]['name'])->toBe('Extra Cheese');
        expect($data['selected_options'][0]['price'])->toBe(2.50);
        expect($data['selected_options'][1]['name'])->toBe('Bacon');
        expect($data['selected_options'][1]['price'])->toBe(3.00);
    });

    test('handles empty selected options', function () {
        $customer = Customer::factory()->create();
        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $product = Product::factory()->create();

        $cartItem = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10.00,
            'subtotal' => 10.00,
            'selected_options' => null,
        ]);

        $resource = new CartItemResource($cartItem);
        $data = $resource->toArray(request());

        expect($data['selected_options'])->toBeArray();
        expect($data['selected_options'])->toBeEmpty();
    });
});
