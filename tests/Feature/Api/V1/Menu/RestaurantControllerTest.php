<?php

use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('index', function () {
    test('returns active restaurants', function () {
        Restaurant::factory()->count(3)->create(['is_active' => true]);
        Restaurant::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/restaurants');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'restaurants' => [
                        '*' => [
                            'id',
                            'name',
                            'address',
                            'phone',
                            'email',
                            'latitude',
                            'longitude',
                            'is_active',
                            'delivery_active',
                            'pickup_active',
                            'schedule',
                            'estimated_delivery_time',
                            'minimum_order_amount',
                            'has_geofence',
                            'is_open_now',
                            'today_schedule',
                            'status_text',
                        ],
                    ],
                ],
            ]);

        expect($response->json('data.restaurants'))->toHaveCount(3);
    });

    test('filters by delivery active', function () {
        Restaurant::factory()->count(2)->create([
            'is_active' => true,
            'delivery_active' => true,
            'geofence_kml' => '<kml>test</kml>',
        ]);
        Restaurant::factory()->create([
            'is_active' => true,
            'delivery_active' => false,
        ]);

        $response = $this->getJson('/api/v1/restaurants?delivery_active=1');

        $response->assertOk();
        expect($response->json('data.restaurants'))->toHaveCount(2);
    });

    test('filters by pickup active', function () {
        Restaurant::factory()->count(2)->create([
            'is_active' => true,
            'pickup_active' => true,
        ]);
        Restaurant::factory()->create([
            'is_active' => true,
            'pickup_active' => false,
        ]);

        $response = $this->getJson('/api/v1/restaurants?pickup_active=1');

        $response->assertOk();
        expect($response->json('data.restaurants'))->toHaveCount(2);
    });

    test('filters by both delivery and pickup active', function () {
        Restaurant::factory()->create([
            'is_active' => true,
            'delivery_active' => true,
            'pickup_active' => true,
            'geofence_kml' => '<kml>test</kml>',
        ]);
        Restaurant::factory()->create([
            'is_active' => true,
            'delivery_active' => true,
            'pickup_active' => false,
            'geofence_kml' => '<kml>test</kml>',
        ]);
        Restaurant::factory()->create([
            'is_active' => true,
            'delivery_active' => false,
            'pickup_active' => true,
        ]);

        $response = $this->getJson('/api/v1/restaurants?delivery_active=1&pickup_active=1');

        $response->assertOk();
        expect($response->json('data.restaurants'))->toHaveCount(1);
    });

    test('orders restaurants by name', function () {
        Restaurant::factory()->create(['name' => 'Zebra Restaurant', 'is_active' => true]);
        Restaurant::factory()->create(['name' => 'Alpha Restaurant', 'is_active' => true]);
        Restaurant::factory()->create(['name' => 'Beta Restaurant', 'is_active' => true]);

        $response = $this->getJson('/api/v1/restaurants');

        $response->assertOk();
        $restaurants = $response->json('data.restaurants');

        expect($restaurants[0]['name'])->toBe('Alpha Restaurant');
        expect($restaurants[1]['name'])->toBe('Beta Restaurant');
        expect($restaurants[2]['name'])->toBe('Zebra Restaurant');
    });
});

describe('show', function () {
    test('returns restaurant details', function () {
        $restaurant = Restaurant::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/v1/restaurants/{$restaurant->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'restaurant' => [
                        'id',
                        'name',
                        'address',
                        'phone',
                        'email',
                        'latitude',
                        'longitude',
                        'is_active',
                        'delivery_active',
                        'pickup_active',
                        'schedule',
                        'estimated_delivery_time',
                        'minimum_order_amount',
                        'has_geofence',
                        'is_open_now',
                        'today_schedule',
                        'status_text',
                    ],
                ],
            ]);

        expect($response->json('data.restaurant.id'))->toBe($restaurant->id);
        expect($response->json('data.restaurant.name'))->toBe($restaurant->name);
    });

    test('returns 404 for inactive restaurant', function () {
        $restaurant = Restaurant::factory()->create(['is_active' => false]);

        $response = $this->getJson("/api/v1/restaurants/{$restaurant->id}");

        $response->assertNotFound();
    });

    test('returns 404 for non-existent restaurant', function () {
        $response = $this->getJson('/api/v1/restaurants/999999');

        $response->assertNotFound();
    });
});

