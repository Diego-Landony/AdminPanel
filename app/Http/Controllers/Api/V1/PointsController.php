<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Points\PointsBalanceResource;
use App\Http\Resources\Api\V1\Points\PointsTransactionResource;
use App\Models\CustomerPointsTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointsController extends Controller
{
    /**
     * Get customer's current points balance
     *
     * @OA\Get(
     *     path="/api/v1/points/balance",
     *     tags={"Points"},
     *     summary="Obtener balance de puntos",
     *     description="Retorna el balance actual de puntos del cliente, su valor en Quetzales, y las tasas de conversión.
     *
     * **Sistema de Puntos:**
     * - Acumulación: 1 punto por cada Q10 gastados
     * - Redención: 1 punto = Q0.10 de descuento
     * - Los puntos expiran a los 6 meses de inactividad
     * - El multiplicador de puntos varía según el tipo de cliente (Regular, Bronce, Plata, Oro, Platino)",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Balance de puntos obtenido exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="points_balance", type="integer", example=250, description="Puntos totales disponibles"),
     *                 @OA\Property(property="points_updated_at", type="string", format="date-time", example="2025-12-10T15:30:00Z"),
     *                 @OA\Property(property="points_value_in_currency", type="number", format="float", example=25.00, description="Valor en Quetzales (Q)"),
     *                 @OA\Property(property="conversion_rate", type="object",
     *                     @OA\Property(property="points_per_quetzal_spent", type="integer", example=10, description="Puntos ganados por cada Q1 gastado"),
     *                     @OA\Property(property="points_value", type="number", format="float", example=0.10, description="Valor en Q de cada punto")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function balance(Request $request): JsonResponse
    {
        $customer = $request->user();

        return response()->json([
            'data' => PointsBalanceResource::make($customer),
        ]);
    }

    /**
     * Get customer's points transaction history
     *
     * @OA\Get(
     *     path="/api/v1/points/history",
     *     tags={"Points"},
     *     summary="Historial de transacciones de puntos",
     *     description="Retorna el historial paginado de todas las transacciones de puntos del cliente (ganados, redimidos, expirados).",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Historial obtenido exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="points", type="integer", example=50, description="Puntos (positivo = ganados, negativo = redimidos)"),
     *                     @OA\Property(property="type", type="string", enum={"earned","redeemed","expired","bonus","adjustment"}, example="earned"),
     *                     @OA\Property(property="description", type="string", example="Puntos ganados en orden #ORD-2025-000123"),
     *                     @OA\Property(property="reference_type", type="string", nullable=true, example="App\\Models\\Order"),
     *                     @OA\Property(property="reference_id", type="integer", nullable=true, example=456),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-12-10T15:30:00Z")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=87)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function history(Request $request): JsonResponse
    {
        $customer = $request->user();

        $transactions = CustomerPointsTransaction::query()
            ->where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => PointsTransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    // Note: redeem() and rewards() methods were removed
    // Points redemption only happens in-store, not in the app
}
