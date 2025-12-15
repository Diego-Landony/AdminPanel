<?php

namespace App\Services;

use App\Models\CustomerAddress;
use App\Models\Restaurant;
use App\Services\Geofence\GeofenceService;

class DeliveryValidationService
{
    public function __construct(
        private GeofenceService $geofenceService
    ) {}

    /**
     * Valida dirección de entrega y retorna el restaurante asignado
     */
    public function validateDeliveryAddress(CustomerAddress $address): DeliveryValidationResult
    {
        return $this->validateCoordinates($address->latitude, $address->longitude);
    }

    /**
     * Valida coordenadas y retorna resultado
     */
    public function validateCoordinates(float $lat, float $lng): DeliveryValidationResult
    {
        $restaurant = $this->geofenceService->getBestRestaurantForDelivery($lat, $lng);

        if (! $restaurant) {
            $nearbyPickup = $this->getNearbyPickupRestaurants($lat, $lng);

            return new DeliveryValidationResult(
                isValid: false,
                restaurant: null,
                zone: null,
                nearbyPickupRestaurants: $nearbyPickup,
                errorMessage: 'No tenemos cobertura de delivery en esta ubicación'
            );
        }

        return new DeliveryValidationResult(
            isValid: true,
            restaurant: $restaurant,
            zone: $restaurant->price_location,
            nearbyPickupRestaurants: [],
            errorMessage: null
        );
    }

    /**
     * Obtiene restaurantes cercanos para pickup cuando no hay delivery disponible
     */
    private function getNearbyPickupRestaurants(float $lat, float $lng, int $limit = 3): array
    {
        return Restaurant::query()
            ->active()
            ->pickupActive()
            ->withCoordinates()
            ->get()
            ->map(function ($restaurant) use ($lat, $lng) {
                $distance = $this->calculateDistance($lat, $lng, $restaurant->latitude, $restaurant->longitude);

                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'address' => $restaurant->address,
                    'distance_km' => round($distance, 2),
                ];
            })
            ->sortBy('distance_km')
            ->take($limit)
            ->values()
            ->toArray();
    }

    /**
     * Calcula distancia entre dos puntos usando Haversine
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
