<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Exceptions\InvalidPasswordException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Driver\UpdateProfileRequest;
use App\Http\Resources\Api\V1\Driver\DriverProfileResource;
use App\Services\Driver\DriverProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        protected DriverProfileService $profileService
    ) {}

    /**
     * Get driver profile with detailed stats.
     *
     * GET /api/v1/driver/profile
     */
    public function show(Request $request): JsonResponse
    {
        $driver = $request->user('driver');
        $driver->load('restaurant');

        return response()->json([
            'success' => true,
            'data' => DriverProfileResource::make($driver),
            'message' => 'Perfil obtenido correctamente.',
        ]);
    }

    /**
     * Update driver profile.
     *
     * PUT /api/v1/driver/profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $driver = $request->user('driver');

        $driver = $this->profileService->updateProfile(
            $driver,
            $request->validated()
        );

        $driver->load('restaurant');

        return response()->json([
            'success' => true,
            'data' => DriverProfileResource::make($driver),
            'message' => 'Perfil actualizado correctamente.',
        ]);
    }

    /**
     * Change driver password.
     *
     * PUT /api/v1/driver/profile/password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $driver = $request->user('driver');

        try {
            $this->profileService->changePassword(
                $driver,
                $request->validated('current_password'),
                $request->validated('password')
            );

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Contraseña actualizada correctamente.',
            ]);
        } catch (InvalidPasswordException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'INVALID_PASSWORD',
            ], 422);
        }
    }
}
