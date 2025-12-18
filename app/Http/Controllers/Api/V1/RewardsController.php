<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\ComboResource;
use App\Http\Resources\Api\V1\Menu\ProductResource;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use Illuminate\Http\JsonResponse;

class RewardsController extends Controller
{
    /**
     * Get all redeemable items (products, variants, and combos).
     *
     * @OA\Get(
     *     path="/api/v1/rewards",
     *     tags={"Rewards"},
     *     summary="Get redeemable items catalog",
     *     description="Returns all products, variants, and combos that can be redeemed with loyalty points. Items are grouped by type and sorted by points_cost ascending.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Redeemable items retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="products", type="array", description="Products WITHOUT variants redeemable with points",
     *                     @OA\Items(type="object")
     *                 ),
     *                 @OA\Property(property="variants", type="array", description="Product variants (sizes) redeemable with points",
     *                     @OA\Items(type="object")
     *                 ),
     *                 @OA\Property(property="combos", type="array", description="Combos redeemable with points",
     *                     @OA\Items(type="object")
     *                 ),
     *                 @OA\Property(property="total_count", type="integer", example=8, description="Total number of redeemable items")
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        // Get redeemable products WITHOUT variants (active and with points_cost set)
        $products = Product::query()
            ->where('is_active', true)
            ->where('is_redeemable', true)
            ->whereNotNull('points_cost')
            ->where('points_cost', '>', 0)
            ->whereDoesntHave('variants', function ($q) {
                $q->where('is_active', true);
            })
            ->with(['category'])
            ->orderBy('points_cost')
            ->orderBy('name')
            ->get();

        // Get redeemable variants (for products WITH variants)
        $variants = ProductVariant::query()
            ->where('is_active', true)
            ->where('is_redeemable', true)
            ->whereNotNull('points_cost')
            ->where('points_cost', '>', 0)
            ->whereHas('product', function ($q) {
                $q->where('is_active', true);
            })
            ->with(['product'])
            ->orderBy('points_cost')
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

        // Transform variants to a cleaner format
        $variantsData = $variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'size' => $variant->size,
                'image_url' => $variant->product->getImageUrl(),
                'points_cost' => $variant->points_cost,
                'is_redeemable' => true,
                'type' => 'variant',
            ];
        });

        return response()->json([
            'data' => [
                'products' => ProductResource::collection($products),
                'variants' => $variantsData,
                'combos' => ComboResource::collection($combos),
                'total_count' => $products->count() + $variants->count() + $combos->count(),
            ],
        ]);
    }
}
