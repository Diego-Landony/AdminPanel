<?php

use App\Models\Restaurant;
use App\Services\DeliveryValidationResult;
use App\Services\DeliveryValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DeliveryValidationService', function () {
    beforeEach(function () {
        $this->service = app(DeliveryValidationService::class);
    });

    describe('validateCoordinates', function () {
        test('returns valid result when restaurant can deliver', function () {
            $kml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Placemark>
    <Polygon>
      <outerBoundaryIs>
        <LinearRing>
          <coordinates>
            -90.5200,14.6500,0 -90.5000,14.6500,0 -90.5000,14.6300,0 -90.5200,14.6300,0
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
</kml>
XML;

            $restaurant = Restaurant::factory()->create([
                'is_active' => true,
                'delivery_active' => true,
                'geofence_kml' => $kml,
                'price_location' => 'capital',
            ]);

            $result = $this->service->validateCoordinates(14.6400, -90.5100);

            expect($result)->toBeInstanceOf(DeliveryValidationResult::class);
            expect($result->isValid)->toBeTrue();
            expect($result->restaurant)->not->toBeNull();
            expect($result->zone)->toBe('capital');
        });

        test('returns invalid with error message when no coverage', function () {
            $result = $this->service->validateCoordinates(15.0, -91.0);

            expect($result->isValid)->toBeFalse();
            expect($result->restaurant)->toBeNull();
            expect($result->errorMessage)->toBe('No tenemos cobertura de delivery en esta ubicación');
        });

        test('includes nearby pickup restaurants when delivery unavailable', function () {
            Restaurant::factory()->create([
                'name' => 'Subway Pickup',
                'is_active' => true,
                'pickup_active' => true,
                'delivery_active' => false,
                'latitude' => 14.6405,
                'longitude' => -90.5105,
            ]);

            $result = $this->service->validateCoordinates(14.6400, -90.5100);

            expect($result->isValid)->toBeFalse();
            expect($result->nearbyPickupRestaurants)->not->toBeEmpty();
        });

        test('limits nearby restaurants to 3', function () {
            for ($i = 0; $i < 5; $i++) {
                Restaurant::factory()->create([
                    'is_active' => true,
                    'pickup_active' => true,
                    'latitude' => 14.6400 + ($i * 0.001),
                    'longitude' => -90.5100,
                ]);
            }

            $result = $this->service->validateCoordinates(14.6400, -90.5100);

            expect($result->nearbyPickupRestaurants)->toHaveCount(3);
        });

        test('nearby restaurants include distance info', function () {
            Restaurant::factory()->create([
                'name' => 'Test Restaurant',
                'address' => 'Test Address',
                'is_active' => true,
                'pickup_active' => true,
                'latitude' => 14.6405,
                'longitude' => -90.5105,
            ]);

            $result = $this->service->validateCoordinates(14.6400, -90.5100);

            expect($result->nearbyPickupRestaurants[0])->toHaveKeys(['id', 'name', 'address', 'distance_km']);
        });

        test('sorts nearby restaurants by distance', function () {
            Restaurant::factory()->create([
                'name' => 'Far Restaurant',
                'is_active' => true,
                'pickup_active' => true,
                'latitude' => 14.6500,
                'longitude' => -90.5200,
            ]);

            Restaurant::factory()->create([
                'name' => 'Close Restaurant',
                'is_active' => true,
                'pickup_active' => true,
                'latitude' => 14.6401,
                'longitude' => -90.5101,
            ]);

            $result = $this->service->validateCoordinates(14.6400, -90.5100);

            expect($result->nearbyPickupRestaurants[0]['name'])->toBe('Close Restaurant');
        });

        test('excludes inactive restaurants from nearby', function () {
            Restaurant::factory()->create([
                'is_active' => false,
                'pickup_active' => true,
                'latitude' => 14.6405,
                'longitude' => -90.5105,
            ]);

            $result = $this->service->validateCoordinates(14.6400, -90.5100);

            expect($result->nearbyPickupRestaurants)->toBeEmpty();
        });

        test('excludes restaurants without pickup from nearby', function () {
            Restaurant::factory()->create([
                'is_active' => true,
                'pickup_active' => false,
                'delivery_active' => true,
                'latitude' => 14.6405,
                'longitude' => -90.5105,
            ]);

            $result = $this->service->validateCoordinates(14.6400, -90.5100);

            expect($result->nearbyPickupRestaurants)->toBeEmpty();
        });
    });
});
