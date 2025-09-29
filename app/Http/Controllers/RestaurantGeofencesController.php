<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantGeofencesController extends Controller
{
    /**
     * Display the geofences overview page with all restaurants
     */
    public function index(): Response
    {
        $restaurants = Restaurant::select([
            'id',
            'name',
            'address',
            'latitude',
            'longitude',
            'is_active',
            'delivery_active',
            'pickup_active',
            'geofence_kml',
        ])
        ->get()
        ->map(function ($restaurant) {
            $geofenceCoordinates = [];

            // Extract coordinates from KML if exists
            if ($restaurant->geofence_kml) {
                $geofenceCoordinates = $this->extractCoordinatesFromKML($restaurant->geofence_kml);
            }

            return [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'address' => $restaurant->address,
                'is_active' => $restaurant->is_active,
                'delivery_active' => $restaurant->delivery_active,
                'pickup_active' => $restaurant->pickup_active,
                'coordinates' => $restaurant->coordinates,
                'has_geofence' => $restaurant->hasGeofence(),
                'geofence_coordinates' => $geofenceCoordinates,
            ];
        });

        // Calculate statistics
        $stats = [
            'total_restaurants' => $restaurants->count(),
            'restaurants_with_geofence' => $restaurants->where('has_geofence', true)->count(),
            'active_with_delivery' => $restaurants->where('is_active', true)->where('delivery_active', true)->count(),
            'inactive_restaurants' => $restaurants->where('is_active', false)->count(),
        ];

        return Inertia::render('restaurants/geofences', [
            'restaurants' => $restaurants->values(),
            'stats' => $stats,
        ]);
    }

    /**
     * Extract coordinates from KML content for map display
     */
    private function extractCoordinatesFromKML(string $kmlContent): array
    {
        $coordinates = [];

        try {
            $dom = new \DOMDocument;
            $dom->loadXML($kmlContent);

            $coordElements = $dom->getElementsByTagName('coordinates');

            foreach ($coordElements as $coordElement) {
                $coordText = trim($coordElement->textContent);
                $points = explode(' ', $coordText);

                foreach ($points as $point) {
                    $point = trim($point);
                    if (! empty($point)) {
                        $coords = explode(',', $point);
                        if (count($coords) >= 2) {
                            $coordinates[] = [
                                'lat' => (float) $coords[1],
                                'lng' => (float) $coords[0],
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Return empty array on error
        }

        return $coordinates;
    }
}
