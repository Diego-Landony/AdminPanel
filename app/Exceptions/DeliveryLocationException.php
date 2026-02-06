<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class DeliveryLocationException extends Exception
{
    public function __construct(
        protected float $currentDistance,
        protected float $maxDistance
    ) {
        parent::__construct('Debes estar cerca de la dirección de entrega para completar');
    }

    /**
     * Get the current distance from delivery location in meters.
     */
    public function getCurrentDistance(): float
    {
        return $this->currentDistance;
    }

    /**
     * Get the maximum allowed distance for delivery in meters.
     */
    public function getMaxDistance(): float
    {
        return $this->maxDistance;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error_code' => 'LOCATION_OUT_OF_RANGE',
            'current_distance_meters' => round($this->currentDistance, 2),
            'max_distance_meters' => round($this->maxDistance, 2),
        ], 422);
    }
}
