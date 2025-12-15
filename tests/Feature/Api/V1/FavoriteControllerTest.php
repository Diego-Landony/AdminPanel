<?php

use App\Models\Customer;
use App\Models\Favorite;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('index (GET /api/v1/favorites)', function () {
    test('returns empty list when customer has no favorites', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/favorites');

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJson(['data' => []]);
    });

    test('returns customer favorites with loaded favorable data', function () {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['name' => 'Test Product']);
        $combo = Combo::factory()->create(['name' => 'Test Combo']);

        Favorite::factory()->create([
            'customer_id' => $customer->id,
            'favorable_type' => Product::class,
            'favorable_id' => $product->id,
        ]);

        Favorite::factory()->create([
            'customer_id' => $customer->id,
            'favorable_type' => Combo::class,
            'favorable_id' => $combo->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/favorites');

        $response->assertOk();
        $data = $response->json('data');

        expect($data)->toHaveCount(2);
        expect($data[0])->toHaveKeys(['id', 'favorable_type', 'favorable_id', 'favorable', 'created_at']);
    });

    test('requires authentication', function () {
        $response = $this->getJson('/api/v1/favorites');

        $response->assertUnauthorized();
    });
});

describe('store (POST /api/v1/favorites)', function () {
    test('creates new product favorite successfully', function () {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/favorites', [
                'favorable_type' => 'product',
                'favorable_id' => $product->id,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'favorable_type',
                    'favorable_id',
                    'favorable',
                    'created_at',
                ],
                'message',
            ]);

        expect($response->json('data.favorable_type'))->toBe('Product');
        expect($response->json('data.favorable_id'))->toBe($product->id);
    });

    test('creates new combo favorite successfully', function () {
        $customer = Customer::factory()->create();
        $combo = Combo::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/favorites', [
                'favorable_type' => 'combo',
                'favorable_id' => $combo->id,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'favorable_type',
                    'favorable_id',
                    'favorable',
                    'created_at',
                ],
                'message',
            ]);

        expect($response->json('data.favorable_type'))->toBe('Combo');
        expect($response->json('data.favorable_id'))->toBe($combo->id);
    });

    test('returns existing favorite when adding duplicate', function () {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        Favorite::factory()->create([
            'customer_id' => $customer->id,
            'favorable_type' => Product::class,
            'favorable_id' => $product->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/favorites', [
                'favorable_type' => 'product',
                'favorable_id' => $product->id,
            ]);

        $response->assertOk()
            ->assertJson(['message' => 'Este item ya está en tus favoritos']);

        expect(Favorite::count())->toBe(1);
    });

    test('requires favorable_type field', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/favorites', [
                'favorable_id' => 1,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['favorable_type']);
    });

    test('requires favorable_id field', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/favorites', [
                'favorable_type' => 'product',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['favorable_id']);
    });

    test('validates favorable_type must be product or combo', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/favorites', [
                'favorable_type' => 'invalid',
                'favorable_id' => 1,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['favorable_type']);
    });

    test('validates product exists', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/favorites', [
                'favorable_type' => 'product',
                'favorable_id' => 99999,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['favorable_id']);
    });

    test('validates combo exists', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/favorites', [
                'favorable_type' => 'combo',
                'favorable_id' => 99999,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['favorable_id']);
    });

    test('requires authentication', function () {
        $response = $this->postJson('/api/v1/favorites', [
            'favorable_type' => 'product',
            'favorable_id' => 1,
        ]);

        $response->assertUnauthorized();
    });
});

describe('destroy (DELETE /api/v1/favorites/{type}/{id})', function () {
    test('removes product favorite successfully', function () {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        Favorite::factory()->create([
            'customer_id' => $customer->id,
            'favorable_type' => Product::class,
            'favorable_id' => $product->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/favorites/product/{$product->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Removido de favoritos exitosamente']);

        expect(Favorite::count())->toBe(0);
    });

    test('removes combo favorite successfully', function () {
        $customer = Customer::factory()->create();
        $combo = Combo::factory()->create();

        Favorite::factory()->create([
            'customer_id' => $customer->id,
            'favorable_type' => Combo::class,
            'favorable_id' => $combo->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/favorites/combo/{$combo->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Removido de favoritos exitosamente']);

        expect(Favorite::count())->toBe(0);
    });

    test('returns 404 when favorite not found', function () {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/favorites/product/{$product->id}");

        $response->assertNotFound();
    });

    test('prevents removing other customer favorites', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $product = Product::factory()->create();

        Favorite::factory()->create([
            'customer_id' => $customer2->id,
            'favorable_type' => Product::class,
            'favorable_id' => $product->id,
        ]);

        $response = actingAs($customer1, 'sanctum')
            ->deleteJson("/api/v1/favorites/product/{$product->id}");

        $response->assertNotFound();
        expect(Favorite::count())->toBe(1);
    });

    test('validates type parameter', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->deleteJson('/api/v1/favorites/invalid/1');

        $response->assertUnprocessable();
    });

    test('requires authentication', function () {
        $response = $this->deleteJson('/api/v1/favorites/product/1');

        $response->assertUnauthorized();
    });
});
