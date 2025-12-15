<?php

use App\Models\Restaurant;
use App\Services\Geofence\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GeofenceService', function () {
    beforeEach(function () {
        $this->service = app(GeofenceService::class);
    });

    describe('canRestaurantDeliverTo', function () {
        test('returns false when restaurant has no geofence', function () {
            $restaurant = Restaurant::factory()->create([
                'geofence_kml' => null,
            ]);

            $result = $this->service->canRestaurantDeliverTo($restaurant, 14.6400, -90.5100);

            expect($result)->toBeFalse();
        });

        test('returns true when point is inside geofence', function () {
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
                'geofence_kml' => $kml,
            ]);

            $result = $this->service->canRestaurantDeliverTo($restaurant, 14.6400, -90.5100);

            expect($result)->toBeTrue();
        });

        test('returns false when point is outside geofence', function () {
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
                'geofence_kml' => $kml,
            ]);

            $result = $this->service->canRestaurantDeliverTo($restaurant, 14.7000, -90.5100);

            expect($result)->toBeFalse();
        });
    });

    describe('getBestRestaurantForDelivery', function () {
        test('returns null when no restaurants can deliver', function () {
            $result = $this->service->getBestRestaurantForDelivery(14.6400, -90.5100);

            expect($result)->toBeNull();
        });

        test('returns restaurant that can deliver to location', function () {
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
            ]);

            $result = $this->service->getBestRestaurantForDelivery(14.6400, -90.5100);

            expect($result)->not->toBeNull();
            expect($result->id)->toBe($restaurant->id);
        });
    });
});
