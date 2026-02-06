<?php

namespace App\Services\Driver;

use App\Models\Driver;

class DriverLocationService
{
    /**
     * Earth's radius in kilometers for Haversine formula.
     */
    private const EARTH_RADIUS_KM = 6371;

    /**
     * Update the GPS location of a driver.
     *
     * @param  Driver  $driver  The driver to update
     * @param  float  $latitude  The new latitude coordinate
     * @param  float  $longitude  The new longitude coordinate
     */
    public function updateLocation(Driver $driver, float $latitude, float $longitude): void
    {
        $driver->updateLocation($latitude, $longitude);
    }

    /**
     * Calculate the distance between two GPS points using the Haversine formula.
     *
     * The Haversine formula calculates the shortest distance over the earth's
     * surface, giving an "as-the-crow-flies" distance between the points.
     *
     * @param  float  $lat1  Latitude of the first point
     * @param  float  $lon1  Longitude of the first point
     * @param  float  $lat2  Latitude of the second point
     * @param  float  $lon2  Longitude of the second point
     * @return float Distance in meters
     */
    public function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2)
            + cos($lat1Rad) * cos($lat2Rad)
            * sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distanceKm = self::EARTH_RADIUS_KM * $c;

        return $distanceKm * 1000;
    }

    /**
     * Check if the driver is within the allowed delivery range from the destination.
     *
     * @param  float  $driverLat  Driver's current latitude
     * @param  float  $driverLon  Driver's current longitude
     * @param  float  $destinationLat  Destination latitude
     * @param  float  $destinationLon  Destination longitude
     * @return bool True if within range, false otherwise
     */
    public function isWithinDeliveryRange(
        float $driverLat,
        float $driverLon,
        float $destinationLat,
        float $destinationLon
    ): bool {
        $distance = $this->calculateDistance(
            $driverLat,
            $driverLon,
            $destinationLat,
            $destinationLon
        );

        return $distance <= $this->getMaxDeliveryDistance();
    }

    /**
     * Get the maximum allowed distance for completing a delivery.
     *
     * @return int Maximum distance in meters
     */
    public function getMaxDeliveryDistance(): int
    {
        return (int) config('driver.max_delivery_distance_meters', 100);
    }
}
