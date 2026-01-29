<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\Wallet\AppleWalletService;
use App\Services\Wallet\GoogleWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class WalletController extends Controller
{
    /**
     * Generate Apple Wallet pass download URL.
     *
     * @OA\Post(
     *     path="/api/v1/wallet/apple/pass",
     *     tags={"Wallet"},
     *     summary="Generar pase Apple Wallet",
     *     description="Genera una URL temporal firmada para descargar el archivo .pkpass de la tarjeta de lealtad.
     *
     * **Flujo:**
     * 1. El endpoint genera una URL firmada temporal (15 min)
     * 2. Flutter abre la URL con url_launcher
     * 3. iOS detecta el .pkpass y muestra el preview
     * 4. El usuario confirma y agrega a Apple Wallet
     *
     * **Nota:** No se envía body. El customer_id se extrae del token JWT de autenticación.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="URL de descarga generada exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="url", type="string", example="https://appmobile.subwaycardgt.com/api/v1/wallet/apple/download/1?signature=...")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="El cliente no tiene una tarjeta Subway asignada"),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite: 1 cada 5 minutos)")
     * )
     */
    public function applePass(Request $request): JsonResponse
    {
        $customer = $request->user();
        $customer->loadMissing('customerType');

        if (! $customer->subway_card) {
            return response()->json([
                'message' => 'No tienes una tarjeta Subway asignada.',
            ], 422);
        }

        $url = URL::temporarySignedRoute(
            'api.v1.wallet.apple.download',
            now()->addMinutes(15),
            ['customer' => $customer->id]
        );

        return response()->json([
            'data' => [
                'url' => $url,
            ],
        ]);
    }

    /**
     * Download Apple Wallet .pkpass file via signed URL.
     *
     * This route uses Laravel's signed URL middleware for security
     * instead of Bearer token authentication, since Flutter opens
     * it via url_launcher which cannot attach auth headers.
     *
     * @OA\Get(
     *     path="/api/v1/wallet/apple/download/{customer}",
     *     tags={"Wallet"},
     *     summary="Descargar archivo .pkpass",
     *     description="Descarga el archivo .pkpass para Apple Wallet. Requiere una URL firmada válida (generada por POST /wallet/apple/pass).
     *
     * **Importante:** Este endpoint NO usa Bearer token. La seguridad la provee la firma de la URL.",
     *
     *     @OA\Parameter(
     *         name="customer",
     *         in="path",
     *         description="ID del cliente",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Archivo .pkpass generado exitosamente",
     *
     *         @OA\MediaType(
     *             mediaType="application/vnd.apple.pkpass"
     *         )
     *     ),
     *
     *     @OA\Response(response=403, description="URL inválida o expirada"),
     *     @OA\Response(response=404, description="Cliente no encontrado"),
     *     @OA\Response(response=500, description="Error generando el pase")
     * )
     */
    public function applePassDownload(Request $request, int $customer, AppleWalletService $appleWalletService): Response
    {
        $customer = Customer::with('customerType')->findOrFail($customer);

        try {
            $passData = $appleWalletService->generatePass($customer);

            Log::info('Apple Wallet pass generated', [
                'customer_id' => $customer->id,
            ]);

            return response($passData, 200, [
                'Content-Type' => 'application/vnd.apple.pkpass',
                'Content-Disposition' => 'attachment; filename="subway-loyalty.pkpass"',
            ]);
        } catch (\Throwable $e) {
            Log::error('Apple Wallet pass generation failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Error generando el pase de Apple Wallet.');
        }
    }

    /**
     * Generate Google Wallet save URL.
     *
     * @OA\Post(
     *     path="/api/v1/wallet/google/pass",
     *     tags={"Wallet"},
     *     summary="Generar URL de Google Wallet",
     *     description="Genera una URL de Google Pay para guardar la tarjeta de lealtad en Google Wallet.
     *
     * **Flujo:**
     * 1. El endpoint crea/actualiza el LoyaltyObject en Google Wallet API
     * 2. Genera un JWT firmado con la referencia al objeto
     * 3. Retorna la URL `https://pay.google.com/gp/v/save/{JWT}`
     * 4. Flutter abre la URL con url_launcher
     * 5. Google Wallet muestra el preview y el usuario confirma
     *
     * **Nota:** No se envía body. El customer_id se extrae del token JWT de autenticación.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="URL de Google Wallet generada exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="save_url", type="string", example="https://pay.google.com/gp/v/save/eyJhbGciOiJSUzI1NiIs...")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="El cliente no tiene una tarjeta Subway asignada"),
     *     @OA\Response(response=429, description="Demasiadas peticiones (límite: 1 cada 5 minutos)"),
     *     @OA\Response(response=500, description="Error generando el pase")
     * )
     */
    public function googlePass(Request $request, GoogleWalletService $googleWalletService): JsonResponse
    {
        $customer = $request->user();
        $customer->loadMissing('customerType');

        if (! $customer->subway_card) {
            return response()->json([
                'message' => 'No tienes una tarjeta Subway asignada.',
            ], 422);
        }

        try {
            $saveUrl = $googleWalletService->generateSaveUrl($customer);

            Log::info('Google Wallet pass generated', [
                'customer_id' => $customer->id,
            ]);

            return response()->json([
                'data' => [
                    'save_url' => $saveUrl,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Google Wallet pass generation failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error generando el pase de Google Wallet.',
            ], 500);
        }
    }
}
