<?php

namespace App\Http\Controllers\Api\V1\Menu;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\ProductResource;
use App\Models\Menu\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get list of products with filters.
     *
     * @OA\Get(
     *     path="/api/v1/menu/products",
     *     tags={"Menu"},
     *     summary="Get list of products",
     *     description="Returns list of products with optional filters.",
     *
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter by category ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by product name",
     *         required=false,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="has_variants",
     *         in="query",
     *         description="Filter by has_variants (true/false)",
     *         required=false,
     *
     *         @OA\Schema(type="boolean")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Products retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="products", type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Product")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->active()
            ->with([
                'variants' => function ($q) {
                    $q->active()->ordered();
                },
                'sections.options',
                'badges',
            ]);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('has_variants')) {
            $hasVariants = filter_var($request->input('has_variants'), FILTER_VALIDATE_BOOLEAN);
            $query->where('has_variants', $hasVariants);
        }

        $products = $query->ordered()->get();

        return response()->json([
            'data' => [
                'products' => ProductResource::collection($products),
            ],
        ]);
    }

    /**
     * Get product details.
     *
     * @OA\Get(
     *     path="/api/v1/menu/products/{id}",
     *     tags={"Menu"},
     *     summary="Get product details",
     *     description="Returns product with variants, sections and badges.",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="product", ref="#/components/schemas/Product")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Product not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::query()
            ->active()
            ->with([
                'variants' => function ($q) {
                    $q->active()->ordered();
                },
                'sections.options',
                'badges',
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'product' => ProductResource::make($product),
            ],
        ]);
    }
}
