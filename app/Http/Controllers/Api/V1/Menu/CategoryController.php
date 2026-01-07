<?php

namespace App\Http\Controllers\Api\V1\Menu;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\CategoryResource;
use App\Models\Menu\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Get list of active categories.
     *
     * @OA\Get(
     *     path="/api/v1/menu/categories",
     *     tags={"Menu"},
     *     summary="Get list of categories",
     *     description="Returns list of active categories ordered by sort_order.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Categories retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="categories", type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Category")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'data' => [
                'categories' => CategoryResource::collection($categories),
            ],
        ]);
    }

    /**
     * Get category with products.
     *
     * @OA\Get(
     *     path="/api/v1/menu/categories/{id}",
     *     tags={"Menu"},
     *     summary="Get category with products",
     *     description="Returns category with its active products.",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Category ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Category retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="category", ref="#/components/schemas/Category")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $category = Category::query()
            ->active()
            ->with([
                'products' => function ($query) {
                    $query->active()
                        ->ordered()
                        ->with([
                            'variants' => function ($q) {
                                $q->active()->ordered();
                            },
                            'activeBadges',
                        ]);
                },
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'category' => CategoryResource::make($category),
            ],
        ]);
    }
}
