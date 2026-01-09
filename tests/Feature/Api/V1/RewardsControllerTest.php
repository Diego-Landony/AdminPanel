<?php

use App\Models\Customer;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('rewards (GET /api/v1/rewards)', function () {
    test('returns redeemable products', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create();

        // Producto canjeable
        $redeemableProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Cookie de Chocolate',
            'is_active' => true,
            'is_redeemable' => true,
            'points_cost' => 50,
        ]);

        // Producto NO canjeable (no debe aparecer)
        Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'is_redeemable' => false,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/rewards');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'name',
                        'image',
                        'points_cost',
                    ],
                ],
            ]);

        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Cookie de Chocolate');
        expect($response->json('data.0.type'))->toBe('product');
        expect($response->json('data.0.points_cost'))->toBe(50);
    });

    test('returns redeemable combos', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create();

        // Combo canjeable
        $redeemableCombo = Combo::factory()->create([
            'category_id' => $category->id,
            'name' => 'Combo Navideño',
            'is_active' => true,
            'is_redeemable' => true,
            'points_cost' => 300,
        ]);

        // Combo NO canjeable (no debe aparecer)
        Combo::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'is_redeemable' => false,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/rewards');

        $response->assertOk();

        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Combo Navideño');
        expect($response->json('data.0.type'))->toBe('combo');
        expect($response->json('data.0.points_cost'))->toBe(300);
    });

    test('returns products with redeemable variants', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create(['uses_variants' => true]);

        // Producto con variantes canjeables
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Subway Pollo Teriyaki',
            'is_active' => true,
            'is_redeemable' => false, // El producto base NO es canjeable
        ]);

        // Variante canjeable
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '15cm',
            'is_active' => true,
            'is_redeemable' => true,
            'points_cost' => 150,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '30cm',
            'is_active' => true,
            'is_redeemable' => true,
            'points_cost' => 250,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/rewards');

        $response->assertOk();

        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Subway Pollo Teriyaki');
        expect($response->json('data.0.points_cost'))->toBeNull();
        expect($response->json('data.0.variants'))->toHaveCount(2);

        // Verificar que ambas variantes existen (sin depender del orden)
        $variantNames = collect($response->json('data.0.variants'))->pluck('name')->toArray();
        expect($variantNames)->toContain('15cm');
        expect($variantNames)->toContain('30cm');
    });

    test('does not return inactive products', function () {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create();

        // Producto canjeable pero inactivo
        Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
            'is_redeemable' => true,
            'points_cost' => 50,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/rewards');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(0);
    });

    test('returns empty array when no redeemable items exist', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/rewards');

        $response->assertOk()
            ->assertJson([
                'data' => [],
            ]);
    });

    test('requires authentication', function () {
        $response = $this->getJson('/api/v1/rewards');

        $response->assertUnauthorized();
    });
});
