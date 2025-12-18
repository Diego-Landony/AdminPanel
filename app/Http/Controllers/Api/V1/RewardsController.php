<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\ComboResource;
use App\Http\Resources\Api\V1\Menu\ProductResource;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use Illuminate\Http\JsonResponse;

class RewardsController extends Controller
{
    /**
     * Get all redeemable items (products and combos).
     *
     * @OA\Get(
     *     path="/api/v1/rewards",
     *     tags={"Rewards"},
     *     summary="Get redeemable items catalog",
     *     description="Returns all products and combos that can be redeemed with loyalty points. Items are grouped by type (products and combos) and sorted by points_cost ascending.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Redeemable items retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="products", type="array", description="Products redeemable with points",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="name", type="string", example="Cookie de Chocolate"),
     *                         @OA\Property(property="description", type="string", example="Deliciosa galleta con chispas de chocolate"),
     *                         @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/storage/products/cookie.jpg"),
     *                         @OA\Property(property="points_cost", type="integer", example=50, description="Points required to redeem"),
     *                         @OA\Property(property="is_redeemable", type="boolean", example=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="combos", type="array", description="Combos redeemable with points",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Combo Sub del Día"),
     *                         @OA\Property(property="description", type="string", example="Sub de 6 pulgadas + bebida + galleta"),
     *                         @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/storage/combos/combo-dia.jpg"),
     *                         @OA\Property(property="points_cost", type="integer", example=150, description="Points required to redeem"),
     *                         @OA\Property(property="is_redeemable", type="boolean", example=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="total_count", type="integer", example=8, description="Total number of redeemable items")
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        // Get redeemable products (active and with points_cost set)
        $products = Product::query()
            ->where('is_active', true)
            ->where('is_redeemable', true)
            ->whereNotNull('points_cost')
            ->where('points_cost', '>', 0)
            ->with(['category'])
            ->orderBy('points_cost')
            ->orderBy('name')
            ->get();

        // Get redeemable combos (active and with points_cost set)
        $combos = Combo::query()
            ->where('is_active', true)
            ->where('is_redeemable', true)
            ->whereNotNull('points_cost')
            ->where('points_cost', '>', 0)
            ->orderBy('points_cost')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => [
                'products' => ProductResource::collection($products),
                'combos' => ComboResource::collection($combos),
                'total_count' => $products->count() + $combos->count(),
            ],
        ]);
    }
}
