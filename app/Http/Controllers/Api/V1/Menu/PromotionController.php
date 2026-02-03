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
     *     description="Returns all active promotions separated by type.
     *
     * **Tipos de promociones:**
     * - `daily_special`: Sub del Día con precio especial
     * - `two_for_one`: 2x1 - por cada 2 productos, el más barato es gratis
     * - `percentage_discount`: Descuento porcentual (ej: 15% off)
     * - `bundle_special`: Combo promocional con precio fijo
     *
     * **Lógica del 2x1:**
     * - El descuento se calcula automáticamente en el carrito
     * - Por cada 2 productos de la misma promoción, el más barato es gratis
     * - Con 4 bebidas: las 2 más baratas son gratis
     * - Con 3 bebidas: solo 1 (la más barata) es gratis
     * - Los extras/opciones siempre se cobran al 100%
     *
     * **Mostrar badges:**
     * Los productos con promoción incluyen `active_promotion.badge` con `name`, `color` y `text_color` para mostrar en el menú.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Promotions retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="daily_special", ref="#/components/schemas/Promotion", nullable=true, description="Sub del Día - single object or null"),
     *                 @OA\Property(property="two_for_one", type="array", description="Promociones 2x1 - por cada 2 productos el mas barato es gratis",
     *
     *                     @OA\Items(ref="#/components/schemas/Promotion")
     *                 ),
     *
     *                 @OA\Property(property="percentage_discounts", type="array", description="Descuentos porcentuales",
     *
     *                     @OA\Items(ref="#/components/schemas/Promotion")
     *                 ),
     *
     *                 @OA\Property(property="bundle_specials", type="array", description="Combos promocionales con precio fijo",
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
                'badgeType',
                'items' => function ($query) {
                    $query->where(function ($q) {
                        // Items con producto activo
                        $q->whereHas('product', fn ($p) => $p->where('is_active', true))
                            // O items con variante cuyo producto está activo
                            ->orWhereHas('variant.product', fn ($p) => $p->where('is_active', true))
                            // O items por categoría (sin producto/variante específico)
                            ->orWhere(function ($sub) {
                                $sub->whereNotNull('category_id')
                                    ->whereNull('product_id')
                                    ->whereNull('variant_id');
                            });
                    })->with([
                        'product' => fn ($p) => $p->where('is_active', true),
                        'variant' => fn ($v) => $v->whereHas('product', fn ($p) => $p->where('is_active', true)),
                        'category',
                    ]);
                },
                'bundleItems' => function ($query) {
                    $query->orderBy('sort_order')
                        ->where(function ($q) {
                            // Items fijos con producto activo
                            $q->where(function ($sub) {
                                $sub->where('is_choice_group', false)
                                    ->whereHas('product', fn ($p) => $p->where('is_active', true));
                            })
                            // O grupos de elección (se filtran las opciones después)
                                ->orWhere('is_choice_group', true);
                        })
                        ->with([
                            'product' => fn ($p) => $p->where('is_active', true),
                            'variant',
                            'options' => function ($q) {
                                $q->orderBy('sort_order')
                                    ->whereHas('product', fn ($p) => $p->where('is_active', true))
                                    ->with([
                                        'product' => fn ($p) => $p->where('is_active', true),
                                        'variant',
                                    ]);
                            },
                        ]);
                },
            ])
            ->get();

        // Agrupar por tipo para facilitar consumo del frontend
        $grouped = $promotions->groupBy('type');

        // Filtrar promociones que requieren items (excluir las que quedaron sin items activos)
        $filterWithItems = fn ($promotion) => $promotion->items->isNotEmpty();

        // daily_special: objeto único (solo hay 1 sub del día), solo si tiene items
        $dailySpecial = $grouped->get('daily_special', collect())
            ->filter($filterWithItems)
            ->first();

        // two_for_one y percentage_discount: solo las que tienen items activos
        $twoForOne = $grouped->get('two_for_one', collect())->filter($filterWithItems);
        $percentageDiscounts = $grouped->get('percentage_discount', collect())->filter($filterWithItems);

        // Filtrar bundle_specials que tienen choice groups sin opciones activas
        $bundleSpecials = $grouped->get('bundle_special', collect())->filter(function ($promotion) {
            foreach ($promotion->bundleItems as $item) {
                // Si es un choice group, debe tener al menos 1 opción activa
                if ($item->is_choice_group && $item->options->isEmpty()) {
                    return false;
                }
            }

            return true;
        });

        return response()->json([
            'data' => [
                'daily_special' => $dailySpecial ? PromotionResource::make($dailySpecial) : null,
                'two_for_one' => PromotionResource::collection($twoForOne),
                'percentage_discounts' => PromotionResource::collection($percentageDiscounts),
                'bundle_specials' => PromotionResource::collection($bundleSpecials),
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
     *     description="Returns active daily special promotion (Sub del Día). Without parameters, returns ALL items for the entire week. Use ?today=1 to filter items by: weekday, date range (valid_from/valid_until), and time range (time_from/time_until). Multiple subs can be valid for the same day. Each item has a 'weekdays' array (1=Monday to 7=Sunday, ISO-8601).",
     *
     *     @OA\Parameter(
     *         name="today",
     *         in="query",
     *         description="Filter by current moment. If set to 1, returns only items valid RIGHT NOW based on: weekday, date range, and time range. Multiple items may be returned if more than one sub is available.",
     *         required=false,
     *
     *         @OA\Schema(type="integer", enum={0, 1}, default=0)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Daily special retrieved successfully. When using ?today=1, 'now' object is included with current datetime info.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="promotion", type="object", description="The daily special promotion containing filtered items",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="name", type="string", example="Sub del Día"),
     *                     @OA\Property(property="type", type="string", example="daily_special"),
     *                     @OA\Property(property="items", type="array", description="Array of subs valid for the period.",
     *
     *                         @OA\Items(type="object",
     *
     *                             @OA\Property(property="id", type="integer", example=14),
     *                             @OA\Property(property="weekdays", type="array", description="Days when available (1=Mon, 7=Sun)",
     *
     *                                 @OA\Items(type="integer", example=2)
     *                             ),
     *
     *                             @OA\Property(property="valid_from", type="string", format="date", nullable=true, example="2024-01-01"),
     *                             @OA\Property(property="valid_until", type="string", format="date", nullable=true, example="2024-12-31"),
     *                             @OA\Property(property="time_from", type="string", nullable=true, example="11:00"),
     *                             @OA\Property(property="time_until", type="string", nullable=true, example="15:00"),
     *                             @OA\Property(property="special_price_pickup_capital", type="number", format="float", nullable=true, example=22.00),
     *                             @OA\Property(property="special_price_delivery_capital", type="number", format="float", nullable=true, example=22.00),
     *                             @OA\Property(property="special_price_pickup_interior", type="number", format="float", nullable=true, example=24.00),
     *                             @OA\Property(property="special_price_delivery_interior", type="number", format="float", nullable=true, example=24.00),
     *                             @OA\Property(property="discounted_prices", type="object", nullable=true,
     *                                 @OA\Property(property="pickup_capital", type="number", format="float", nullable=true, example=22.00),
     *                                 @OA\Property(property="delivery_capital", type="number", format="float", nullable=true, example=22.00),
     *                                 @OA\Property(property="pickup_interior", type="number", format="float", nullable=true, example=24.00),
     *                                 @OA\Property(property="delivery_interior", type="number", format="float", nullable=true, example=24.00)
     *                             ),
     *                             @OA\Property(property="product", type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name", type="string", example="Italian B.M.T."),
     *                                 @OA\Property(property="image_url", type="string", nullable=true)
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="today", type="object", nullable=true, description="Only present when ?today=1",
     *                     @OA\Property(property="weekday", type="integer", example=2, description="Current weekday (1-7)"),
     *                     @OA\Property(property="weekday_name", type="string", example="Martes"),
     *                     @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
     *                     @OA\Property(property="time", type="string", example="12:30"),
     *                     @OA\Property(property="datetime", type="string", format="date-time", example="2024-01-15T12:30:00-06:00")
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
     *             @OA\Property(property="message", type="string", example="Sub del Día no disponible")
     *         )
     *     )
     * )
     */
    public function daily(Request $request): JsonResponse
    {
        $filterToday = $request->boolean('today');
        $now = now();
        $currentWeekday = (int) $now->dayOfWeekIso; // 1=Lunes, 7=Domingo

        $promotion = Promotion::query()
            ->active()
            ->dailySpecial()
            ->with([
                'items' => function ($query) {
                    $query->where(function ($q) {
                        // Items con producto activo
                        $q->whereHas('product', fn ($p) => $p->where('is_active', true))
                            // O items con variante cuyo producto está activo
                            ->orWhereHas('variant.product', fn ($p) => $p->where('is_active', true))
                            // O items por categoría (sin producto/variante específico)
                            ->orWhere(function ($sub) {
                                $sub->whereNotNull('category_id')
                                    ->whereNull('product_id')
                                    ->whereNull('variant_id');
                            });
                    })->with([
                        'product' => fn ($p) => $p->where('is_active', true),
                        'variant' => fn ($v) => $v->whereHas('product', fn ($p) => $p->where('is_active', true)),
                        'category',
                    ]);
                },
            ])
            ->first();

        // Verificar que existe y tiene items con productos activos
        if (! $promotion || $promotion->items->isEmpty()) {
            return response()->json([
                'message' => 'Sub del Día no disponible',
            ], 404);
        }

        // Si se solicita solo el de hoy, filtrar los items por:
        // - weekdays (días de la semana)
        // - valid_from/valid_until (rango de fechas)
        // - time_from/time_until (rango de horas)
        if ($filterToday && $promotion->items) {
            $todayItems = $promotion->items->filter(function ($item) use ($now) {
                // Usar el método del modelo que valida TODO:
                // weekdays, date_range, time_range, date_time_range, permanent
                return $item->isValidToday($now);
            });

            // Reemplazar la colección de items con los filtrados
            $promotion->setRelation('items', $todayItems->values());
        }

        $response = [
            'data' => [
                'promotion' => PromotionResource::make($promotion),
            ],
        ];

        // Agregar información del momento actual si se filtró
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
                'date' => $now->toDateString(),
                'time' => $now->format('H:i'),
                'datetime' => $now->toIso8601String(),
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
     *     summary="Get bundle specials (Combinados)",
     *     description="Returns active bundle special promotions valid now (Combinados). Filters by: is_active, valid_from/valid_until dates, time_from/time_until hours, and weekdays. Only returns promotions with all required items available.",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Bundle specials retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="promotions", type="array", description="List of active bundle specials",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="name", type="string", example="Combo Familiar"),
     *                         @OA\Property(property="description", type="string", nullable=true, example="2 subs + 2 bebidas + papas"),
     *                         @OA\Property(property="image_url", type="string", nullable=true),
     *                         @OA\Property(property="type", type="string", example="bundle_special"),
     *                         @OA\Property(property="prices", type="object", description="Prices by zone and service type",
     *                             @OA\Property(property="pickup_capital", type="number", format="float", nullable=true, example=85.00),
     *                             @OA\Property(property="delivery_capital", type="number", format="float", nullable=true, example=90.00),
     *                             @OA\Property(property="pickup_interior", type="number", format="float", nullable=true, example=90.00),
     *                             @OA\Property(property="delivery_interior", type="number", format="float", nullable=true, example=95.00)
     *                         ),
     *                         @OA\Property(property="valid_from", type="string", format="date", nullable=true, example="2024-01-01"),
     *                         @OA\Property(property="valid_until", type="string", format="date", nullable=true, example="2024-12-31"),
     *                         @OA\Property(property="time_from", type="string", nullable=true, example="11:00"),
     *                         @OA\Property(property="time_until", type="string", nullable=true, example="22:00"),
     *                         @OA\Property(property="weekdays", type="array", nullable=true, description="Days when available (1=Mon, 7=Sun). Null means all days.",
     *
     *                             @OA\Items(type="integer", example=1)
     *                         ),
     *
     *                         @OA\Property(property="bundle_items", type="array", description="Items included in the bundle",
     *
     *                             @OA\Items(type="object",
     *
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name", type="string", example="Sub 15cm"),
     *                                 @OA\Property(property="is_choice_group", type="boolean", example=true),
     *                                 @OA\Property(property="quantity", type="integer", example=2),
     *                                 @OA\Property(property="product", type="object", nullable=true),
     *                                 @OA\Property(property="options", type="array", description="Available choices if is_choice_group=true",
     *
     *                                     @OA\Items(type="object")
     *                                 )
     *                             )
     *                         )
     *                     )
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
                        ->where(function ($q) {
                            // Items fijos con producto activo
                            $q->where(function ($sub) {
                                $sub->where('is_choice_group', false)
                                    ->whereHas('product', fn ($p) => $p->where('is_active', true));
                            })
                            // O grupos de elección (se filtran las opciones después)
                                ->orWhere('is_choice_group', true);
                        })
                        ->with([
                            'product' => fn ($p) => $p->where('is_active', true),
                            'variant',
                            'options' => function ($q) {
                                $q->orderBy('sort_order')
                                    ->whereHas('product', fn ($p) => $p->where('is_active', true))
                                    ->with([
                                        'product' => fn ($p) => $p->where('is_active', true),
                                        'variant',
                                    ]);
                            },
                        ]);
                },
                'badgeType',
            ])
            ->get()
            // Filtrar combinados que tienen choice groups sin opciones activas
            // (igual que se hace con combos en ComboController)
            ->filter(function ($promotion) {
                foreach ($promotion->bundleItems as $item) {
                    if ($item->is_choice_group && $item->options->isEmpty()) {
                        return false;
                    }
                }

                return true;
            });

        return response()->json([
            'data' => [
                'combinados' => PromotionResource::collection($promotions),
            ],
        ]);
    }

    /**
     * Get a specific combinado by ID.
     *
     * @OA\Get(
     *     path="/api/v1/menu/promotions/combinados/{id}",
     *     tags={"Menu"},
     *     summary="Get combinado details",
     *     description="Returns a specific bundle special promotion if valid now.",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Combinado ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Combinado retrieved successfully"
     *     ),
     *     @OA\Response(response=404, description="Combinado not found or not available")
     * )
     */
    public function showCombinado(int $id): JsonResponse
    {
        $promotion = Promotion::query()
            ->validNowCombinados()
            ->available()
            ->with([
                'bundleItems' => function ($query) {
                    $query->orderBy('sort_order')
                        ->where(function ($q) {
                            $q->where(function ($sub) {
                                $sub->where('is_choice_group', false)
                                    ->whereHas('product', fn ($p) => $p->where('is_active', true));
                            })
                                ->orWhere('is_choice_group', true);
                        })
                        ->with([
                            'product' => fn ($p) => $p->where('is_active', true),
                            'variant',
                            'options' => function ($q) {
                                $q->orderBy('sort_order')
                                    ->whereHas('product', fn ($p) => $p->where('is_active', true))
                                    ->with([
                                        'product' => fn ($p) => $p->where('is_active', true),
                                        'variant',
                                    ]);
                            },
                        ]);
                },
                'badgeType',
            ])
            ->findOrFail($id);

        // Verificar que el combinado tenga todos sus choice groups con opciones activas
        foreach ($promotion->bundleItems as $item) {
            if ($item->is_choice_group && $item->options->isEmpty()) {
                abort(404, 'Combinado no disponible');
            }
        }

        return response()->json([
            'data' => [
                'combinado' => PromotionResource::make($promotion),
            ],
        ]);
    }
}
