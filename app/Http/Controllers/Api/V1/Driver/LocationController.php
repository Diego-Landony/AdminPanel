<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\UpdateLocationRequest;
use App\Services\Driver\DriverLocationService;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function __construct(
        protected DriverLocationService $locationService
    ) {}

    /**
     * Update driver's GPS location.
     *
     * POST /api/v1/driver/location
     */
    public function update(UpdateLocationRequest $request): JsonResponse
    {
        $driver = auth('driver')->user();

        $this->locationService->updateLocation(
            $driver,
            $request->validated('latitude'),
            $request->validated('longitude')
        );

        return response()->json([
            'success' => true,
            'data' => [
                'latitude' => (float) $request->validated('latitude'),
                'longitude' => (float) $request->validated('longitude'),
                'updated_at' => now()->toIso8601String(),
            ],
            'message' => 'Ubicación actualizada correctamente.',
        ]);
    }
}
