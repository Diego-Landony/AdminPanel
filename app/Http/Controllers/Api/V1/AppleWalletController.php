<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppleWalletRegistration;
use App\Models\Customer;
use App\Services\Wallet\AppleWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Controlador para los endpoints REST de Apple Wallet.
 *
 * Apple Wallet requiere estos endpoints para el registro de dispositivos
 * y la actualización automática de pases.
 *
 * @see https://developer.apple.com/documentation/walletpasses/adding_a_web_service_to_update_passes
 */
class AppleWalletController extends Controller
{
    public function __construct(
        private AppleWalletService $walletService
    ) {}

    /**
     * Registrar un dispositivo para recibir push notifications.
     *
     * POST /v1/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}/{serialNumber}
     */
    public function registerDevice(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $serialNumber
    ): Response {
        if (! $this->validateAuthorization($request, $serialNumber)) {
            return response('', 401);
        }

        $pushToken = $request->input('pushToken');
        if (! $pushToken) {
            return response('', 400);
        }

        $customerId = AppleWalletRegistration::extractCustomerIdFromSerial($serialNumber);
        if (! $customerId) {
            return response('', 404);
        }

        // Verificar si ya existe el registro
        $existing = AppleWalletRegistration::where('device_library_identifier', $deviceLibraryIdentifier)
            ->where('pass_type_identifier', $passTypeIdentifier)
            ->where('serial_number', $serialNumber)
            ->first();

        if ($existing) {
            // Actualizar push token si cambió
            if ($existing->push_token !== $pushToken) {
                $existing->update(['push_token' => $pushToken]);
            }

            return response('', 200);
        }

        // Crear nuevo registro
        AppleWalletRegistration::create([
            'customer_id' => $customerId,
            'device_library_identifier' => $deviceLibraryIdentifier,
            'push_token' => $pushToken,
            'pass_type_identifier' => $passTypeIdentifier,
            'serial_number' => $serialNumber,
        ]);

        return response('', 201);
    }

    /**
     * Eliminar el registro de un dispositivo.
     *
     * DELETE /v1/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}/{serialNumber}
     */
    public function unregisterDevice(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier,
        string $serialNumber
    ): Response {
        if (! $this->validateAuthorization($request, $serialNumber)) {
            return response('', 401);
        }

        AppleWalletRegistration::where('device_library_identifier', $deviceLibraryIdentifier)
            ->where('pass_type_identifier', $passTypeIdentifier)
            ->where('serial_number', $serialNumber)
            ->delete();

        return response('', 200);
    }

    /**
     * Obtener lista de pases actualizados para un dispositivo.
     *
     * GET /v1/devices/{deviceLibraryIdentifier}/registrations/{passTypeIdentifier}
     */
    public function getSerialNumbers(
        Request $request,
        string $deviceLibraryIdentifier,
        string $passTypeIdentifier
    ): Response|JsonResponse {
        $passesUpdatedSince = $request->query('passesUpdatedSince');

        $query = AppleWalletRegistration::where('device_library_identifier', $deviceLibraryIdentifier)
            ->where('pass_type_identifier', $passTypeIdentifier);

        if ($passesUpdatedSince) {
            // Buscar pases actualizados después de esta fecha
            $query->whereHas('customer', function ($q) use ($passesUpdatedSince) {
                $q->where('points_updated_at', '>', date('Y-m-d H:i:s', (int) $passesUpdatedSince));
            });
        }

        $registrations = $query->get();

        if ($registrations->isEmpty()) {
            return response('', 204);
        }

        $serialNumbers = $registrations->pluck('serial_number')->unique()->values()->toArray();

        // Obtener el timestamp más reciente para lastUpdated
        $lastUpdated = $registrations->max('updated_at');

        return response()->json([
            'serialNumbers' => $serialNumbers,
            'lastUpdated' => $lastUpdated ? (string) $lastUpdated->timestamp : (string) time(),
        ]);
    }

    /**
     * Obtener el pase actualizado.
     *
     * GET /v1/passes/{passTypeIdentifier}/{serialNumber}
     */
    public function getPass(
        Request $request,
        string $passTypeIdentifier,
        string $serialNumber
    ): Response {
        if (! $this->validateAuthorization($request, $serialNumber)) {
            return response('', 401);
        }

        $customerId = AppleWalletRegistration::extractCustomerIdFromSerial($serialNumber);
        if (! $customerId) {
            return response('', 404);
        }

        $customer = Customer::find($customerId);
        if (! $customer) {
            return response('', 404);
        }

        try {
            $passData = $this->walletService->generatePass($customer);

            return response($passData, 200)
                ->header('Content-Type', 'application/vnd.apple.pkpass')
                ->header('Content-Disposition', 'attachment; filename="subway-card.pkpass"')
                ->header('Last-Modified', gmdate('D, d M Y H:i:s', $customer->points_updated_at?->timestamp ?? time()).' GMT');
        } catch (\Exception $e) {
            \Log::error('Error generating Apple Wallet pass: '.$e->getMessage());

            return response('', 500);
        }
    }

    /**
     * Recibir logs de errores de dispositivos (opcional pero útil para debugging).
     *
     * POST /v1/log
     */
    public function log(Request $request): Response
    {
        $logs = $request->input('logs', []);

        foreach ($logs as $log) {
            \Log::info('Apple Wallet device log: '.$log);
        }

        return response('', 200);
    }

    /**
     * Valida el header Authorization de la solicitud.
     */
    private function validateAuthorization(Request $request, string $serialNumber): bool
    {
        $authHeader = $request->header('Authorization');

        if (! $authHeader || ! str_starts_with($authHeader, 'ApplePass ')) {
            return false;
        }

        $token = substr($authHeader, 10);

        return $this->walletService->validateAuthToken($token, $serialNumber);
    }
}
