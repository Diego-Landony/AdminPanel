<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rewards\RewardResource;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use Illuminate\Http\JsonResponse;

class RewardsController extends Controller
{
    /**
     * Get list of redeemable products and combos
     *
     * @OA\Get(
     *     path="/api/v1/menu/rewards",
     *     tags={"Menu"},
     *     summary="Obtener catálogo de recompensas canjeables",
     *     description="Retorna la lista de productos y combos que pueden ser canjeados por puntos en tienda física.
     *
     * **Importante:**
     * - Endpoint público (no requiere autenticación)
     * - El canje solo se realiza en tienda física, no en la app
     * - Cada item incluye su nombre, imagen y costo en puntos
     * - Los productos con variantes canjeables incluyen sus variantes",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Catálogo de recompensas obtenido exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", enum={"product","combo"}, example="product"),
     *                     @OA\Property(property="name", type="string", example="Subway Pollo Teriyaki"),
     *                     @OA\Property(property="image_url", type="string", nullable=true, example="/storage/products/pollo-teriyaki.jpg"),
     *                     @OA\Property(property="points_cost", type="integer", nullable=true, example=150, description="Costo en puntos (null si tiene variantes)"),
     *                     @OA\Property(property="variants", type="array", nullable=true, description="Variantes canjeables del producto",
     *
     *                         @OA\Items(
     *
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="15cm"),
     *                             @OA\Property(property="points_cost", type="integer", example=150)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        // Productos canjeables (activos y con is_redeemable = true)
        $products = Product::query()
            ->where('is_active', true)
            ->where(function ($query) {
                // Producto directamente canjeable
                $query->where('is_redeemable', true)
                    // O tiene variantes canjeables
                    ->orWhereHas('variants', function ($q) {
                        $q->where('is_active', true)
                            ->where('is_redeemable', true);
                    });
            })
            ->with([
                'variants' => function ($q) {
                    $q->where('is_active', true)
                        ->where('is_redeemable', true)
                        ->orderBy('sort_order');
                },
            ])
            ->orderBy('sort_order')
            ->get();

        // Combos canjeables (activos y con is_redeemable = true)
        $combos = Combo::query()
            ->where('is_active', true)
            ->where('is_redeemable', true)
            ->orderBy('sort_order')
            ->get();

        // Combinar y ordenar por nombre
        $rewards = collect()
            ->concat($products->map(fn ($p) => ['item' => $p, 'type' => 'product']))
            ->concat($combos->map(fn ($c) => ['item' => $c, 'type' => 'combo']))
            ->sortBy(fn ($r) => $r['item']->name)
            ->values();

        return response()->json([
            'data' => RewardResource::collection($rewards),
        ]);
    }
}
