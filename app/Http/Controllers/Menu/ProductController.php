<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreProductRequest;
use App\Http\Requests\Menu\UpdateProductRequest;
use App\Models\ActivityLog;
use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Section;
use App\Services\MenuVersionService;
use App\Support\ActivityLogging;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de Productos
 *
 * Maneja la gestión completa de productos con:
 * - Categoría asociada directamente al producto
 * - Precios directos en el producto (si has_variants = false)
 * - Variantes con precios (si has_variants = true)
 * - Secciones de personalización
 */
class ProductController extends Controller
{
    /**
     * Listar productos agrupados por categoría
     */
    public function index(Request $request): Response
    {
        // Obtener categorías ordenadas
        $categories = Category::query()
            ->orderBy('sort_order')
            ->get();

        // Transformar a estructura agrupada, cargando productos por category_id
        $groupedProducts = $categories->map(function ($category) {
            $products = Product::where('category_id', $category->id)
                ->with(['variants' => function ($query) {
                    $query->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get();

            return [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
                'products' => $products,
            ];
        })->filter(fn ($group) => $group['products']->isNotEmpty())->values();

        // Agregar productos sin categoría al final
        $productsWithoutCategory = Product::whereNull('category_id')
            ->with(['variants' => function ($query) {
                $query->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        if ($productsWithoutCategory->isNotEmpty()) {
            $groupedProducts->push([
                'category' => [
                    'id' => null,
                    'name' => 'Sin categoría',
                ],
                'products' => $productsWithoutCategory,
            ]);
        }

        // Stats
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();

        return Inertia::render('menu/products/index', [
            'groupedProducts' => $groupedProducts,
            'stats' => [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
            ],
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(): Response
    {
        $categories = Category::active()
            ->where('is_combo_category', false)
            ->ordered()
            ->get(['id', 'name', 'uses_variants', 'variant_definitions']);
        $sections = Section::with('options')->orderBy('sort_order')->get();

        return Inertia::render('menu/products/create', [
            'categories' => $categories,
            'sections' => $sections,
        ]);
    }

    /**
     * Guardar nuevo producto
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $sectionIds = $validated['sections'] ?? [];
        $variants = $validated['variants'] ?? [];
        unset($validated['sections'], $validated['variants']);

        // Handle image upload
        \Log::info('=== DEBUG IMAGE UPLOAD (store) ===');
        \Log::info('hasFile image: '.($request->hasFile('image') ? 'true' : 'false'));
        \Log::info('All files: '.json_encode($request->allFiles()));

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            \Log::info('Image is valid');
            \Log::info('Original name: '.$image->getClientOriginalName());
            \Log::info('Extension: '.$image->getClientOriginalExtension());
            \Log::info('Size: '.$image->getSize());

            $extension = $image->getClientOriginalExtension() ?: $image->guessExtension() ?: 'jpg';
            $filename = Str::uuid().'.'.$extension;
            $path = $image->storeAs('menu/products', $filename, 'public');

            \Log::info('Stored path: '.($path ?: 'FAILED'));

            if ($path) {
                $validated['image'] = '/storage/'.$path;
                \Log::info('Final image path: '.$validated['image']);
            } else {
                \Log::error('Failed to store image');
                unset($validated['image']);
            }
        } else {
            \Log::info('No valid image file received');
            if ($request->hasFile('image')) {
                \Log::info('File exists but is not valid: '.$request->file('image')->getError());
            }
            unset($validated['image']);
        }

        DB::transaction(function () use ($validated, $sectionIds, $variants, &$product) {
            // Si no usa variantes, limpiar el campo variants
            if (! ($validated['has_variants'] ?? false)) {
                $validated['precio_pickup_capital'] = $validated['precio_pickup_capital'] ?? null;
                $validated['precio_domicilio_capital'] = $validated['precio_domicilio_capital'] ?? null;
                $validated['precio_pickup_interior'] = $validated['precio_pickup_interior'] ?? null;
                $validated['precio_domicilio_interior'] = $validated['precio_domicilio_interior'] ?? null;
            } else {
                // Si usa variantes, limpiar los precios del producto
                $validated['precio_pickup_capital'] = null;
                $validated['precio_domicilio_capital'] = null;
                $validated['precio_pickup_interior'] = null;
                $validated['precio_domicilio_interior'] = null;
            }

            $product = Product::create($validated);

            // Crear variantes si has_variants = true
            if (($validated['has_variants'] ?? false) && ! empty($variants)) {
                foreach ($variants as $index => $variantData) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => 'PROD-'.$product->id.'-'.($index + 1),
                        'name' => $variantData['name'],
                        'size' => $variantData['name'],
                        'precio_pickup_capital' => $variantData['precio_pickup_capital'],
                        'precio_domicilio_capital' => $variantData['precio_domicilio_capital'],
                        'precio_pickup_interior' => $variantData['precio_pickup_interior'],
                        'precio_domicilio_interior' => $variantData['precio_domicilio_interior'],
                        'is_redeemable' => $variantData['is_redeemable'] ?? false,
                        'points_cost' => ! empty($variantData['points_cost']) ? $variantData['points_cost'] : null,
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ]);
                }

                // Si tiene variantes, limpiar redención a nivel producto
                $product->update([
                    'is_redeemable' => false,
                    'points_cost' => null,
                ]);
            }

            // Attach sections
            if (! empty($sectionIds)) {
                $syncData = [];
                foreach ($sectionIds as $index => $sectionId) {
                    $syncData[$sectionId] = ['sort_order' => $index];
                }
                $product->sections()->sync($syncData);
            }
        });

        return redirect()
            ->route('menu.products.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Product $product): Response
    {
        $product->load(['category', 'sections', 'variants']);
        $categories = Category::active()
            ->where('is_combo_category', false)
            ->ordered()
            ->get(['id', 'name', 'uses_variants', 'variant_definitions']);
        $sections = Section::with('options')->orderBy('sort_order')->get();

        return Inertia::render('menu/products/edit', [
            'product' => $product,
            'categories' => $categories,
            'sections' => $sections,
        ]);
    }

    /**
     * Actualizar producto
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();
        $sectionIds = $validated['sections'] ?? [];
        $variants = $validated['variants'] ?? [];
        $removeImage = $validated['remove_image'] ?? false;
        unset($validated['sections'], $validated['variants'], $validated['remove_image']);

        // Capturar valores originales para log consolidado
        $originalProduct = $product->getAttributes();
        $originalVariants = $product->variants->keyBy('id')->map(fn ($v) => $v->getAttributes())->toArray();

        // Handle image upload
        \Log::info('=== DEBUG IMAGE UPLOAD (update) ===');
        \Log::info('hasFile image: '.($request->hasFile('image') ? 'true' : 'false'));
        \Log::info('All files: '.json_encode($request->allFiles()));
        \Log::info('removeImage flag: '.($removeImage ? 'true' : 'false'));

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            // Delete old image if exists
            if ($product->image && $product->image !== '/storage/') {
                $oldPath = str_replace('/storage/', '', $product->image);
                Storage::disk('public')->delete($oldPath);
            }

            $image = $request->file('image');
            \Log::info('Image is valid');
            \Log::info('Original name: '.$image->getClientOriginalName());
            \Log::info('Extension: '.$image->getClientOriginalExtension());
            \Log::info('Size: '.$image->getSize());

            $extension = $image->getClientOriginalExtension() ?: $image->guessExtension() ?: 'jpg';
            $filename = Str::uuid().'.'.$extension;
            $path = $image->storeAs('menu/products', $filename, 'public');

            \Log::info('Stored path: '.($path ?: 'FAILED'));

            if ($path) {
                $validated['image'] = '/storage/'.$path;
                \Log::info('Final image path: '.$validated['image']);
            } else {
                \Log::error('Failed to store image');
                unset($validated['image']);
            }
        } elseif ($removeImage) {
            // Remove image if requested
            if ($product->image && $product->image !== '/storage/') {
                $oldPath = str_replace('/storage/', '', $product->image);
                Storage::disk('public')->delete($oldPath);
            }
            $validated['image'] = null;
            \Log::info('Image removed');
        } else {
            \Log::info('No valid image file received');
            if ($request->hasFile('image')) {
                \Log::info('File exists but is not valid: '.$request->file('image')->getError());
            }
            unset($validated['image']);
        }

        // Variables para rastrear cambios
        $variantChanges = [];
        $newVariants = [];

        // Deshabilitar logging automático para crear un log consolidado
        ActivityLogging::withoutLogging(function () use ($validated, $sectionIds, $variants, $product, &$variantChanges, &$newVariants, $originalVariants) {
            DB::transaction(function () use ($validated, $sectionIds, $variants, $product, &$variantChanges, &$newVariants, $originalVariants) {
                // Si no usa variantes, limpiar el campo variants y actualizar precios del producto
                if (! ($validated['has_variants'] ?? false)) {
                    $validated['precio_pickup_capital'] = $validated['precio_pickup_capital'] ?? null;
                    $validated['precio_domicilio_capital'] = $validated['precio_domicilio_capital'] ?? null;
                    $validated['precio_pickup_interior'] = $validated['precio_pickup_interior'] ?? null;
                    $validated['precio_domicilio_interior'] = $validated['precio_domicilio_interior'] ?? null;

                    // Eliminar referencias en combo_item_options antes de eliminar variantes
                    $variantIds = $product->variants()->pluck('id');
                    if ($variantIds->isNotEmpty()) {
                        \App\Models\Menu\ComboItemOption::whereIn('variant_id', $variantIds)->delete();
                    }

                    // Eliminar variantes existentes
                    $product->variants()->delete();
                } else {
                    // Si usa variantes, limpiar los precios del producto
                    $validated['precio_pickup_capital'] = null;
                    $validated['precio_domicilio_capital'] = null;
                    $validated['precio_pickup_interior'] = null;
                    $validated['precio_domicilio_interior'] = null;

                    // Manejar variantes: actualizar existentes y crear nuevas
                    $existingVariantIds = [];

                    foreach ($variants as $index => $variantData) {
                        if (isset($variantData['id']) && $variantData['id']) {
                            // Actualizar variante existente
                            $variant = ProductVariant::find($variantData['id']);
                            if ($variant && $variant->product_id === $product->id) {
                                $oldVariant = $originalVariants[$variant->id] ?? [];
                                $variant->update([
                                    'name' => $variantData['name'],
                                    'size' => $variantData['name'],
                                    'precio_pickup_capital' => $variantData['precio_pickup_capital'],
                                    'precio_domicilio_capital' => $variantData['precio_domicilio_capital'],
                                    'precio_pickup_interior' => $variantData['precio_pickup_interior'],
                                    'precio_domicilio_interior' => $variantData['precio_domicilio_interior'],
                                    'is_redeemable' => $variantData['is_redeemable'] ?? false,
                                    'points_cost' => ! empty($variantData['points_cost']) ? $variantData['points_cost'] : null,
                                    'is_active' => true,
                                    'sort_order' => $index + 1,
                                ]);

                                // Rastrear cambios de variante
                                $changes = $variant->getChanges();
                                unset($changes['updated_at']);
                                if (! empty($changes)) {
                                    $variantChanges[$variant->name] = [
                                        'old' => array_intersect_key($oldVariant, $changes),
                                        'new' => $changes,
                                    ];
                                }

                                $existingVariantIds[] = $variant->id;
                            }
                        } else {
                            // Crear nueva variante
                            $variant = ProductVariant::create([
                                'product_id' => $product->id,
                                'sku' => 'PROD-'.$product->id.'-'.uniqid(),
                                'name' => $variantData['name'],
                                'size' => $variantData['name'],
                                'precio_pickup_capital' => $variantData['precio_pickup_capital'],
                                'precio_domicilio_capital' => $variantData['precio_domicilio_capital'],
                                'precio_pickup_interior' => $variantData['precio_pickup_interior'],
                                'precio_domicilio_interior' => $variantData['precio_domicilio_interior'],
                                'is_redeemable' => $variantData['is_redeemable'] ?? false,
                                'points_cost' => ! empty($variantData['points_cost']) ? $variantData['points_cost'] : null,
                                'is_active' => true,
                                'sort_order' => $index + 1,
                            ]);
                            $newVariants[] = $variant->name;
                            $existingVariantIds[] = $variant->id;
                        }
                    }

                    // Desactivar variantes que ya no están en la lista (no eliminar para conservar historial)
                    $product->variants()->whereNotIn('id', $existingVariantIds)->update(['is_active' => false]);

                    // Si tiene variantes, limpiar redención a nivel producto
                    $validated['is_redeemable'] = false;
                    $validated['points_cost'] = null;
                }

                $product->update($validated);

                // Sync sections
                if (isset($sectionIds)) {
                    $syncData = [];
                    foreach ($sectionIds as $index => $sectionId) {
                        $syncData[$sectionId] = ['sort_order' => $index];
                    }
                    $product->sections()->sync($syncData);
                }
            });
        });

        // Crear log consolidado
        $this->createConsolidatedLog($product, $originalProduct, $variantChanges, $newVariants);

        return redirect()
            ->route('menu.products.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Crear log consolidado de cambios de producto y variantes
     */
    private function createConsolidatedLog(
        Product $product,
        array $originalProduct,
        array $variantChanges,
        array $newVariants
    ): void {
        $product->refresh();
        $productChanges = $product->getChanges();
        unset($productChanges['updated_at']);

        // Si no hay cambios, no crear log
        if (empty($productChanges) && empty($variantChanges) && empty($newVariants)) {
            return;
        }

        $translations = config('activity.field_translations', []);
        $changes = [];

        // Cambios del producto
        foreach ($productChanges as $field => $newValue) {
            $oldValue = $originalProduct[$field] ?? null;
            $fieldName = $translations[$field] ?? str_replace('_', ' ', $field);
            $changes[] = "{$fieldName}: '{$this->formatLogValue($oldValue)}' -> '{$this->formatLogValue($newValue)}'";
        }

        // Cambios de variantes existentes
        foreach ($variantChanges as $variantName => $data) {
            foreach ($data['new'] as $field => $newValue) {
                $oldValue = $data['old'][$field] ?? null;
                $fieldName = $translations[$field] ?? str_replace('_', ' ', $field);
                $changes[] = "Variante '{$variantName}' - {$fieldName}: '{$this->formatLogValue($oldValue)}' -> '{$this->formatLogValue($newValue)}'";
            }
        }

        // Variantes nuevas
        foreach ($newVariants as $variantName) {
            $changes[] = "Nueva variante '{$variantName}' creada";
        }

        // Limitar a 5 cambios para legibilidad
        $displayChanges = array_slice($changes, 0, 5);
        if (count($changes) > 5) {
            $displayChanges[] = '... y '.(count($changes) - 5).' cambio(s) más';
        }

        $description = "Producto '{$product->name}' actualizado";
        if (! empty($displayChanges)) {
            $description .= ' - '.implode(', ', $displayChanges);
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'event_type' => 'updated',
            'target_model' => Product::class,
            'target_id' => $product->id,
            'description' => $description,
            'old_values' => [
                'product' => array_intersect_key($originalProduct, $productChanges),
                'variants' => collect($variantChanges)->map(fn ($c) => $c['old'])->toArray(),
            ],
            'new_values' => [
                'product' => $productChanges,
                'variants' => collect($variantChanges)->map(fn ($c) => $c['new'])->toArray(),
                'new_variants' => $newVariants,
            ],
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Formatear valor para el log
     */
    private function formatLogValue(mixed $value): string
    {
        if (is_null($value)) {
            return '(vacío)';
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        $str = (string) $value;

        return strlen($str) > 50 ? substr($str, 0, 47).'...' : $str;
    }

    /**
     * Eliminar producto
     */
    public function destroy(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        // Obtener combos donde se usa el producto en items fijos
        $combosFromItems = \App\Models\Menu\ComboItem::where('product_id', $product->id)
            ->with('combo:id,name')
            ->get()
            ->pluck('combo.name')
            ->unique()
            ->filter();

        // Obtener combos donde se usa el producto en grupos de elección
        $combosFromChoiceGroups = \App\Models\Menu\ComboItemOption::where('product_id', $product->id)
            ->with('comboItem.combo:id,name')
            ->get()
            ->pluck('comboItem.combo.name')
            ->unique()
            ->filter();

        // Combinar ambos conjuntos de combos
        $allCombos = $combosFromItems->merge($combosFromChoiceGroups)->unique()->sort()->values();

        if ($allCombos->isNotEmpty()) {
            $comboCount = $allCombos->count();
            $comboList = $allCombos->join(', ');

            $message = "No se puede eliminar el producto porque está siendo usado en {$comboCount} combo(s): {$comboList}. Está usado en "
                .($combosFromItems->isNotEmpty() ? 'items de combo' : '')
                .($combosFromItems->isNotEmpty() && $combosFromChoiceGroups->isNotEmpty() ? ' y ' : '')
                .($combosFromChoiceGroups->isNotEmpty() ? 'grupos de elección' : '').'.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        try {
            // Soft delete variants first, then the product
            $product->variants()->delete();
            $product->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Producto eliminado exitosamente.',
                ], 200);
            }

            return redirect()
                ->route('menu.products.index')
                ->with('success', 'Producto eliminado exitosamente.');
        } catch (\Exception $e) {
            $errorMessage = 'Error al eliminar el producto: '.$e->getMessage();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $errorMessage,
                ], 500);
            }

            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Obtener información de uso del producto en combos
     */
    public function usageInfo(Product $product): JsonResponse
    {
        $usedInChoiceGroups = \App\Models\Menu\ComboItemOption::where('product_id', $product->id)
            ->with('comboItem.combo')
            ->get();

        if ($usedInChoiceGroups->isEmpty()) {
            return response()->json([
                'used_in_combos' => false,
                'combos' => [],
            ]);
        }

        $comboNames = $usedInChoiceGroups
            ->pluck('comboItem.combo.name')
            ->unique()
            ->sort()
            ->values();

        return response()->json([
            'used_in_combos' => true,
            'combos' => $comboNames,
            'count' => $comboNames->count(),
        ]);
    }

    /**
     * Reordenar productos dentro de su categoría
     */
    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->products as $product) {
            Product::where('id', $product['id'])
                ->update(['sort_order' => $product['sort_order']]);
        }

        $count = count($request->products);

        // Log de reordenamiento manual
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'event_type' => 'reordered',
            'target_model' => Product::class,
            'target_id' => null,
            'description' => "Productos reordenados ({$count} elementos)",
            'old_values' => null,
            'new_values' => ['items_count' => $count],
            'user_agent' => request()->userAgent(),
        ]);

        // Invalidar versión del menú para que Flutter actualice su caché
        app(MenuVersionService::class)->invalidate('products_reordered');

        return redirect()->back()
            ->with('success', 'Orden actualizado exitosamente.');
    }
}
