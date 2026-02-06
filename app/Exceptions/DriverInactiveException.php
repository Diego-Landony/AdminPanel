<?php

namespace App\Exceptions;

use App\Models\Driver;
use Exception;
use Illuminate\Http\JsonResponse;

class DriverInactiveException extends Exception
{
    public function __construct(
        protected Driver $driver
    ) {
        parent::__construct(__('auth.driver_inactive'));
    }

    /**
     * Get the inactive driver.
     */
    public function getDriver(): Driver
    {
        return $this->driver;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'driver_inactive',
        ], 403);
    }
}
