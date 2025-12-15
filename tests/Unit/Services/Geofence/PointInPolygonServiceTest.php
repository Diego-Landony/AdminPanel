<?php

use App\Services\Geofence\PointInPolygonService;

describe('PointInPolygonService', function () {
    beforeEach(function () {
        $this->service = new PointInPolygonService;
    });

    describe('simple square polygon', function () {
        beforeEach(function () {
            $this->squarePolygon = [
                ['lat' => 14.6450, 'lng' => -90.5150],
                ['lat' => 14.6450, 'lng' => -90.5050],
                ['lat' => 14.6350, 'lng' => -90.5050],
                ['lat' => 14.6350, 'lng' => -90.5150],
            ];
        });

        test('point inside square returns true', function () {
            $result = $this->service->isPointInPolygon(14.6400, -90.5100, $this->squarePolygon);

            expect($result)->toBeTrue();
        });

        test('point outside north returns false', function () {
            $result = $this->service->isPointInPolygon(14.6500, -90.5100, $this->squarePolygon);

            expect($result)->toBeFalse();
        });

        test('point outside west returns false', function () {
            $result = $this->service->isPointInPolygon(14.6400, -90.5200, $this->squarePolygon);

            expect($result)->toBeFalse();
        });

        test('point outside east returns false', function () {
            $result = $this->service->isPointInPolygon(14.6400, -90.5000, $this->squarePolygon);

            expect($result)->toBeFalse();
        });

        test('point outside south returns false', function () {
            $result = $this->service->isPointInPolygon(14.6300, -90.5100, $this->squarePolygon);

            expect($result)->toBeFalse();
        });
    });

    describe('triangle polygon', function () {
        beforeEach(function () {
            $this->trianglePolygon = [
                ['lat' => 14.6400, 'lng' => -90.5100],
                ['lat' => 14.6300, 'lng' => -90.5200],
                ['lat' => 14.6300, 'lng' => -90.5000],
            ];
        });

        test('point inside triangle returns true', function () {
            $result = $this->service->isPointInPolygon(14.6330, -90.5100, $this->trianglePolygon);

            expect($result)->toBeTrue();
        });

        test('point outside triangle returns false', function () {
            $result = $this->service->isPointInPolygon(14.6250, -90.5100, $this->trianglePolygon);

            expect($result)->toBeFalse();
        });
    });

    describe('complex polygon', function () {
        beforeEach(function () {
            $this->complexPolygon = [
                ['lat' => 14.6020, 'lng' => -90.5150],
                ['lat' => 14.6030, 'lng' => -90.5100],
                ['lat' => 14.6050, 'lng' => -90.5080],
                ['lat' => 14.6070, 'lng' => -90.5060],
                ['lat' => 14.6080, 'lng' => -90.5100],
                ['lat' => 14.6070, 'lng' => -90.5140],
                ['lat' => 14.6050, 'lng' => -90.5160],
                ['lat' => 14.6030, 'lng' => -90.5170],
            ];
        });

        test('point inside complex polygon returns true', function () {
            $result = $this->service->isPointInPolygon(14.6050, -90.5120, $this->complexPolygon);

            expect($result)->toBeTrue();
        });

        test('point outside complex polygon returns false', function () {
            $result = $this->service->isPointInPolygon(14.6100, -90.5120, $this->complexPolygon);

            expect($result)->toBeFalse();
        });
    });

    describe('edge cases', function () {
        test('empty polygon returns false', function () {
            $result = $this->service->isPointInPolygon(14.6400, -90.5100, []);

            expect($result)->toBeFalse();
        });

        test('polygon with single point returns false', function () {
            $polygon = [['lat' => 14.6400, 'lng' => -90.5100]];

            $result = $this->service->isPointInPolygon(14.6400, -90.5100, $polygon);

            expect($result)->toBeFalse();
        });

        test('polygon with two points returns false', function () {
            $polygon = [
                ['lat' => 14.6400, 'lng' => -90.5100],
                ['lat' => 14.6300, 'lng' => -90.5000],
            ];

            $result = $this->service->isPointInPolygon(14.6350, -90.5050, $polygon);

            expect($result)->toBeFalse();
        });
    });

    describe('Guatemala City real locations', function () {
        test('punto en zona 10 centro', function () {
            $zona10 = [
                ['lat' => 14.6100, 'lng' => -90.5200],
                ['lat' => 14.6100, 'lng' => -90.5000],
                ['lat' => 14.5900, 'lng' => -90.5000],
                ['lat' => 14.5900, 'lng' => -90.5200],
            ];

            $result = $this->service->isPointInPolygon(14.6000, -90.5100, $zona10);

            expect($result)->toBeTrue();
        });

        test('punto fuera de zona 10', function () {
            $zona10 = [
                ['lat' => 14.6100, 'lng' => -90.5200],
                ['lat' => 14.6100, 'lng' => -90.5000],
                ['lat' => 14.5900, 'lng' => -90.5000],
                ['lat' => 14.5900, 'lng' => -90.5200],
            ];

            $result = $this->service->isPointInPolygon(14.6500, -90.5100, $zona10);

            expect($result)->toBeFalse();
        });
    });
});
