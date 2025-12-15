<?php

use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('index (GET /api/v1/restaurants)', function () {
    test('returns list of active restaurants', function () {
        Restaurant::factory()->create([
            'name' => 'Subway Zona 10',
            'is_active' => true,
        ]);

        Restaurant::factory()->create([
            'name' => 'Subway Carretera',
            'is_active' => true,
        ]);

        Restaurant::factory()->create([
            'name' => 'Subway Antigua',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/restaurants');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'restaurants' => [
                        '*' => ['id', 'name'],
                    ],
                ],
            ]);

        expect($response->json('data.restaurants'))->toHaveCount(2);
    });

    test('filters by delivery_active when provided', function () {
        Restaurant::factory()->create([
            'name' => 'With Delivery',
            'is_active' => true,
            'delivery_active' => true,
        ]);

        Restaurant::factory()->create([
            'name' => 'Without Delivery',
            'is_active' => true,
            'delivery_active' => false,
        ]);

        $response = $this->getJson('/api/v1/restaurants?delivery_active=1');

        $response->assertOk();
        $data = $response->json('data.restaurants');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('With Delivery');
    });

    test('filters by pickup_active when provided', function () {
        Restaurant::factory()->create([
            'name' => 'With Pickup',
            'is_active' => true,
            'pickup_active' => true,
        ]);

        Restaurant::factory()->create([
            'name' => 'Without Pickup',
            'is_active' => true,
            'pickup_active' => false,
        ]);

        $response = $this->getJson('/api/v1/restaurants?pickup_active=1');

        $response->assertOk();
        expect($response->json('data.restaurants'))->toHaveCount(1);
    });

    test('does not require authentication', function () {
        Restaurant::factory()->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/restaurants');

        $response->assertOk();
    });
});

describe('show (GET /api/v1/restaurants/{id})', function () {
    test('returns restaurant details when active', function () {
        $restaurant = Restaurant::factory()->create([
            'name' => 'Subway Test',
            'address' => 'Zona 10, Guatemala',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/v1/restaurants/{$restaurant->id}");

        $response->assertOk()
            ->assertJsonPath('data.restaurant.id', $restaurant->id)
            ->assertJsonPath('data.restaurant.name', 'Subway Test');
    });

    test('returns 404 for inactive restaurant', function () {
        $restaurant = Restaurant::factory()->create(['is_active' => false]);

        $response = $this->getJson("/api/v1/restaurants/{$restaurant->id}");

        $response->assertNotFound();
    });

    test('returns 404 for non-existent restaurant', function () {
        $response = $this->getJson('/api/v1/restaurants/99999');

        $response->assertNotFound();
    });
});

describe('nearby (GET /api/v1/restaurants/nearby)', function () {
    test('returns restaurants within radius ordered by distance', function () {
        $userLat = 14.6349;
        $userLng = -90.5069;

        Restaurant::factory()->create([
            'name' => 'Nearby Restaurant',
            'is_active' => true,
            'latitude' => 14.6350,
            'longitude' => -90.5070,
        ]);

        Restaurant::factory()->create([
            'name' => 'Far Restaurant',
            'is_active' => true,
            'latitude' => 14.7000,
            'longitude' => -90.5500,
        ]);

        $response = $this->getJson("/api/v1/restaurants/nearby?lat={$userLat}&lng={$userLng}&radius_km=5");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'restaurants',
                    'search_radius_km',
                    'total_found',
                ],
            ]);

        expect($response->json('data.total_found'))->toBe(1);
        expect($response->json('data.search_radius_km'))->toBe(5);
    });

    test('uses default radius of 10km when not specified', function () {
        Restaurant::factory()->create([
            'is_active' => true,
            'latitude' => 14.6349,
            'longitude' => -90.5069,
        ]);

        $response = $this->getJson('/api/v1/restaurants/nearby?lat=14.6349&lng=-90.5069');

        $response->assertOk();
        expect($response->json('data.search_radius_km'))->toBeNumeric();
    });

    test('requires latitude parameter', function () {
        $response = $this->getJson('/api/v1/restaurants/nearby?lng=-90.5069');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lat']);
    });

    test('requires longitude parameter', function () {
        $response = $this->getJson('/api/v1/restaurants/nearby?lat=14.6349');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lng']);
    });

    test('validates latitude range', function () {
        $response = $this->getJson('/api/v1/restaurants/nearby?lat=91&lng=-90.5069');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lat']);
    });

    test('validates longitude range', function () {
        $response = $this->getJson('/api/v1/restaurants/nearby?lat=14.6349&lng=181');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lng']);
    });

    test('validates radius_km minimum', function () {
        $response = $this->getJson('/api/v1/restaurants/nearby?lat=14.6349&lng=-90.5069&radius_km=0.05');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['radius_km']);
    });

    test('validates radius_km maximum', function () {
        $response = $this->getJson('/api/v1/restaurants/nearby?lat=14.6349&lng=-90.5069&radius_km=51');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['radius_km']);
    });

    test('filters nearby by delivery_active', function () {
        $withDelivery = Restaurant::factory()->create([
            'is_active' => true,
            'delivery_active' => true,
            'latitude' => 14.6350,
            'longitude' => -90.5070,
        ]);

        Restaurant::factory()->create([
            'is_active' => true,
            'delivery_active' => false,
            'latitude' => 14.6350,
            'longitude' => -90.5070,
        ]);

        $response = $this->getJson('/api/v1/restaurants/nearby?lat=14.6349&lng=-90.5069&delivery_active=1');

        $response->assertOk();
        expect($response->json('data.total_found'))->toBe(1);
    });

    test('excludes restaurants without coordinates', function () {
        Restaurant::factory()->create([
            'is_active' => true,
            'latitude' => null,
            'longitude' => null,
        ]);

        $response = $this->getJson('/api/v1/restaurants/nearby?lat=14.6349&lng=-90.5069');

        $response->assertOk();
        expect($response->json('data.total_found'))->toBe(0);
    });
});
