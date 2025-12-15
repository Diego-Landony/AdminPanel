<?php

namespace App\Services\Geofence;

class PointInPolygonService
{
    /**
     * Verifica si un punto está dentro de un polígono usando ray-casting
     *
     * @param  float  $lat  Latitud del punto
     * @param  float  $lng  Longitud del punto
     * @param  array  $polygon  Array de ['lat' => float, 'lng' => float]
     */
    public function isPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $n = count($polygon);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i]['lat'];
            $yi = $polygon[$i]['lng'];
            $xj = $polygon[$j]['lat'];
            $yj = $polygon[$j]['lng'];

            if ((($yi > $lng) !== ($yj > $lng)) &&
                ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi)) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