describe('nearby', function () {
    test('returns restaurants within radius', function () {
        // Guatemala City center coordinates
        $userLat = 14.6349;
        $userLng = -90.5069;

        // Create restaurant near the center (14.64, -90.51) - approximately 0.5km away
        Restaurant::factory()->create([
            'name' => 'Nearby Restaurant',
            'is_active' => true,
            'latitude' => 14.640,
            'longitude' => -90.510,
        ]);

        // Create restaurant far away (14.50, -90.40) - approximately 20km away
        Restaurant::factory()->create([
            'name' => 'Far Restaurant',
            'is_active' => true,
            'latitude' => 14.50,
            'longitude' => -90.40,
        ]);

        $response = $this->getJson("/api/v1/restaurants/nearby?lat={$userLat}&lng={$userLng}&radius_km=5");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'restaurants' => [
                        '*' => [
                            'id',
                            'name',
                            'distance_km',
                        ],
                    ],
                    'search_radius_km',
                    'total_found',
                ],
            ]);

        expect($response->json('data.total_found'))->toBe(1);
        expect($response->json('data.search_radius_km'))->toBe(5);
        expect($response->json('data.restaurants.0.name'))->toBe('Nearby Restaurant');
        expect($response->json('data.restaurants.0.distance_km'))->toBeLessThan(5);
    });

    test('orders results by distance ascending', function () {
        $userLat = 14.6349;
        $userLng = -90.5069;

        Restaurant::factory()->create([
            'name' => 'Far Restaurant',
            'is_active' => true,
            'latitude' => 14.645,
            'longitude' => -90.520,
        ]);

        Restaurant::factory()->create([
            'name' => 'Near Restaurant',
            'is_active' => true,
            'latitude' => 14.635,
            'longitude' => -90.507,
        ]);

        Restaurant::factory()->create([
            'name' => 'Middle Restaurant',
            'is_active' => true,
            'latitude' => 14.640,
            'longitude' => -90.510,
        ]);

        $response = $this->getJson("/api/v1/restaurants/nearby?lat={$userLat}&lng={$userLng}&radius_km=10");

        $response->assertOk();
        $restaurants = $response->json('data.restaurants');

        expect($restaurants[0]['name'])->toBe('Near Restaurant');
        expect($restaurants[0]['distance_km'])->toBeLessThan($restaurants[1]['distance_km']);
        expect($restaurants[1]['distance_km'])->toBeLessThan($restaurants[2]['distance_km']);
    });

    test('uses default radius of 10km when not specified', function () {
        $userLat = 14.6349;
        $userLng = -90.5069;

        Restaurant::factory()->create([
            'is_active' => true,
            'latitude' => 14.640,
            'longitude' => -90.510,
        ]);

        $response = $this->getJson("/api/v1/restaurants/nearby?lat={$userLat}&lng={$userLng}");

        $response->assertOk();
        expect($response->json('data.search_radius_km'))->toBe(10);
    });

    test('filters by delivery active', function () {
        $userLat = 14.6349;
        $userLng = -90.5069;

        Restaurant::factory()->create([
            'name' => 'Delivery Restaurant',
            'is_active' => true,
            'delivery_active' => true,
            'geofence_kml' => '<kml>test</kml>',
            'latitude' => 14.640,
            'longitude' => -90.510,
        ]);

        Restaurant::factory()->create([
            'name' => 'No Delivery Restaurant',
            'is_active' => true,
            'delivery_active' => false,
            'latitude' => 14.641,
            'longitude' => -90.511,
        ]);

        $response = $this->getJson("/api/v1/restaurants/nearby?lat={$userLat}&lng={$userLng}&radius_km=5&delivery_active=1");

        $response->assertOk();
        expect($response->json('data.total_found'))->toBe(1);
        expect($response->json('data.restaurants.0.name'))->toBe('Delivery Restaurant');
    });

    test('filters by pickup active', function () {
        $userLat = 14.6349;
        $userLng = -90.5069;

        Restaurant::factory()->create([
            'name' => 'Pickup Restaurant',
            'is_active' => true,
            'pickup_active' => true,
            'latitude' => 14.640,
            'longitude' => -90.510,
        ]);

        Restaurant::factory()->create([
            'name' => 'No Pickup Restaurant',
            'is_active' => true,
            'pickup_active' => false,
            'latitude' => 14.641,
            'longitude' => -90.511,
        ]);

        $response = $this->getJson("/api/v1/restaurants/nearby?lat={$userLat}&lng={$userLng}&radius_km=5&pickup_active=1");

        $response->assertOk();
        expect($response->json('data.total_found'))->toBe(1);
        expect($response->json('data.restaurants.0.name'))->toBe('Pickup Restaurant');
    });

    test('excludes restaurants without coordinates', function () {
        $userLat = 14.6349;
        $userLng = -90.5069;

        Restaurant::factory()->create([
            'name' => 'With Coordinates',
            'is_active' => true,
            'latitude' => 14.640,
            'longitude' => -90.510,
        ]);

        Restaurant::factory()->create([
            'name' => 'Without Coordinates',
            'is_active' => true,
            'latitude' => null,
            'longitude' => null,
        ]);

        $response = $this->getJson("/api/v1/restaurants/nearby?lat={$userLat}&lng={$userLng}&radius_km=10");

        $response->assertOk();
        expect($response->json('data.total_found'))->toBe(1);
        expect($response->json('data.restaurants.0.name'))->toBe('With Coordinates');
    });

    test('requires latitude parameter', function () {
        $response = $this->getJson('/api/v1/restaurants/nearby?lng=-90.5069');

        $response->assertUnprocessable()
            ->assertJson([
                'error' => 'location_required',
            ]);
    });

    test('requires longitude parameter', function () {
        $response = $this->getJson('/api/v1/restaurants/nearby?lat=14.6349');

        $response->assertUnprocessable()
            ->assertJson([
                'error' => 'location_required',
            ]);
    });

    test('validates latitude range', function () {
        $response = $this->getJson('/api/v1/restaurants/nearby?lat=100&lng=-90.5069');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lat']);
    });

    test('validates longitude range', function () {
        $response = $this->getJson('/api/v1/restaurants/nearby?lat=14.6349&lng=200');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lng']);
    });

    test('validates maximum radius', function () {
        $response = $this->getJson('/api/v1/restaurants/nearby?lat=14.6349&lng=-90.5069&radius_km=100');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['radius_km']);
    });
});
