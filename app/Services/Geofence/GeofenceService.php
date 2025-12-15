<?php

namespace App\Services\Geofence;

use App\Models\Restaurant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GeofenceService
{
    public function __construct(
        private KmlParserService $kmlParser,
        private PointInPolygonService $pointInPolygon
    ) {}

    /**
     * Encuentra restaurantes que pueden entregar a las coordenadas dadas
     */
    public function findRestaurantsForCoordinates(float $lat, float $lng): Collection
    {
        $restaurants = Restaurant::query()
            ->active()
            ->deliveryActive()
            ->withGeofence()
            ->get();

        return $restaurants->filter(function ($restaurant) use ($lat, $lng) {
            return $this->canRestaurantDeliverTo($restaurant, $lat, $lng);
        });
    }

    /**
     * Verifica si un restaurante puede entregar a las coordenadas
     */
    public function canRestaurantDeliverTo(Restaurant $restaurant, float $lat, float $lng): bool
    {
        if (! $restaurant->hasGeofence()) {
            return false;
        }

        $coordinates = $this->getGeofenceCoordinates($restaurant);

        if (empty($coordinates)) {
            return false;
        }

        return $this->pointInPolygon->isPointInPolygon($lat, $lng, $coordinates);
    }

    /**
     * Obtiene el mejor restaurante para delivery (el más cercano)
     */
    public function getBestRestaurantForDelivery(float $lat, float $lng): ?Restaurant
    {
        $restaurants = $this->findRestaurantsForCoordinates($lat, $lng);

        if ($restaurants->isEmpty()) {
            return null;
        }

        return $restaurants->sortBy(function ($restaurant) use ($lat, $lng) {
            return $this->calculateDistance($lat, $lng, $restaurant->latitude, $restaurant->longitude);
        })->first();
    }

    /**
     * Obtiene coordenadas de geocerca con cache
     */
    private function getGeofenceCoordinates(Restaurant $restaurant): array
    {
        $cacheKey = "restaurant:{$restaurant->id}:geofence:".md5($restaurant->geofence_kml);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($restaurant) {
            return $this->kmlParser->parseToCoordinates($restaurant->geofence_kml);
        });
    }

    /**
     * Calcula distancia entre dos puntos (Haversine)
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
