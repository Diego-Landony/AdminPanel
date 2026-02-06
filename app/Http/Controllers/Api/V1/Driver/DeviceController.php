<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * Register or update FCM token for push notifications.
     *
     * POST /api/v1/driver/device/fcm-token
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string', 'max:500'],
        ]);

        $driver = $request->user('driver');

        $driver->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Token FCM registrado correctamente.',
        ]);
    }

    /**
     * Remove FCM token (on logout or disable notifications).
     *
     * DELETE /api/v1/driver/device/fcm-token
     */
    public function removeFcmToken(Request $request): JsonResponse
    {
        $driver = $request->user('driver');

        $driver->update([
            'fcm_token' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Token FCM eliminado correctamente.',
        ]);
    }
}
