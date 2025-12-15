<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\ComboResource;
use App\Http\Resources\Api\V1\Menu\ProductResource;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\ProductView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductViewController extends Controller
{
    /**
     * Record product view.
     *
     * @OA\Post(
     *     path="/api/v1/products/{product}/view",
     *     tags={"Product Views"},
     *     summary="Registrar vista de producto",
     *     description="Registra que un cliente vio un producto. Se usa para el historial de productos vistos recientemente.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="product",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Vista registrada exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Vista registrada exitosamente")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function recordProductView(Request $request, Product $product): JsonResponse
    {
        $customer = $request->user();

        ProductView::recordView($customer, $product);

        return response()->json([
            'message' => 'Vista registrada exitosamente',
        ]);
    }

    /**
     * Record combo view.
     *
     * @OA\Post(
     *     path="/api/v1/combos/{combo}/view",
     *     tags={"Product Views"},
     *     summary="Registrar vista de combo",
     *     description="Registra que un cliente vio un combo. Se usa para el historial de productos vistos recientemente.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="combo",
     *         in="path",
     *         description="Combo ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Vista registrada exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Vista registrada exitosamente")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Combo no encontrado")
     * )
     */
    public function recordComboView(Request $request, Combo $combo): JsonResponse
    {
        $customer = $request->user();

        ProductView::recordView($customer, $combo);

        return response()->json([
            'message' => 'Vista registrada exitosamente',
        ]);
    }

    /**
     * Get recently viewed products and combos.
     *
     * @OA\Get(
     *     path="/api/v1/me/recently-viewed",
     *     tags={"Product Views"},
     *     summary="Obtener productos vistos recientemente",
     *     description="Retorna los últimos 20 productos y combos vistos por el cliente autenticado.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Productos vistos recientemente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="type", type="string", example="product", description="Tipo: product o combo"),
     *                     @OA\Property(property="data", type="object", description="Datos del producto o combo"),
     *                     @OA\Property(property="viewed_at", type="string", format="date-time", example="2025-12-15T10:30:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function getRecentlyViewed(Request $request): JsonResponse
    {
        $customer = $request->user();

        $views = ProductView::where('customer_id', $customer->id)
            ->with('viewable')
            ->orderBy('viewed_at', 'desc')
            ->limit(20)
            ->get();

        $items = $views->map(function ($view) {
            if (! $view->viewable) {
                return null;
            }

            if ($view->viewable_type === Product::class) {
                return [
                    'type' => 'product',
                    'data' => new ProductResource($view->viewable),
                    'viewed_at' => $view->viewed_at->toIso8601String(),
                ];
            } elseif ($view->viewable_type === Combo::class) {
                return [
                    'type' => 'combo',
                    'data' => new ComboResource($view->viewable),
                    'viewed_at' => $view->viewed_at->toIso8601String(),
                ];
            }

            return null;
        })->filter()->values();

        return response()->json([
            'data' => $items,
        ]);
    }
}
