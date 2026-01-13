<?php

namespace App\Exceptions\Order;

use Exception;
use Illuminate\Http\JsonResponse;

class RestaurantClosedException extends Exception
{
    public function __construct(
        public readonly string $restaurantName,
        public readonly string $serviceType,
        public readonly ?string $closingTime = null,
        public readonly ?string $lastOrderTime = null,
        public readonly ?string $nextOpenTime = null
    ) {
        if ($this->lastOrderTime && $this->closingTime) {
            $message = sprintf(
                '%s ya no acepta pedidos de %s. El último horario para ordenar era %s (cierre: %s).',
                $restaurantName,
                $serviceType === 'pickup' ? 'recogida' : 'delivery',
                $lastOrderTime,
                $closingTime
            );
        } elseif ($this->closingTime) {
            $message = sprintf(
                '%s está cerrado actualmente. Horario de cierre: %s.',
                $restaurantName,
                $closingTime
            );
        } else {
            $message = sprintf(
                '%s no está disponible para pedidos en este momento.',
                $restaurantName
            );
        }

        parent::__construct($message);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error_code' => 'RESTAURANT_CLOSED',
            'data' => [
                'restaurant_name' => $this->restaurantName,
                'service_type' => $this->serviceType,
                'closing_time' => $this->closingTime,
                'last_order_time' => $this->lastOrderTime,
                'next_open_time' => $this->nextOpenTime,
            ],
        ], 422);
    }
}
