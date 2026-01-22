<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Points\PointsBalanceResource;
use App\Http\Resources\Api\V1\Points\PointsTransactionResource;
use App\Models\CustomerPointsTransaction;
use App\Models\PointsSetting;
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
     *                     @OA\Property(property="quetzales_per_point", type="integer", example=10, description="Cuántos quetzales equivale a 1 punto al canjear"),
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
     *     description="Retorna el historial paginado de todas las transacciones de puntos del cliente.
     *
     * **Tipos de transacciones:**
     * - `earned`: Puntos ganados por compras (reference_type = Order)
     * - `redeemed`: Puntos canjeados en compras (reference_type = Order)
     * - `expired`: Puntos expirados por 6 meses de inactividad
     * - `bonus`: Puntos de bonificación (promociones especiales)
     * - `adjustment`: Ajustes manuales por soporte
     *
     * **Vinculación con Pedidos:**
     * - Cuando `reference_type` = 'App\\Models\\Order', el `reference_id` es el ID del pedido
     * - Usa GET /orders/{reference_id} para ver el detalle del pedido relacionado",
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
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrar por tipo de transacción",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"earned","redeemed","expired","bonus","adjustment"})
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
     *                     @OA\Property(property="points", type="integer", example=9, description="Puntos (positivo = ganados, negativo = redimidos/expirados)"),
     *                     @OA\Property(property="type", type="string", enum={"earned","redeemed","expired","bonus","adjustment"}, example="earned"),
     *                     @OA\Property(property="description", type="string", example="Puntos ganados en orden #ORD-20251215-0001"),
     *                     @OA\Property(property="reference_type", type="string", nullable=true, example="App\\Models\\Order", description="Tipo de entidad relacionada"),
     *                     @OA\Property(property="reference_id", type="integer", nullable=true, example=123, description="ID de la entidad relacionada (ej: order_id)"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-12-15T15:30:00Z")
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

        $query = CustomerPointsTransaction::query()
            ->where('customer_id', $customer->id);

        // Filtrar por tipo si se proporciona
        if ($request->has('type') && in_array($request->type, ['earned', 'redeemed', 'expired', 'bonus', 'adjustment'])) {
            $query->where('type', $request->type);
        }

        $transactions = $query->orderBy('created_at', 'desc')
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

    /**
     * Get points expiration information
     *
     * @OA\Get(
     *     path="/api/v1/points/expiring",
     *     tags={"Points"},
     *     summary="Ver información de expiración de puntos",
     *     description="Retorna información sobre cuándo expirarán los puntos del cliente.
     *
     * **Métodos de expiración:**
     * - `total`: Todos los puntos expiran de golpe si hay X meses sin actividad
     * - `fifo`: Solo expiran los puntos más antiguos primero
     *
     * **Regla de expiración:**
     * - Los puntos expiran después de X meses de inactividad (configurable en admin)
     * - 'Actividad' se define como cualquier transacción de puntos (ganar o canjear)
     *
     * **Campos importantes:**
     * - `expiration_method`: método de expiración configurado (total o fifo)
     * - `expiration_months`: meses de inactividad configurados
     * - `will_expire`: true si los puntos están en riesgo de expirar
     * - `expires_at`: fecha cuando expirarán
     * - `days_until_expiration`: días restantes antes de expiración
     * - `warning_level`: 'critical' (≤7 días), 'warning' (≤30 días), 'safe' (>30 días)",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Información de expiración obtenida exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="points_balance", type="integer", example=250, description="Puntos actuales que podrían expirar"),
     *                 @OA\Property(property="expiration_method", type="string", enum={"total","fifo"}, example="total", description="Método de expiración configurado"),
     *                 @OA\Property(property="expiration_months", type="integer", example=6, description="Meses de inactividad antes de expirar"),
     *                 @OA\Property(property="will_expire", type="boolean", example=true, description="True si los puntos expirarán eventualmente"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", nullable=true, example="2026-06-15T10:30:00Z", description="Fecha de expiración (null si no hay puntos)"),
     *                 @OA\Property(property="days_until_expiration", type="integer", nullable=true, example=45, description="Días hasta expiración (null si no aplica)"),
     *                 @OA\Property(property="last_activity_at", type="string", format="date-time", nullable=true, example="2025-12-15T10:30:00Z", description="Última actividad de puntos"),
     *                 @OA\Property(property="warning_level", type="string", enum={"critical","warning","safe","none"}, example="warning", description="Nivel de urgencia"),
     *                 @OA\Property(property="message", type="string", example="Tus puntos expirarán en 45 días si no realizas una compra", description="Mensaje amigable para mostrar al usuario")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function expiring(Request $request): JsonResponse
    {
        $customer = $request->user();
        $points = $customer->points ?? 0;
        $settings = PointsSetting::get();

        // Si no tiene puntos, no hay nada que expire
        if ($points <= 0) {
            return response()->json([
                'data' => [
                    'points_balance' => 0,
                    'expiration_method' => $settings->expiration_method,
                    'expiration_months' => $settings->expiration_months,
                    'will_expire' => false,
                    'expires_at' => null,
                    'days_until_expiration' => null,
                    'last_activity_at' => $customer->points_last_activity_at?->toIso8601String(),
                    'warning_level' => 'none',
                    'message' => 'Sin puntos acumulados',
                ],
            ]);
        }

        // Calcular fecha de expiración usando meses configurados
        $lastActivity = $customer->points_last_activity_at ?? $customer->created_at;
        $expiresAt = $lastActivity->copy()->addMonths($settings->expiration_months);
        $now = now();

        // Si ya pasó la fecha de expiración, el job los expirará pronto
        if ($now->gte($expiresAt)) {
            return response()->json([
                'data' => [
                    'points_balance' => $points,
                    'expiration_method' => $settings->expiration_method,
                    'expiration_months' => $settings->expiration_months,
                    'will_expire' => true,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'days_until_expiration' => 0,
                    'last_activity_at' => $lastActivity->toIso8601String(),
                    'warning_level' => 'critical',
                    'message' => '¡Tus puntos expirarán muy pronto! Realiza una compra para conservarlos.',
                ],
            ]);
        }

        $daysUntilExpiration = $now->diffInDays($expiresAt);

        // Determinar nivel de advertencia
        $warningLevel = match (true) {
            $daysUntilExpiration <= 7 => 'critical',
            $daysUntilExpiration <= 30 => 'warning',
            default => 'safe',
        };

        // Mensaje amigable según método de expiración
        $methodDescription = $settings->expiration_method === 'total'
            ? 'todos tus puntos expirarán'
            : 'tus puntos más antiguos expirarán';

        $message = match ($warningLevel) {
            'critical' => "¡Atención! En {$daysUntilExpiration} días {$methodDescription}. Realiza una compra para conservarlos.",
            'warning' => "En {$daysUntilExpiration} días {$methodDescription} si no realizas una compra.",
            default => "Tus puntos están seguros. Expirarán en {$daysUntilExpiration} días sin actividad.",
        };

        return response()->json([
            'data' => [
                'points_balance' => $points,
                'expiration_method' => $settings->expiration_method,
                'expiration_months' => $settings->expiration_months,
                'will_expire' => true,
                'expires_at' => $expiresAt->toIso8601String(),
                'days_until_expiration' => $daysUntilExpiration,
                'last_activity_at' => $lastActivity->toIso8601String(),
                'warning_level' => $warningLevel,
                'message' => $message,
            ],
        ]);
    }
}
