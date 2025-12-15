<?php

namespace App\Services\Geofence;

use App\Exceptions\Delivery\InvalidKmlException;

class KmlParserService
{
    /**
     * Parsea contenido KML y extrae coordenadas del polígono
     *
     * @return array Array de ['lat' => float, 'lng' => float]
     *
     * @throws InvalidKmlException
     */
    public function parseToCoordinates(string $kmlContent): array
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
            throw new InvalidKmlException(0, 'Error parsing KML: '.$e->getMessage());
        }

        return $coordinates;
    }
}
