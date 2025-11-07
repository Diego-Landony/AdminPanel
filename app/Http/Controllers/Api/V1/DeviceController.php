<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CustomerDeviceResource;
use App\Models\CustomerDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
     *     description="Registers new device or updates existing device with FCM token. Associates device with current Sanctum token for session management.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"fcm_token","device_type"},
     *
     *             @OA\Property(property="fcm_token", type="string", example="fKw8h4Xj...", description="Firebase Cloud Messaging token"),
     *             @OA\Property(property="device_identifier", type="string", nullable=true, example="ABC123DEF456", description="Unique device identifier (auto-generated if not provided)"),
     *             @OA\Property(property="device_type", type="string", enum={"ios","android","web"}, example="ios", description="Device platform"),
     *             @OA\Property(property="device_name", type="string", nullable=true, example="iPhone 14 Pro", description="Human-readable device name"),
     *             @OA\Property(property="device_model", type="string", nullable=true, example="iPhone15,2", description="Device model identifier"),
     *             @OA\Property(property="app_version", type="string", nullable=true, example="1.0.5", description="App version number"),
     *             @OA\Property(property="os_version", type="string", nullable=true, example="17.2", description="OS version number")
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
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'device_type' => ['required', 'in:ios,android,web'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_model' => ['nullable', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:20'],
            'os_version' => ['nullable', 'string', 'max:20'],
        ]);

        $customer = $request->user();
        $currentToken = $customer->currentAccessToken();

        // Buscar dispositivo existente por device_identifier o fcm_token
        $device = CustomerDevice::where('customer_id', $customer->id)
            ->where(function ($query) use ($validated) {
                if (isset($validated['device_identifier'])) {
                    $query->where('device_identifier', $validated['device_identifier']);
                }
                $query->orWhere('fcm_token', $validated['fcm_token']);
            })
            ->first();

        if ($device) {
            // Actualizar dispositivo existente
            $device->update([
                'fcm_token' => $validated['fcm_token'],
                'device_identifier' => $validated['device_identifier'] ?? $device->device_identifier,
                'device_type' => $validated['device_type'],
                'device_name' => $validated['device_name'] ?? $device->device_name,
                'device_model' => $validated['device_model'] ?? $device->device_model,
                'app_version' => $validated['app_version'] ?? $device->app_version,
                'os_version' => $validated['os_version'] ?? $device->os_version,
                'sanctum_token_id' => $currentToken->id,
                'is_active' => true,
                'last_used_at' => now(),
            ]);

            $message = 'Dispositivo actualizado exitosamente.';
        } else {
            // Crear nuevo dispositivo
            $device = CustomerDevice::create([
                'customer_id' => $customer->id,
                'fcm_token' => $validated['fcm_token'],
                'device_identifier' => $validated['device_identifier'] ?? Str::uuid()->toString(),
                'device_type' => $validated['device_type'],
                'device_name' => $validated['device_name'] ?? $validated['device_type'].' device',
                'device_model' => $validated['device_model'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'os_version' => $validated['os_version'] ?? null,
                'sanctum_token_id' => $currentToken->id,
                'is_active' => true,
                'last_used_at' => now(),
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
     *     @OA\Response(response=403, description="Forbidden - device belongs to another customer")
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
