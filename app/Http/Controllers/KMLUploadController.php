<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class KMLUploadController extends Controller
{
    /**
     * Show the KML upload form for a restaurant
     */
    public function show(Restaurant $restaurant): Response
    {
        return Inertia::render('restaurants/kml-upload', [
            'restaurant' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'address' => $restaurant->address,
                'has_geofence' => $restaurant->hasGeofence(),
                'geofence_kml' => $restaurant->geofence_kml,
            ],
        ]);
    }

    /**
     * Upload and process KML file for restaurant geofence
     */
    public function upload(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'kml_file' => 'required|file|max:2048', // Max 2MB
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->with('error', 'Error en el archivo. Asegúrate de que no supere 2MB.');
        }

        $file = $request->file('kml_file');

        // Verify file extension
        if (strtolower($file->getClientOriginalExtension()) !== 'kml') {
            return back()->with('error', 'El archivo debe tener extensión .kml');
        }

        try {
            $kmlContent = file_get_contents($file->getRealPath());

            // Validate KML content
            if (! $this->isValidKML($kmlContent)) {
                return back()->with('error', 'El archivo KML no es válido o no contiene datos de polígono.');
            }

            // Save KML content to restaurant
            $restaurant->update([
                'geofence_kml' => $kmlContent,
            ]);

            return back()->with('success', 'Geocerca actualizada exitosamente desde archivo KML.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el archivo KML: '.$e->getMessage());
        }
    }

    /**
     * Remove KML geofence from restaurant
     */
    public function remove(Restaurant $restaurant): RedirectResponse
    {
        $restaurant->update([
            'geofence_kml' => null,
        ]);

        return back()->with('success', 'Geocerca removida exitosamente.');
    }

    /**
     * Validate KML content
     */
    private function isValidKML(string $kmlContent): bool
    {
        // Basic KML validation
        if (empty($kmlContent)) {
            return false;
        }

        // Check if it's valid XML
        $previousValue = libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $isValidXML = $dom->loadXML($kmlContent);
        libxml_use_internal_errors($previousValue);

        if (! $isValidXML) {
            return false;
        }

        // Check for KML namespace and required elements
        $hasKMLNamespace = strpos($kmlContent, 'http://www.opengis.net/kml/2.2') !== false ||
                          strpos($kmlContent, 'http://earth.google.com/kml/2.2') !== false ||
                          strpos($kmlContent, '<kml') !== false;

        $hasPolygonData = strpos($kmlContent, '<Polygon>') !== false ||
                         strpos($kmlContent, '<coordinates>') !== false;

        return $hasKMLNamespace && $hasPolygonData;
    }

    /**
     * Preview KML coordinates (optional method for future map integration)
     */
    public function preview(Restaurant $restaurant): Response
    {
        if (! $restaurant->hasGeofence()) {
            return back()->with('error', 'Este restaurante no tiene geocerca definida.');
        }

        $coordinates = $this->extractCoordinatesFromKML($restaurant->geofence_kml);

        return Inertia::render('restaurants/kml-preview', [
            'restaurant' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'address' => $restaurant->address,
                'coordinates' => $restaurant->coordinates,
            ],
            'geofence_coordinates' => $coordinates,
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
                                'lng' => (float) $coords[0],
                                'lat' => (float) $coords[1],
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
