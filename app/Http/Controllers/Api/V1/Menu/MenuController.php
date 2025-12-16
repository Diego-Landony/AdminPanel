<?php

namespace App\Http\Controllers\Api\V1\Menu;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\CategoryResource;
use App\Http\Resources\Api\V1\Menu\ComboResource;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    /**
     * Get complete menu grouped by categories.
     *
     * @OA\Get(
     *     path="/api/v1/menu",
     *     tags={"Menu"},
     *     summary="Get complete menu",
     *     description="Returns complete menu with categories, products and permanent combos. For promotions/offers, use GET /api/v1/menu/promotions.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Menu retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="categories", type="array", description="Product categories with their products",
     *
     *                     @OA\Items(ref="#/components/schemas/Category")
     *                 ),
     *
     *                 @OA\Property(property="combos", type="array", description="Permanent menu combos (not promotional)",
     *
     *                     @OA\Items(ref="#/components/schemas/Combo")
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
            ->where('is_combo_category', false)
            ->with([
                'products' => function ($query) {
                    $query->active()
                        ->ordered()
                        ->with([
                            'variants' => function ($q) {
                                $q->active()->ordered();
                            },
                            'sections.options',
                            'badges.badgeType',
                        ]);
                },
            ])
            ->get();

        $combos = Combo::query()
            ->active()
            ->available()
            ->ordered()
            ->with([
                'items.product',
                'items.variant',
                'items.options.product',
                'items.options.variant',
                'badges',
            ])
            ->get();

        return response()->json([
            'data' => [
                'categories' => CategoryResource::collection($categories),
                'combos' => ComboResource::collection($combos),
            ],
        ]);
    }
}
