<?php

namespace App\Http\Controllers\Api\V1\Menu;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\CategoryResource;
use App\Http\Resources\Api\V1\Menu\ComboResource;
use App\Http\Resources\Api\V1\Menu\ProductResource;
use App\Models\Menu\BadgeType;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\PromotionalBanner;
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
     *     description="Returns complete menu with categories, products and permanent combos.
     *
     * **⚠️ IMPORTANTE - Disclaimer de Precios:**
     * El campo `price` muestra el precio de PICKUP en CAPITAL (precio base de referencia).
     * Este NO es el precio de delivery ni el precio en interior.
     * Puede variar segun area (capital/interior) y tipo de servicio (pickup/delivery).
     *
     * El precio final se calcula automaticamente cuando el usuario:
     * - Selecciona un restaurante para pickup (PUT /cart/restaurant)
     * - Selecciona una direccion para delivery (PUT /cart/delivery-address)
     *
     * **📋 IMPORTANTE - Ordenamiento del Menú:**
     * Flutter debe combinar `categories` y `combos` usando el campo `sort_order` para mostrar
     * el menú en el orden configurado por el administrador.
     *
     * 1. `categories[]` - Solo incluye categorías normales (is_combo_category = false)
     * 2. `combos_category` - Categoría especial para combos con su `sort_order`
     * 3. `combos[]` - Lista de combos que van en esa sección
     *
     * Flutter debe:
     * - Combinar ambos arrays usando `sort_order`
     * - Ordenar todo de menor a mayor
     * - Permitir orden mixto (ej: Productos, Combos, Productos)
     * - Ordenar productos dentro de cada categoría por `product.sort_order`
     * - Ordenar combos dentro de la sección por `combo.sort_order`
     *
     * For promotions/offers, use GET /api/v1/menu/promotions.",
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
     *                 @OA\Property(property="price_disclaimer", type="string", example="Precio sujeto a ubicación/servicio.", description="Disclaimer obligatorio a mostrar en UI junto a los precios"),
     *                 @OA\Property(property="categories", type="array", description="Product categories (excludes combo category). Order by sort_order.",
     *
     *                     @OA\Items(ref="#/components/schemas/Category")
     *                 ),
     *
     *                 @OA\Property(property="combos_category", type="object", description="Special category for combos section with sort_order to position it among other categories",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="name", type="string", example="Combos"),
     *                     @OA\Property(property="image_url", type="string", example="/storage/categories/combos.jpg", nullable=true),
     *                     @OA\Property(property="sort_order", type="integer", example=2, description="Position in menu - mix with categories sort_order")
     *                 ),
     *
     *                 @OA\Property(property="combos", type="array", description="Permanent menu combos (not promotional). Order by sort_order within combos section.",
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
        // NOTE: Variants now show active_promotion which calls getActivePromotion().
        // This method needs access to product.category for promotion lookup.
        // For N+1 optimization, consider caching active promotions or using a
        // dedicated PromotionService that bulk-loads promotions for all variants.
        $categories = Category::query()
            ->active()
            ->ordered()
            ->where('is_combo_category', false)
            ->with([
                'products' => function ($query) {
                    $query->active()
                        ->ordered()
                        ->with([
                            'category', // Needed for variant promotion lookup
                            'variants' => function ($q) {
                                $q->active()->ordered()->with('product.category');
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

        // Get the combos category for Flutter to know where to position combos section
        $combosCategory = Category::query()
            ->where('is_combo_category', true)
            ->active()
            ->first(['id', 'name', 'image', 'sort_order']);

        return response()->json([
            'data' => [
                'price_disclaimer' => 'Precio sujeto a ubicación/servicio.',
                'categories' => CategoryResource::collection($categories),
                'combos_category' => $combosCategory ? [
                    'id' => $combosCategory->id,
                    'name' => $combosCategory->name,
                    'image_url' => $combosCategory->getImageUrl(),
                    'sort_order' => $combosCategory->sort_order,
                ] : null,
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

        // Get the combos category for Flutter to know where to position combos section
        $combosCategory = Category::query()
            ->where('is_combo_category', true)
            ->active()
            ->first(['id', 'name', 'image', 'sort_order']);

        return response()->json([
            'data' => [
                'price_disclaimer' => 'Precio sujeto a ubicación/servicio.',
                'categories' => $categories->map(fn ($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'image_url' => $cat->image ? Storage::url($cat->image) : null,
                    'products_count' => $cat->products_count,
                    'sort_order' => $cat->sort_order,
                ]),
                'combos_category' => $combosCategory ? [
                    'id' => $combosCategory->id,
                    'name' => $combosCategory->name,
                    'image_url' => $combosCategory->getImageUrl(),
                    'sort_order' => $combosCategory->sort_order,
                ] : null,
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

    /**
     * Get featured products and combos grouped by badge type.
     *
     * @OA\Get(
     *     path="/api/v1/menu/featured",
     *     tags={"Menu"},
     *     summary="Get featured products and combos",
     *     description="Returns products and combos that have active badges, along with available badge types.
     *
     * Flutter can use this endpoint to build carousels for each badge type (Popular, Best Seller, New, etc.).
     *
     * **Usage:**
     * 1. Get all badge_types from the response
     * 2. Filter products/combos by badge_type_id
     * 3. Render a carousel for each badge_type that has items
     *
     * Badge types are ordered by sort_order, so render carousels in that order.",
     *
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of products/combos to return per badge type (default: 10)",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=10, minimum=1, maximum=50)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Featured items retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="badge_types", type="array", description="Available badge types ordered by sort_order",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Popular"),
     *                         @OA\Property(property="color", type="string", example="orange"),
     *                         @OA\Property(property="sort_order", type="integer", example=1)
     *                     )
     *                 ),
     *                 @OA\Property(property="products", type="array", description="Products with active badges",
     *
     *                     @OA\Items(ref="#/components/schemas/Product")
     *                 ),
     *
     *                 @OA\Property(property="combos", type="array", description="Combos with active badges",
     *
     *                     @OA\Items(ref="#/components/schemas/Combo")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 10), 50);

        // Get active badge types ordered by sort_order
        $badgeTypes = BadgeType::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'color', 'sort_order']);

        // Get products that have at least one active badge
        // NOTE: Variants need product.category for active_promotion lookup
        $products = Product::query()
            ->active()
            ->whereHas('activeBadges')
            ->with([
                'category', // Needed for variant promotion lookup
                'variants' => fn ($q) => $q->active()->ordered()->with('product.category'),
                'activeBadges.badgeType',
            ])
            ->ordered()
            ->limit($limit)
            ->get();

        // Get combos that have at least one active badge
        $combos = Combo::query()
            ->active()
            ->available()
            ->whereHas('activeBadges')
            ->with([
                'items.product',
                'items.variant',
                'activeBadges.badgeType',
            ])
            ->ordered()
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => [
                'badge_types' => $badgeTypes->map(fn ($bt) => [
                    'id' => $bt->id,
                    'name' => $bt->name,
                    'color' => $bt->color,
                    'sort_order' => $bt->sort_order,
                ]),
                'products' => ProductResource::collection($products),
                'combos' => ComboResource::collection($combos),
            ],
        ]);
    }

    /**
     * Get promotional banners for Home Screen.
     *
     * @OA\Get(
     *     path="/api/v1/menu/banners",
     *     tags={"Menu"},
     *     summary="Get promotional banners",
     *     description="Returns active promotional banners separated by orientation.
     *
     * **Response structure:**
     * - `horizontal` - Banners for the main carousel in Home Screen
     * - `vertical` - Banners for stories, popups, or other vertical placements
     *
     * Both arrays are filtered by active status and valid date range/weekdays.
     *
     * **Link Types:**
     * - `product` - Navigate to product detail screen
     * - `combo` - Navigate to combo detail screen
     * - `category` - Navigate to category screen
     * - `promotion` - Navigate to promotion detail
     * - `url` - Open external URL in browser
     * - `null` - No action on tap",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Banners retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="horizontal", type="array", description="Horizontal banners for main carousel",
     *
     *                     @OA\Items(ref="#/components/schemas/Banner")
     *                 ),
     *
     *                 @OA\Property(property="vertical", type="array", description="Vertical banners for stories/popups",
     *
     *                     @OA\Items(ref="#/components/schemas/Banner")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function banners(): JsonResponse
    {
        $allBanners = PromotionalBanner::query()
            ->validNow()
            ->ordered()
            ->get();

        $mapBanner = fn ($banner) => [
            'id' => $banner->id,
            'title' => $banner->title,
            'description' => $banner->description,
            'image_url' => $banner->getImageUrl(),
            'orientation' => $banner->orientation,
            'display_seconds' => $banner->display_seconds,
            'link' => $banner->getLinkData(),
        ];

        return response()->json([
            'data' => [
                'horizontal' => $allBanners->where('orientation', 'horizontal')->values()->map($mapBanner),
                'vertical' => $allBanners->where('orientation', 'vertical')->values()->map($mapBanner),
            ],
        ]);
    }
}
