<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Points\RedeemPointsRequest;
use App\Http\Resources\Api\V1\Points\PointsBalanceResource;
use App\Http\Resources\Api\V1\Points\PointsTransactionResource;
use App\Http\Resources\Api\V1\Points\RewardResource;
use App\Models\CustomerPointsTransaction;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    /**
     * Redeem points on an order
     *
     * @OA\Post(
     *     path="/api/v1/points/redeem",
     *     tags={"Points"},
     *     summary="Canjear puntos",
     *     description="Canjea puntos del cliente en una orden para aplicar un descuento. Cada punto equivale a Q0.10 de descuento.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"order_id","points_to_redeem"},
     *
     *             @OA\Property(property="order_id", type="integer", example=123, description="ID de la orden"),
     *             @OA\Property(property="points_to_redeem", type="integer", example=100, description="Cantidad de puntos a canjear (mínimo 1)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Puntos redimidos exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Puntos redimidos exitosamente."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="transaction", type="object",
     *                     @OA\Property(property="id", type="integer", example=456),
     *                     @OA\Property(property="points", type="integer", example=-100),
     *                     @OA\Property(property="type", type="string", example="redeemed"),
     *                     @OA\Property(property="description", type="string", example="Redimidos 100 puntos en orden #123")
     *                 ),
     *                 @OA\Property(property="new_balance", type="object",
     *                     @OA\Property(property="points_balance", type="integer", example=150),
     *                     @OA\Property(property="points_value_in_currency", type="number", format="float", example=15.00)
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No tienes suficientes puntos disponibles."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="points_to_redeem", type="array", @OA\Items(type="string", example="No tienes suficientes puntos disponibles."))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Orden no encontrada")
     * )
     */
    public function redeem(RedeemPointsRequest $request): JsonResponse
    {
        $customer = $request->user();
        $validated = $request->validated();

        $order = Order::query()
            ->where('id', $validated['order_id'])
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        if ($customer->points < $validated['points_to_redeem']) {
            throw ValidationException::withMessages([
                'points_to_redeem' => ['No tienes suficientes puntos disponibles.'],
            ]);
        }

        DB::beginTransaction();

        try {
            $transaction = CustomerPointsTransaction::create([
                'customer_id' => $customer->id,
                'points' => -$validated['points_to_redeem'],
                'type' => 'redeemed',
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'description' => "Redimidos {$validated['points_to_redeem']} puntos en orden #{$order->id}",
            ]);

            $customer->update([
                'points' => $customer->points - $validated['points_to_redeem'],
                'points_updated_at' => now(),
            ]);

            $customer->updateCustomerType();

            DB::commit();

            return response()->json([
                'message' => 'Puntos redimidos exitosamente.',
                'data' => [
                    'transaction' => PointsTransactionResource::make($transaction),
                    'new_balance' => PointsBalanceResource::make($customer->fresh()),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get list of redeemable rewards (products, variants, and combos)
     *
     * @OA\Get(
     *     path="/api/v1/points/rewards",
     *     tags={"Points"},
     *     summary="Lista de recompensas disponibles",
     *     description="Retorna todos los productos, variantes y combos que pueden ser canjeados con puntos, ordenados por costo en puntos ascendente.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Recompensas obtenidas exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="type", type="string", enum={"product","variant","combo"}, example="product"),
     *                     @OA\Property(property="name", type="string", example="Sub del Día"),
     *                     @OA\Property(property="description", type="string", example="Sub especial del día", nullable=true),
     *                     @OA\Property(property="points_cost", type="integer", example=500, description="Puntos requeridos para canjear"),
     *                     @OA\Property(property="image", type="string", example="/storage/products/sub.jpg", nullable=true),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total", type="integer", example=15),
     *                 @OA\Property(property="products_count", type="integer", example=8),
     *                 @OA\Property(property="variants_count", type="integer", example=4),
     *                 @OA\Property(property="combos_count", type="integer", example=3)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function rewards(Request $request): JsonResponse
    {
        $products = Product::query()
            ->where('is_redeemable', true)
            ->whereNotNull('points_cost')
            ->where('is_active', true)
            ->get();

        $variants = ProductVariant::query()
            ->where('is_redeemable', true)
            ->whereNotNull('points_cost')
            ->where('is_active', true)
            ->get();

        $combos = Combo::query()
            ->where('is_redeemable', true)
            ->whereNotNull('points_cost')
            ->where('is_active', true)
            ->get();

        $rewards = collect()
            ->merge($products)
            ->merge($variants)
            ->merge($combos)
            ->sortBy('points_cost')
            ->values();

        return response()->json([
            'data' => RewardResource::collection($rewards),
            'meta' => [
                'total' => $rewards->count(),
                'products_count' => $products->count(),
                'variants_count' => $variants->count(),
                'combos_count' => $combos->count(),
            ],
        ]);
    }
}
