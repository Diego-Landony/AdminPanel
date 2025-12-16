<?php

namespace App\Http\Controllers\Api\V1\Menu;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Menu\PromotionResource;
use App\Models\Menu\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    /**
     * Get all active promotions grouped by type.
     *
     * @OA\Get(
     *     path="/api/v1/menu/promotions",
     *     tags={"Menu"},
     *     summary="Get all promotions grouped by type",
     *     description="Returns all active promotions separated by type: daily_special (single object), two_for_one, percentage_discounts, and bundle_specials.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Promotions retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="daily_special", ref="#/components/schemas/Promotion", nullable=true, description="Sub del Día - single object or null"),
     *                 @OA\Property(property="two_for_one", type="array", description="2x1 promotions",
     *
     *                     @OA\Items(ref="#/components/schemas/Promotion")
     *                 ),
     *
     *                 @OA\Property(property="percentage_discounts", type="array", description="Percentage discount promotions",
     *
     *                     @OA\Items(ref="#/components/schemas/Promotion")
     *                 ),
     *
     *                 @OA\Property(property="bundle_specials", type="array", description="Promotional combos (temporary bundles)",
     *
     *                     @OA\Items(ref="#/components/schemas/Promotion")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $promotions = Promotion::query()
            ->active()
            ->orderBy('sort_order')
            ->with([
                'items' => function ($query) {
                    $query->with(['product', 'variant', 'category']);
                },
                'bundleItems' => function ($query) {
                    $query->orderBy('sort_order')
                        ->with([
                            'product',
                            'variant',
                            'options' => function ($q) {
                                $q->orderBy('sort_order')
                                    ->with(['product', 'variant']);
                            },
                        ]);
                },
            ])
            ->get();

        // Agrupar por tipo para facilitar consumo del frontend
        $grouped = $promotions->groupBy('type');

        // daily_special: objeto único (solo hay 1 sub del día)
        $dailySpecial = $grouped->get('daily_special')?->first();

        return response()->json([
            'data' => [
                'daily_special' => $dailySpecial ? PromotionResource::make($dailySpecial) : null,
                'two_for_one' => PromotionResource::collection($grouped->get('two_for_one', collect())),
                'percentage_discounts' => PromotionResource::collection($grouped->get('percentage_discount', collect())),
                'bundle_specials' => PromotionResource::collection($grouped->get('bundle_special', collect())),
            ],
        ]);
    }

    /**
     * Get daily special (Sub del Día).
     *
     * @OA\Get(
     *     path="/api/v1/menu/promotions/daily",
     *     tags={"Menu"},
     *     summary="Get daily special (Sub del Día)",
     *     description="Returns active daily special promotion (Sub del Día). Without parameters, returns ALL items for the entire week. Use ?today=1 to filter and get only items valid for today's weekday. Note: Multiple subs can be valid for the same day (e.g., 'Italian B.M.T.' + 'Pechuga de Pollo' on Tuesday). Each item has a 'weekdays' array indicating which days it's available (1=Monday to 7=Sunday, ISO-8601 format).",
     *
     *     @OA\Parameter(
     *         name="today",
     *         in="query",
     *         description="Filter by today's weekday. If set to 1, returns only items where the current weekday is in their 'weekdays' array. Multiple items may be returned if more than one sub is available for today.",
     *         required=false,
     *
     *         @OA\Schema(type="integer", enum={0, 1}, default=0)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Daily special retrieved successfully. The 'items' array inside 'promotion' contains all subs valid for the requested period. When using ?today=1, 'today' object is included with current weekday info.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="promotion", type="object", description="The daily special promotion containing filtered items",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="name", type="string", example="Sub del Día"),
     *                     @OA\Property(property="type", type="string", example="daily_special"),
     *                     @OA\Property(property="items", type="array", description="Array of subs valid for the period. Multiple items can appear for the same day.",
     *                         @OA\Items(type="object",
     *                             @OA\Property(property="id", type="integer", example=14),
     *                             @OA\Property(property="weekdays", type="array", description="Days when this item is available (1=Mon, 7=Sun)",
     *                                 @OA\Items(type="integer", example=2)
     *                             ),
     *                             @OA\Property(property="special_price_capital", type="string", example="22.00"),
     *                             @OA\Property(property="special_price_interior", type="string", example="24.00"),
     *                             @OA\Property(property="product", type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name", type="string", example="Italian B.M.T."),
     *                                 @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/storage/products/italian-bmt.jpg")
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="today", type="object", nullable=true, description="Only present when ?today=1 is used",
     *                     @OA\Property(property="weekday", type="integer", example=2, description="Current weekday (1=Monday to 7=Sunday)"),
     *                     @OA\Property(property="weekday_name", type="string", example="Martes", description="Weekday name in Spanish")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="No daily special promotion configured or active",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No hay Sub del Día disponible.")
     *         )
     *     )
     * )
     */
    public function daily(Request $request): JsonResponse
    {
        $filterToday = $request->boolean('today');
        $currentWeekday = (int) now()->dayOfWeekIso; // 1=Lunes, 7=Domingo

        $promotion = Promotion::query()
            ->active()
            ->dailySpecial()
            ->with([
                'items' => function ($query) {
                    $query->with(['product', 'variant', 'category']);
                },
            ])
            ->first();

        if (! $promotion) {
            return response()->json([
                'message' => 'No hay Sub del Día disponible.',
            ], 404);
        }

        // Si se solicita solo el de hoy, filtrar los items
        if ($filterToday && $promotion->items) {
            $todayItems = $promotion->items->filter(function ($item) use ($currentWeekday) {
                // Si weekdays es null o vacío, el item es válido todos los días
                if (empty($item->weekdays)) {
                    return true;
                }

                return in_array($currentWeekday, $item->weekdays);
            });

            // Reemplazar la colección de items con los filtrados
            $promotion->setRelation('items', $todayItems->values());
        }

        $response = [
            'data' => [
                'promotion' => PromotionResource::make($promotion),
            ],
        ];

        // Agregar información del día actual si se filtró
        if ($filterToday) {
            $weekdayNames = [
                1 => 'Lunes',
                2 => 'Martes',
                3 => 'Miércoles',
                4 => 'Jueves',
                5 => 'Viernes',
                6 => 'Sábado',
                7 => 'Domingo',
            ];

            $response['data']['today'] = [
                'weekday' => $currentWeekday,
                'weekday_name' => $weekdayNames[$currentWeekday],
            ];
        }

        return response()->json($response);
    }

    /**
     * Get active bundle specials (Combinados).
     *
     * @OA\Get(
     *     path="/api/v1/menu/promotions/combinados",
     *     tags={"Menu"},
     *     summary="Get bundle specials",
     *     description="Returns active bundle special promotions valid now (Combinados).",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Bundle specials retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="promotions", type="array",
     *
     *                     @OA\Items(ref="#/components/schemas/Promotion")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function combinados(): JsonResponse
    {
        $promotions = Promotion::query()
            ->validNowCombinados()
            ->available()
            ->ordered()
            ->with([
                'bundleItems' => function ($query) {
                    $query->orderBy('sort_order')
                        ->with([
                            'product',
                            'variant',
                            'options' => function ($q) {
                                $q->orderBy('sort_order')
                                    ->with(['product', 'variant']);
                            },
                        ]);
                },
            ])
            ->get();

        return response()->json([
            'data' => [
                'promotions' => PromotionResource::collection($promotions),
            ],
        ]);
    }
}
