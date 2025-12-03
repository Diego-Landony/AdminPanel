<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreProductRequest;
use App\Http\Requests\Menu\UpdateProductRequest;
use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Section;
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
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = Str::uuid().'.'.$image->getClientOriginalExtension();
            $path = $image->storeAs('images', $filename, 'public');
            $validated['image'] = '/storage/'.$path;
        } else {
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
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ]);
                }
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

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                $oldPath = str_replace('/storage/', '', $product->image);
                Storage::disk('public')->delete($oldPath);
            }

            $image = $request->file('image');
            $filename = Str::uuid().'.'.$image->getClientOriginalExtension();
            $path = $image->storeAs('images', $filename, 'public');
            $validated['image'] = '/storage/'.$path;
        } elseif ($removeImage) {
            // Remove image if requested
            if ($product->image) {
                $oldPath = str_replace('/storage/', '', $product->image);
                Storage::disk('public')->delete($oldPath);
            }
            $validated['image'] = null;
        } else {
            unset($validated['image']);
        }

        DB::transaction(function () use ($validated, $sectionIds, $variants, $product) {
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
                            $variant->update([
                                'name' => $variantData['name'],
                                'size' => $variantData['name'],
                                'precio_pickup_capital' => $variantData['precio_pickup_capital'],
                                'precio_domicilio_capital' => $variantData['precio_domicilio_capital'],
                                'precio_pickup_interior' => $variantData['precio_pickup_interior'],
                                'precio_domicilio_interior' => $variantData['precio_domicilio_interior'],
                                'is_active' => true,
                                'sort_order' => $index + 1,
                            ]);
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
                            'is_active' => true,
                            'sort_order' => $index + 1,
                        ]);
                        $existingVariantIds[] = $variant->id;
                    }
                }

                // Desactivar variantes que ya no están en la lista (no eliminar para conservar historial)
                $product->variants()->whereNotIn('id', $existingVariantIds)->update(['is_active' => false]);
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

        return redirect()
            ->route('menu.products.index')
            ->with('success', 'Producto actualizado exitosamente.');
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
            // Eliminar el producto
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

        return redirect()->back()
            ->with('success', 'Orden actualizado exitosamente.');
    }
}
