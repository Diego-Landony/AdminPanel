<?php

use App\Services\Geofence\KmlParserService;

describe('KmlParserService', function () {
    beforeEach(function () {
        $this->service = new KmlParserService;
    });

    describe('valid KML parsing', function () {
        test('parses simple KML with polygon coordinates', function () {
            $kml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Placemark>
    <Polygon>
      <outerBoundaryIs>
        <LinearRing>
          <coordinates>
            -90.5150,14.6450,0 -90.5050,14.6450,0 -90.5050,14.6350,0 -90.5150,14.6350,0
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
</kml>
XML;

            $result = $this->service->parseToCoordinates($kml);

            expect($result)->toBeArray();
            expect($result)->toHaveCount(4);
            expect($result[0])->toHaveKeys(['lat', 'lng']);
        });

        test('correctly parses lng,lat format to lat,lng', function () {
            $kml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Placemark>
    <Polygon>
      <outerBoundaryIs>
        <LinearRing>
          <coordinates>-90.5100,14.6400,0</coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
</kml>
XML;

            $result = $this->service->parseToCoordinates($kml);

            expect($result[0]['lat'])->toBe(14.6400);
            expect($result[0]['lng'])->toBe(-90.5100);
        });

        test('handles newlines in coordinates', function () {
            $kml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Placemark>
    <Polygon>
      <outerBoundaryIs>
        <LinearRing>
          <coordinates>
            -90.5150,14.6450,0
            -90.5050,14.6450,0
            -90.5050,14.6350,0
          </coordinates>
        </LinearRing>
      </outerBoundaryIs>
    </Polygon>
  </Placemark>
</kml>
XML;

            $result = $this->service->parseToCoordinates($kml);

            expect($result)->toHaveCount(3);
        });
    });

    describe('edge cases', function () {
        test('returns empty array for KML without coordinates', function () {
            $kml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Placemark>
    <name>Test</name>
  </Placemark>
</kml>
XML;

            $result = $this->service->parseToCoordinates($kml);

            expect($result)->toBeArray()->toBeEmpty();
        });
    });
});
