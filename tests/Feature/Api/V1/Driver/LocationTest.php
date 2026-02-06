<?php

use App\Models\Driver;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Driver Location', function () {
    it('updates driver location successfully', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create([
            'current_latitude' => null,
            'current_longitude' => null,
            'last_location_update' => null,
        ]);

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 14.6349,
            'longitude' => -90.5069,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'latitude' => 14.6349,
                    'longitude' => -90.5069,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'latitude',
                    'longitude',
                    'updated_at',
                ],
                'message',
            ]);

        // Verify database was updated
        $driver->refresh();
        expect((float) $driver->current_latitude)->toBe(14.6349);
        expect((float) $driver->current_longitude)->toBe(-90.5069);
        expect($driver->last_location_update)->not->toBeNull();
    });

    it('accepts optional accuracy parameter', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 14.6349,
            'longitude' => -90.5069,
            'accuracy' => 10.5,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('accepts optional heading parameter', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 14.6349,
            'longitude' => -90.5069,
            'heading' => 180,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('accepts optional speed parameter', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 14.6349,
            'longitude' => -90.5069,
            'speed' => 25.5,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    });

    it('validates latitude is required', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'longitude' => -90.5069,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude']);
    });

    it('validates longitude is required', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 14.6349,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['longitude']);
    });

    it('validates latitude bounds - rejects latitude greater than 90', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 91,
            'longitude' => -90.5069,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude']);
    });

    it('validates latitude bounds - rejects latitude less than -90', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => -91,
            'longitude' => -90.5069,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude']);
    });

    it('validates longitude bounds - rejects longitude greater than 180', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 14.6349,
            'longitude' => 181,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['longitude']);
    });

    it('validates longitude bounds - rejects longitude less than -180', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 14.6349,
            'longitude' => -181,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['longitude']);
    });

    it('validates latitude must be numeric', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 'not-a-number',
            'longitude' => -90.5069,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude']);
    });

    it('validates longitude must be numeric', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 14.6349,
            'longitude' => 'not-a-number',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['longitude']);
    });

    it('validates accuracy must be non-negative', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 14.6349,
            'longitude' => -90.5069,
            'accuracy' => -5,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['accuracy']);
    });

    it('validates heading must be between 0 and 360', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 14.6349,
            'longitude' => -90.5069,
            'heading' => 400,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['heading']);
    });

    it('validates speed must be non-negative', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 14.6349,
            'longitude' => -90.5069,
            'speed' => -10,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['speed']);
    });

    it('rejects location update without authentication', function () {
        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 14.6349,
            'longitude' => -90.5069,
        ]);

        $response->assertUnauthorized();
    });

    it('rejects location update when driver is inactive', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->inactive()->create();

        // Create token while driver exists
        $token = $driver->createToken('test-token', ['driver']);

        $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/driver/location', [
                'latitude' => 14.6349,
                'longitude' => -90.5069,
            ]);

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'error_code' => 'DRIVER_INACTIVE',
            ]);
    });

    it('accepts edge case latitude values', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        // Test latitude = 90 (North Pole)
        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 90,
            'longitude' => 0,
        ]);

        $response->assertOk();

        // Test latitude = -90 (South Pole)
        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => -90,
            'longitude' => 0,
        ]);

        $response->assertOk();
    });

    it('accepts edge case longitude values', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        // Test longitude = 180
        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 0,
            'longitude' => 180,
        ]);

        $response->assertOk();

        // Test longitude = -180
        $response = $this->postJson('/api/v1/driver/location', [
            'latitude' => 0,
            'longitude' => -180,
        ]);

        $response->assertOk();
    });
});
