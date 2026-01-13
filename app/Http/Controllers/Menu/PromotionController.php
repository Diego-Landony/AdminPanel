<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreBundlePromotionRequest;
use App\Http\Requests\Menu\StorePromotionRequest;
use App\Http\Requests\Menu\UpdateBundlePromotionRequest;
use App\Http\Requests\Menu\UpdatePromotionRequest;
use App\Models\Menu\BadgeType;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
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

        $perPage = (int) $request->input('per_page', 15);
        $promotions = $query->with(['items.variant.product', 'items.product', 'items.combo', 'items.category'])
            ->paginate($perPage)
            ->withQueryString();

        // Transformar promociones para aplanar datos del primer item
        $promotions->getCollection()->transform(function ($promotion) {
            $firstItem = $promotion->items->first();

            if ($firstItem) {
                $promotion->scope_type = 'product';
                $promotion->special_price_pickup_capital = $firstItem->special_price_pickup_capital;
                $promotion->special_price_delivery_capital = $firstItem->special_price_delivery_capital;
                $promotion->special_price_pickup_interior = $firstItem->special_price_pickup_interior;
                $promotion->special_price_delivery_interior = $firstItem->special_price_delivery_interior;
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
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'is_active']),
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

        // Manejar la imagen si se subió
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('menu/promotions', 'public');
        }

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
                        // Campos para Sub del Día (4 precios independientes)
                        'special_price_pickup_capital' => $item['special_price_pickup_capital'] ?? null,
                        'special_price_delivery_capital' => $item['special_price_delivery_capital'] ?? null,
                        'special_price_pickup_interior' => $item['special_price_pickup_interior'] ?? null,
                        'special_price_delivery_interior' => $item['special_price_delivery_interior'] ?? null,
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
        $routeName = $this->getPromotionIndexRoute($promotion->type);

        return redirect()->route($routeName)
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
            'items.combo',
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
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'has_variants', 'is_active']),
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'is_combo_category']),
            'combos' => Combo::query()
                ->with(['items.product:id,name', 'category:id,name'])
                ->ordered()
                ->get(['id', 'name', 'category_id', 'precio_pickup_capital', 'precio_domicilio_capital', 'precio_pickup_interior', 'precio_domicilio_interior', 'is_active']),
            'badgeTypes' => BadgeType::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'color', 'text_color', 'is_active']),
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

        // Manejar la imagen si se subió
        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($promotion->image) {
                \Storage::disk('public')->delete($promotion->image);
            }
            $validated['image'] = $request->file('image')->store('menu/promotions', 'public');
        }

        DB::transaction(function () use ($validated, $items, $promotion) {
            $promotion->update($validated);

            // Preservar IDs existentes para no romper carritos/órdenes
            $existingItemIds = $promotion->items()->pluck('id')->toArray();
            $updatedItemIds = [];

            if (! empty($items)) {
                foreach ($items as $item) {
                    $itemData = [
                        'product_id' => $item['product_id'] ?? null,
                        'variant_id' => $item['variant_id'] ?? null,
                        'category_id' => $item['category_id'] ?? null,
                        // Campos para Sub del Día (4 precios independientes)
                        'special_price_pickup_capital' => $item['special_price_pickup_capital'] ?? null,
                        'special_price_delivery_capital' => $item['special_price_delivery_capital'] ?? null,
                        'special_price_pickup_interior' => $item['special_price_pickup_interior'] ?? null,
                        'special_price_delivery_interior' => $item['special_price_delivery_interior'] ?? null,
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
                    ];

                    if (! empty($item['id']) && in_array($item['id'], $existingItemIds)) {
                        // Actualizar item existente
                        $promotion->items()->where('id', $item['id'])->update($itemData);
                        $updatedItemIds[] = $item['id'];
                    } else {
                        // Crear nuevo item
                        $newItem = $promotion->items()->create($itemData);
                        $updatedItemIds[] = $newItem->id;
                    }
                }
            }

            // Eliminar solo items removidos
            $promotion->items()->whereNotIn('id', $updatedItemIds)->delete();
        });

        // Redirigir según el tipo de promoción
        $routeName = $this->getPromotionIndexRoute($promotion->type);

        return redirect()->route($routeName)
            ->with('success', 'Promoción actualizada exitosamente.');
    }

    /**
     * Archiva una promoción (soft delete)
     */
    public function destroy(Promotion $promotion): RedirectResponse
    {
        try {
            $routeName = $this->getPromotionIndexRoute($promotion->type);
            $promotion->delete();

            $entityName = $promotion->type === 'bundle_special' ? 'Combinado' : 'Promoción';

            return redirect()->route($routeName)
                ->with('success', "{$entityName} archivado exitosamente.");
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al archivar: '.$e->getMessage()]);
        }
    }

    /**
     * Obtiene la ruta del índice según el tipo de promoción
     */
    private function getPromotionIndexRoute(string $type): string
    {
        return match ($type) {
            'daily_special' => 'menu.promotions.daily-special.index',
            'percentage_discount' => 'menu.promotions.percentage.index',
            'two_for_one' => 'menu.promotions.two-for-one.index',
            'bundle_special' => 'menu.promotions.bundle-specials.index',
            default => 'menu.promotions.daily-special.index',
        };
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
                    $query->select(['id', 'product_id', 'name', 'size', 'precio_pickup_capital', 'precio_pickup_interior', 'is_active'])
                        ->orderBy('sort_order');
                }])
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'has_variants', 'is_active']),
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'is_combo_category']),
            'combos' => Combo::query()
                ->with(['items.product:id,name', 'category:id,name'])
                ->ordered()
                ->get(['id', 'name', 'category_id', 'precio_pickup_capital', 'precio_domicilio_capital', 'precio_pickup_interior', 'precio_domicilio_interior', 'is_active']),
            'badgeTypes' => BadgeType::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'color', 'text_color', 'is_active']),
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
            'products' => Product::query()
                ->with(['category:id,name', 'variants' => function ($query) {
                    $query->select(['id', 'product_id', 'name', 'size', 'precio_pickup_capital', 'precio_pickup_interior'])
                        ->orderBy('sort_order');
                }])
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'has_variants', 'is_active']),
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'is_combo_category']),
            'combos' => Combo::query()
                ->with(['items.product:id,name', 'category:id,name'])
                ->ordered()
                ->get(['id', 'name', 'category_id', 'precio_pickup_capital', 'precio_domicilio_capital', 'precio_pickup_interior', 'precio_domicilio_interior', 'is_active']),
            'badgeTypes' => BadgeType::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'color', 'text_color', 'is_active']),
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
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'has_variants', 'is_active']),
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'is_combo_category']),
            'combos' => Combo::query()
                ->with(['items.product:id,name', 'category:id,name'])
                ->ordered()
                ->get(['id', 'name', 'category_id', 'precio_pickup_capital', 'precio_domicilio_capital', 'precio_pickup_interior', 'precio_domicilio_interior', 'is_active']),
            'badgeTypes' => BadgeType::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'color', 'text_color', 'is_active']),
        ]);
    }

    /**
     * Muestra listado de combinados
     */
    public function bundleSpecialsIndex(Request $request): Response
    {
        $search = $request->input('search');

        $query = Promotion::query()
            ->combinados()
            ->with(['bundleItems.product:id,name'])
            ->withCount('bundleItems')
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $combinados = $query->get();

        // Calcular stats
        $allCombinados = Promotion::combinados();
        $activeCombinados = Promotion::combinados()->where('is_active', true);
        $availableCombinados = Promotion::available();
        $validNowCombinados = Promotion::validNowCombinados();

        return Inertia::render('menu/promotions/bundle-specials/index', [
            'combinados' => $combinados,
            'stats' => [
                'total_combinados' => $allCombinados->count(),
                'active_combinados' => $activeCombinados->count(),
                'available_combinados' => $availableCombinados->count(),
                'valid_now_combinados' => $validNowCombinados->count(),
            ],
            'filters' => [
                'search' => $search,
                'per_page' => 10,
            ],
        ]);
    }

    /**
     * Muestra formulario de creación de combinado
     */
    public function createBundleSpecial(): Response
    {
        return Inertia::render('menu/promotions/bundle-specials/create', [
            'products' => Product::query()
                ->with([
                    'category:id,name,uses_variants',
                    'variants' => function ($query) {
                        $query->orderBy('sort_order')
                            ->select('id', 'product_id', 'name', 'size', 'precio_pickup_capital', 'is_active');
                    },
                ])
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'has_variants', 'is_active']),
            'badgeTypes' => BadgeType::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'color', 'text_color', 'is_active']),
        ]);
    }

    /**
     * Muestra formulario de edición de combinado
     */
    public function editBundleSpecial(Promotion $promotion): Response
    {
        // Verificar que sea un combinado
        if ($promotion->type !== 'bundle_special') {
            abort(404);
        }

        $promotion->load([
            'bundleItems.product',
            'bundleItems.variant',
            'bundleItems.options.product',
            'bundleItems.options.variant',
        ]);

        return Inertia::render('menu/promotions/bundle-specials/edit', [
            'combinado' => $promotion,
            'products' => Product::query()
                ->with([
                    'category:id,name,uses_variants',
                    'variants' => function ($query) {
                        $query->orderBy('sort_order')
                            ->select('id', 'product_id', 'name', 'size', 'precio_pickup_capital', 'is_active');
                    },
                ])
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'has_variants', 'is_active']),
            'badgeTypes' => BadgeType::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'color', 'text_color', 'is_active']),
        ]);
    }

    /**
     * Almacena un nuevo combinado
     */
    public function storeBundleSpecial(StoreBundlePromotionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        // Manejar la imagen si se subió
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('menu/promotions', 'public');
        }

        $promotion = null;

        DB::transaction(function () use ($validated, $items, &$promotion) {
            $promotion = Promotion::create($validated);

            // Crear items (IDÉNTICO a ComboController)
            if (! empty($items)) {
                foreach ($items as $item) {
                    $isChoiceGroup = $item['is_choice_group'] ?? false;

                    $bundleItem = $promotion->bundleItems()->create([
                        'product_id' => $isChoiceGroup ? null : ($item['product_id'] ?? null),
                        'variant_id' => $isChoiceGroup ? null : ($item['variant_id'] ?? null),
                        'quantity' => $item['quantity'],
                        'sort_order' => $item['sort_order'] ?? 0,
                        'is_choice_group' => $isChoiceGroup,
                        'choice_label' => $isChoiceGroup ? ($item['choice_label'] ?? null) : null,
                    ]);

                    // Crear opciones si es grupo
                    if ($isChoiceGroup && ! empty($item['options'])) {
                        foreach ($item['options'] as $option) {
                            $bundleItem->options()->create([
                                'product_id' => $option['product_id'],
                                'variant_id' => $option['variant_id'] ?? null,
                                'sort_order' => $option['sort_order'] ?? 0,
                            ]);
                        }
                    }
                }
            }
        });

        return redirect()->route('menu.promotions.bundle-specials.index')
            ->with('success', 'Combinado creado exitosamente.');
    }

    /**
     * Actualiza un combinado existente
     */
    public function updateBundleSpecial(UpdateBundlePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        // Verificar que sea un combinado
        if ($promotion->type !== 'bundle_special') {
            abort(404);
        }

        $validated = $request->validated();

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        // Manejar la imagen si se subió una nueva
        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($promotion->image) {
                \Storage::disk('public')->delete($promotion->image);
            }
            $validated['image'] = $request->file('image')->store('menu/promotions', 'public');
        }

        DB::transaction(function () use ($validated, $items, $promotion) {
            $promotion->update($validated);

            // Preservar IDs existentes para no romper carritos/órdenes
            $existingItemIds = $promotion->bundleItems()->pluck('id')->toArray();
            $updatedItemIds = [];

            if (! empty($items)) {
                foreach ($items as $item) {
                    $isChoiceGroup = $item['is_choice_group'] ?? false;
                    $itemData = [
                        'product_id' => $isChoiceGroup ? null : ($item['product_id'] ?? null),
                        'variant_id' => $isChoiceGroup ? null : ($item['variant_id'] ?? null),
                        'quantity' => $item['quantity'],
                        'sort_order' => $item['sort_order'] ?? 0,
                        'is_choice_group' => $isChoiceGroup,
                        'choice_label' => $isChoiceGroup ? ($item['choice_label'] ?? null) : null,
                    ];

                    if (! empty($item['id']) && in_array($item['id'], $existingItemIds)) {
                        // Actualizar item existente
                        $promotion->bundleItems()->where('id', $item['id'])->update($itemData);
                        $bundleItem = $promotion->bundleItems()->find($item['id']);
                        $updatedItemIds[] = $item['id'];
                    } else {
                        // Crear nuevo item
                        $bundleItem = $promotion->bundleItems()->create($itemData);
                        $updatedItemIds[] = $bundleItem->id;
                    }

                    // Manejar opciones del grupo de elección
                    if ($isChoiceGroup && $bundleItem) {
                        $existingOptionIds = $bundleItem->options()->pluck('id')->toArray();
                        $updatedOptionIds = [];

                        if (! empty($item['options'])) {
                            foreach ($item['options'] as $option) {
                                $optionData = [
                                    'product_id' => $option['product_id'],
                                    'variant_id' => $option['variant_id'] ?? null,
                                    'sort_order' => $option['sort_order'] ?? 0,
                                ];

                                if (! empty($option['id']) && in_array($option['id'], $existingOptionIds)) {
                                    // Actualizar opción existente
                                    $bundleItem->options()->where('id', $option['id'])->update($optionData);
                                    $updatedOptionIds[] = $option['id'];
                                } else {
                                    // Crear nueva opción
                                    $newOption = $bundleItem->options()->create($optionData);
                                    $updatedOptionIds[] = $newOption->id;
                                }
                            }
                        }

                        // Eliminar solo opciones removidas
                        $bundleItem->options()->whereNotIn('id', $updatedOptionIds)->delete();
                    }
                }
            }

            // Eliminar solo items removidos
            $promotion->bundleItems()->whereNotIn('id', $updatedItemIds)->delete();
        });

        return redirect()->route('menu.promotions.bundle-specials.index')
            ->with('success', 'Combinado actualizado exitosamente.');
    }

    /**
     * Activa o desactiva un combinado
     */
    public function toggleBundleSpecial(Promotion $promotion): RedirectResponse
    {
        if ($promotion->type !== 'bundle_special') {
            abort(404);
        }

        $promotion->update([
            'is_active' => ! $promotion->is_active,
        ]);

        $status = $promotion->is_active ? 'activado' : 'desactivado';

        return redirect()->back()
            ->with('success', "Combinado {$status} exitosamente.");
    }

    /**
     * Reordena los combinados
     */
    public function reorderBundleSpecials(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'combinados' => ['required', 'array'],
            'combinados.*.id' => ['required', 'integer', 'exists:promotions,id'],
            'combinados.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($validated['combinados'] as $combinadoData) {
            Promotion::where('id', $combinadoData['id'])
                ->where('type', 'bundle_special')
                ->update([
                    'sort_order' => $combinadoData['sort_order'],
                ]);
        }

        $count = count($validated['combinados']);

        // Log de reordenamiento manual
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'event_type' => 'reordered',
            'target_model' => Promotion::class,
            'target_id' => null,
            'description' => "Promociones bundle reordenadas ({$count} elementos)",
            'old_values' => null,
            'new_values' => ['items_count' => $count],
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->back()
            ->with('success', 'Orden actualizado exitosamente.');
    }
}
