<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StorePromotionRequest;
use App\Http\Requests\Menu\UpdatePromotionRequest;
use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\Promotion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para gestionar promociones del menú
 */
class PromotionController extends Controller
{
    /**
     * Muestra listado de promociones con filtros y búsqueda
     */
    public function index(Request $request, ?string $typeFilter = null, ?string $view = null): Response
    {
        $query = Promotion::query()
            ->withCount('items');

        // Filtro por tipo si se proporciona
        if ($typeFilter) {
            $query->where('type', $typeFilter);
        }

        // Búsqueda por nombre
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtro por tipo
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filtro por estado
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filtro por aplicabilidad - comentado porque applies_to no existe en la tabla promotions
        // if ($request->filled('applies_to')) {
        //     $query->where('applies_to', $request->input('applies_to'));
        // }

        // Filtro por vigencia actual - comentado porque valid_from/valid_until están en promotion_items
        // if ($request->boolean('only_valid')) {
        //     $query->where('is_active', true)
        //         ->whereHas('items', function ($q) {
        //             $q->where(function ($q2) {
        //                 $q2->whereNull('valid_from')
        //                     ->orWhere('valid_from', '<=', now());
        //             })
        //             ->where(function ($q2) {
        //                 $q2->whereNull('valid_until')
        //                     ->orWhere('valid_until', '>=', now());
        //             });
        //         });
        // }

        // Ordenamiento
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        if (in_array($sortField, ['name', 'type', 'is_active', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection);
        } elseif ($sortField === 'items_count') {
            $query->orderBy('items_count', $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = (int) $request->input('per_page', 10);
        $promotions = $query->with(['items.variant.product', 'items.product', 'items.category'])
            ->paginate($perPage)
            ->withQueryString();

        // Transformar promociones para aplanar datos del primer item
        $promotions->getCollection()->transform(function ($promotion) {
            $firstItem = $promotion->items->first();

            if ($firstItem) {
                $promotion->scope_type = 'product';
                $promotion->special_price_capital = $firstItem->special_price_capital;
                $promotion->special_price_interior = $firstItem->special_price_interior;
                $promotion->applies_to = 'product';
                $promotion->service_type = $firstItem->service_type ?? 'both';
                $promotion->validity_type = $firstItem->validity_type ?? 'permanent';
                $promotion->is_permanent = $firstItem->validity_type === 'permanent';
                $promotion->valid_from = $firstItem->valid_from;
                $promotion->valid_until = $firstItem->valid_until;
                $promotion->has_time_restriction = ! empty($firstItem->time_from) && ! empty($firstItem->time_until);
                $promotion->time_from = $firstItem->time_from;
                $promotion->time_until = $firstItem->time_until;
                $promotion->active_days = $firstItem->weekdays;
                $promotion->weekdays = $firstItem->weekdays;
            }

            return $promotion;
        });

        // Calcular estadísticas - filtrar por tipo si se proporciona
        $statsQuery = Promotion::query();
        if ($typeFilter) {
            $statsQuery->where('type', $typeFilter);
        }
        $allPromotions = $statsQuery->get();
        $activePromotions = $allPromotions->where('is_active', true);

        // Promociones válidas ahora (activas + vigentes)
        $validNowPromotions = $activePromotions->filter(function ($promotion) {
            return $promotion->isValidNow();
        });

        // Determinar qué vista renderizar
        $viewName = $view ?? 'menu/promotions/index';

        return Inertia::render($viewName, [
            'promotions' => $promotions,
            'stats' => [
                'total_promotions' => $allPromotions->count(),
                'active_promotions' => $activePromotions->count(),
                'valid_now_promotions' => $validNowPromotions->count(),
            ],
            'filters' => [
                'search' => $request->input('search'),
                'per_page' => $perPage,
                'sort_field' => $request->filled('sort_field') ? $sortField : null,
                'sort_direction' => $request->filled('sort_direction') ? $sortDirection : null,
            ],
        ]);
    }

    /**
     * Muestra formulario de creación
     */
    public function create(): Response
    {
        return Inertia::render('menu/promotions/create', [
            'products' => Product::query()
                ->with('category')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'category_id']),
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Almacena una nueva promoción con sus items
     */
    public function store(StorePromotionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $promotion = null;

        DB::transaction(function () use ($validated, $items, &$promotion) {
            $promotion = Promotion::create($validated);

            // Crear los items
            if (! empty($items)) {
                foreach ($items as $item) {
                    $promotion->items()->create([
                        'product_id' => $item['product_id'] ?? null,
                        'variant_id' => $item['variant_id'] ?? null,
                        'category_id' => $item['category_id'] ?? null,
                        // Campos para Sub del Día
                        'special_price_capital' => $item['special_price_capital'] ?? null,
                        'special_price_interior' => $item['special_price_interior'] ?? null,
                        // Campo para Percentage Discount
                        'discount_percentage' => $item['discount_percentage'] ?? null,
                        // Campos comunes
                        'service_type' => $item['service_type'] ?? null,
                        'validity_type' => $item['validity_type'] ?? null,
                        'valid_from' => $item['valid_from'] ?? null,
                        'valid_until' => $item['valid_until'] ?? null,
                        'time_from' => $item['time_from'] ?? null,
                        'time_until' => $item['time_until'] ?? null,
                        'weekdays' => $item['weekdays'] ?? null,
                    ]);
                }
            }
        });

        // Redirigir según el tipo de promoción
        $routeMap = [
            'daily_special' => 'menu.promotions.daily-special.index',
            'two_for_one' => 'menu.promotions.two-for-one.index',
            'percentage_discount' => 'menu.promotions.percentage.index',
        ];

        $route = $routeMap[$promotion->type] ?? 'menu.promotions.index';

        return redirect()->route($route)
            ->with('success', 'Promoción creada exitosamente.');
    }

    /**
     * Muestra una promoción específica
     */
    public function show(Promotion $promotion): Response
    {
        $promotion->load([
            'items.product:id,name',
            'items.category:id,name',
        ]);

        return Inertia::render('menu/promotions/show', [
            'promotion' => $promotion,
        ]);
    }

    /**
     * Muestra formulario de edición
     */
    public function edit(Promotion $promotion): Response
    {
        $promotion->load([
            'items.product.variants',
            'items.variant.product',
            'items.category',
        ]);

        // Determinar la vista según el tipo de promoción
        $viewMap = [
            'daily_special' => 'menu/promotions/daily-special/edit',
            'two_for_one' => 'menu/promotions/two-for-one/edit',
            'percentage_discount' => 'menu/promotions/percentage/edit',
        ];

        $view = $viewMap[$promotion->type] ?? 'menu/promotions/edit';

        return Inertia::render($view, [
            'promotion' => $promotion,
            'products' => Product::query()
                ->with(['category:id,name', 'variants' => function ($query) {
                    $query->select(['id', 'product_id', 'name', 'size', 'precio_pickup_capital', 'precio_pickup_interior'])
                        ->orderBy('sort_order');
                }])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'has_variants']),
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Actualiza una promoción existente
     */
    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $validated = $request->validated();

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        DB::transaction(function () use ($validated, $items, $promotion) {
            $promotion->update($validated);

            // Actualizar items: eliminar todos y recrear
            $promotion->items()->delete();

            if (! empty($items)) {
                foreach ($items as $item) {
                    $promotion->items()->create([
                        'product_id' => $item['product_id'] ?? null,
                        'variant_id' => $item['variant_id'] ?? null,
                        'category_id' => $item['category_id'] ?? null,
                        // Campos para Sub del Día
                        'special_price_capital' => $item['special_price_capital'] ?? null,
                        'special_price_interior' => $item['special_price_interior'] ?? null,
                        // Campo para Percentage Discount
                        'discount_percentage' => $item['discount_percentage'] ?? null,
                        // Campos comunes
                        'service_type' => $item['service_type'] ?? null,
                        'validity_type' => $item['validity_type'] ?? null,
                        'valid_from' => $item['valid_from'] ?? null,
                        'valid_until' => $item['valid_until'] ?? null,
                        'time_from' => $item['time_from'] ?? null,
                        'time_until' => $item['time_until'] ?? null,
                        'weekdays' => $item['weekdays'] ?? null,
                    ]);
                }
            }
        });

        // Redirigir según el tipo de promoción
        $routeMap = [
            'daily_special' => 'menu.promotions.daily-special.index',
            'two_for_one' => 'menu.promotions.two-for-one.index',
            'percentage_discount' => 'menu.promotions.percentage.index',
        ];

        $route = $routeMap[$promotion->type] ?? 'menu.promotions.index';

        return redirect()->route($route)
            ->with('success', 'Promoción actualizada exitosamente.');
    }

    /**
     * Elimina una promoción
     */
    public function destroy(Promotion $promotion): RedirectResponse
    {
        try {
            $promotion->delete();

            return redirect()->route('menu.promotions.index')
                ->with('success', 'Promoción eliminada exitosamente.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al eliminar la promoción: '.$e->getMessage()]);
        }
    }

    /**
     * Activa o desactiva una promoción
     */
    public function toggle(Promotion $promotion): JsonResponse
    {
        $promotion->update([
            'is_active' => ! $promotion->is_active,
        ]);

        $status = $promotion->is_active ? 'activada' : 'desactivada';

        return response()->json([
            'success' => true,
            'message' => "Promoción {$status} exitosamente.",
            'is_active' => $promotion->is_active,
        ]);
    }

    /**
     * Preview de cómo se verá una promoción Sub del Día
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'special_price_capital' => 'required|numeric|min:0',
            'special_price_interior' => 'required|numeric|min:0',
            'service_type' => 'required|in:both,delivery_only,pickup_only',
            'weekdays' => 'required|array',
            'weekdays.*' => 'integer|min:1|max:7',
        ]);

        $product = Product::with(['variants' => function ($query) {
            $query->orderBy('sort_order');
        }])->findOrFail($validated['product_id']);

        // Obtener precios actuales del producto (primera variante como referencia)
        $variant = $product->variants->first();

        if (! $variant) {
            return response()->json([
                'error' => 'El producto no tiene variantes configuradas.',
            ], 400);
        }

        $dayNames = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];

        $selectedDays = array_map(fn ($day) => $dayNames[$day], $validated['weekdays']);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'image' => $product->image,
            ],
            'current_prices' => [
                'capital' => [
                    'pickup' => $variant->precio_pickup_capital,
                    'delivery' => $variant->precio_delivery_capital,
                ],
                'interior' => [
                    'pickup' => $variant->precio_pickup_interior,
                    'delivery' => $variant->precio_delivery_interior,
                ],
            ],
            'special_prices' => [
                'capital' => $validated['special_price_capital'],
                'interior' => $validated['special_price_interior'],
            ],
            'savings' => [
                'capital' => [
                    'pickup' => max(0, $variant->precio_pickup_capital - $validated['special_price_capital']),
                    'delivery' => max(0, $variant->precio_delivery_capital - $validated['special_price_capital']),
                ],
                'interior' => [
                    'pickup' => max(0, $variant->precio_pickup_interior - $validated['special_price_interior']),
                    'delivery' => max(0, $variant->precio_delivery_interior - $validated['special_price_interior']),
                ],
            ],
            'service_type' => $validated['service_type'],
            'applies_to_days' => $selectedDays,
            'variants_count' => $product->variants->count(),
        ]);
    }

    /**
     * Muestra listado de promociones Sub del Día
     */
    public function dailySpecialIndex(Request $request): Response
    {
        return $this->index($request, 'daily_special', 'menu/promotions/daily-special/index');
    }

    /**
     * Muestra formulario de creación para Sub del Día
     */
    public function createDailySpecial(): Response
    {
        return Inertia::render('menu/promotions/daily-special/create', [
            'products' => Product::query()
                ->with(['category:id,name', 'variants' => function ($query) {
                    $query->select(['id', 'product_id', 'name', 'size', 'precio_pickup_capital', 'precio_pickup_interior'])
                        ->orderBy('sort_order');
                }])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'has_variants']),
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Muestra listado de promociones 2x1
     */
    public function twoForOneIndex(Request $request): Response
    {
        return $this->index($request, 'two_for_one', 'menu/promotions/two-for-one/index');
    }

    /**
     * Muestra formulario de creación para 2x1
     */
    public function createTwoForOne(): Response
    {
        return Inertia::render('menu/promotions/two-for-one/create', [
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Muestra listado de promociones de Porcentaje
     */
    public function percentageIndex(Request $request): Response
    {
        return $this->index($request, 'percentage_discount', 'menu/promotions/percentage/index');
    }

    /**
     * Muestra formulario de creación para Porcentaje
     */
    public function createPercentage(): Response
    {
        return Inertia::render('menu/promotions/percentage/create', [
            'products' => Product::query()
                ->with(['category:id,name', 'variants' => function ($query) {
                    $query->select(['id', 'product_id', 'name', 'size', 'precio_pickup_capital', 'precio_pickup_interior'])
                        ->orderBy('sort_order');
                }])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'has_variants']),
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
