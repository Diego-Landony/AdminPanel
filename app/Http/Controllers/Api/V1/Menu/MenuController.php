<?php

namespace App\Http\Controllers\Api\V1\Menu;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\CategoryResource;
use App\Http\Resources\Api\V1\Menu\ComboResource;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
     *     @OA\Parameter(
     *         name="lite",
     *         in="query",
     *         description="If true, returns lightweight menu structure for navigation",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
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
    public function index(Request $request): JsonResponse
    {
        // Si se pide versión lite, retornar solo estructura
        if ($request->boolean('lite')) {
            return $this->indexLite();
        }
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
                            'sections' => function ($q) {
                                $q->orderByPivot('sort_order')->orderBy('sections.sort_order')->with('options');
                            },
                            'activeBadges',
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
                'activeBadges',
            ])
            ->get();

        return response()->json([
            'data' => [
                'categories' => CategoryResource::collection($categories),
                'combos' => ComboResource::collection($combos),
            ],
        ]);
    }

    /**
     * Get lightweight menu structure for initial navigation.
     */
    protected function indexLite(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->where('is_combo_category', false)
            ->withCount(['products' => fn ($q) => $q->active()])
            ->get(['id', 'name', 'image', 'sort_order']);

        $combosData = Combo::query()
            ->active()
            ->available()
            ->selectRaw('COUNT(*) as count, MIN(precio_pickup_capital) as min_price, MAX(precio_pickup_capital) as max_price')
            ->first();

        return response()->json([
            'data' => [
                'categories' => $categories->map(fn ($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'image_url' => $cat->image ? Storage::url($cat->image) : null,
                    'products_count' => $cat->products_count,
                    'sort_order' => $cat->sort_order,
                ]),
                'combos_summary' => [
                    'count' => (int) $combosData->count,
                    'price_range' => [
                        'min' => (float) ($combosData->min_price ?? 0),
                        'max' => (float) ($combosData->max_price ?? 0),
                    ],
                ],
            ],
        ]);
    }
}
