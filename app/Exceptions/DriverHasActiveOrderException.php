<?php

namespace App\Exceptions;

use App\Models\Driver;
use App\Models\Order;
use Exception;
use Illuminate\Http\JsonResponse;

class DriverHasActiveOrderException extends Exception
{
    public function __construct(
        protected Driver $driver,
        protected ?Order $activeOrder = null
    ) {
        parent::__construct(__('driver.has_active_order'));
    }

    /**
     * Get the driver with active order.
     */
    public function getDriver(): Driver
    {
        return $this->driver;
    }

    /**
     * Get the active order if available.
     */
    public function getActiveOrder(): ?Order
    {
        return $this->activeOrder;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        $data = [
            'message' => $this->getMessage(),
            'error' => 'driver_has_active_order',
        ];

        if ($this->activeOrder) {
            $data['order_id'] = $this->activeOrder->id;
        }

        return response()->json($data, 409);
    }
}
