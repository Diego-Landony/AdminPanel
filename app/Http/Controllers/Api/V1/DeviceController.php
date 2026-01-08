<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CustomerDeviceResource;
use App\Models\CustomerDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * List all devices for the authenticated customer
     *
     * @OA\Get(
     *     path="/api/v1/devices",
     *     tags={"Devices"},
     *     summary="List customer devices",
     *     description="Returns all active devices for authenticated customer. Current device is flagged with is_current_device=true.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Devices retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/CustomerDevice")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $devices = $request->user()
            ->devices()
            ->where('is_active', true)
            ->orderBy('last_used_at', 'desc')
            ->get();

        return response()->json([
            'data' => CustomerDeviceResource::collection($devices),
        ]);
    }

    /**
     * Register or update a device with FCM token
     *
     * @OA\Post(
     *     path="/api/v1/devices/register",
     *     tags={"Devices"},
     *     summary="Register or update device",
     *     description="Enriches existing device (auto-created during auth) with FCM token and custom device name. The mobile app MUST send a personalized device name (e.g., 'iPhone 14 Pro de Juan', 'Samsung Galaxy S23 de María') to replace the generic 'Device' name created during authentication. This allows users to identify their devices in the device list.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"fcm_token","device_identifier","device_name"},
     *
     *             @OA\Property(property="fcm_token", type="string", example="fKw8h4Xj...", description="Firebase Cloud Messaging token for push notifications"),
     *             @OA\Property(property="device_identifier", type="string", example="550e8400-e29b-41d4-a716-446655440000", description="REQUIRED: Unique device UUID (must match identifier from auth)"),
     *             @OA\Property(property="device_name", type="string", example="iPhone 14 Pro de Juan", description="REQUIRED: Personalized device name set by the app (e.g., 'iPhone 14 Pro de Juan', 'Samsung Galaxy S23 de María'). This name will be displayed to the user when managing their devices.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Device registered or updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Dispositivo registrado exitosamente."),
     *             @OA\Property(property="data", ref="#/components/schemas/CustomerDevice")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string', 'max:255'],
            'device_identifier' => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $customer = $request->user();
        $currentToken = $customer->currentAccessToken();

        // Buscar dispositivo existente por device_identifier (prioritario) o fcm_token (fallback)
        $device = CustomerDevice::where('customer_id', $customer->id)
            ->where('device_identifier', $validated['device_identifier'])
            ->first();

        // Fallback: buscar por fcm_token si no se encontró por device_identifier
        if (! $device) {
            $device = CustomerDevice::where('customer_id', $customer->id)
                ->where('fcm_token', $validated['fcm_token'])
                ->first();
        }

        if ($device) {
            // Enriquecer dispositivo existente con FCM token
            $device->update([
                'fcm_token' => $validated['fcm_token'],
                'device_name' => $validated['device_name'],
                'sanctum_token_id' => $currentToken->id,
                'is_active' => true,
                'last_used_at' => now(),
                'login_count' => $device->login_count + 1,
            ]);

            $message = 'Dispositivo actualizado exitosamente.';
        } else {
            // Crear nuevo dispositivo (edge case: dispositivo no fue auto-creado durante auth)
            $device = CustomerDevice::create([
                'customer_id' => $customer->id,
                'fcm_token' => $validated['fcm_token'],
                'device_identifier' => $validated['device_identifier'],
                'device_name' => $validated['device_name'],
                'sanctum_token_id' => $currentToken->id,
                'is_active' => true,
                'last_used_at' => now(),
                'login_count' => 1,
            ]);

            $message = 'Dispositivo registrado exitosamente.';
        }

        return response()->json([
            'message' => $message,
            'data' => CustomerDeviceResource::make($device),
        ]);
    }

    /**
     * Delete/deactivate a device
     *
     * @OA\Delete(
     *     path="/api/v1/devices/{device}",
     *     tags={"Devices"},
     *     summary="Deactivate device",
     *     description="Marks device as inactive (soft deactivate). Device will no longer receive push notifications.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="device",
     *         in="path",
     *         required=true,
     *         description="Device ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Device deactivated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Dispositivo desactivado exitosamente.")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - device belongs to another customer"),
     *     @OA\Response(response=404, description="Device not found")
     * )
     */
    public function destroy(Request $request, CustomerDevice $device): JsonResponse
    {
        // Verificar que el dispositivo pertenezca al usuario autenticado
        if ($device->customer_id !== $request->user()->id) {
            return response()->json([
                'message' => 'No tienes permiso para eliminar este dispositivo.',
            ], 403);
        }

        // Marcar como inactivo en lugar de eliminar (soft deactivate)
        $device->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Dispositivo desactivado exitosamente.',
        ]);
    }
}
