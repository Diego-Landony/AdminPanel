<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreProductRequest;
use App\Http\Requests\Menu\UpdateProductRequest;
use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Listar productos
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $query = Product::query()
            ->with('category')
            ->withCount(['sections', 'variants']);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Ordenamiento
        if ($sortField === 'product') {
            $query->orderBy('name', $sortDirection);
        } elseif (in_array($sortField, ['is_active', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection);
        } elseif ($sortField === 'sections_count') {
            $query->orderBy('sections_count', $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate($perPage);

        // Stats
        $allProducts = Product::all();

        return Inertia::render('menu/products/index', [
            'products' => $products,
            'stats' => [
                'total_products' => $allProducts->count(),
                'active_products' => $allProducts->where('is_active', true)->count(),
                'with_customization' => $allProducts->where('is_customizable', true)->count(),
                'with_variants' => $allProducts->where('has_variants', true)->count(),
            ],
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(): Response
    {
        $categories = Category::active()->ordered()->get(['id', 'name']);
        $sections = Section::with('options')->orderBy('title')->get();

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
                        'sku' => $product->slug.'-'.($index + 1),
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
        $categories = Category::active()->ordered()->get(['id', 'name']);
        $sections = Section::with('options')->orderBy('title')->get();

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
        unset($validated['sections'], $validated['variants']);

        DB::transaction(function () use ($validated, $sectionIds, $variants, $product) {
            // Si no usa variantes, limpiar el campo variants y actualizar precios del producto
            if (! ($validated['has_variants'] ?? false)) {
                $validated['precio_pickup_capital'] = $validated['precio_pickup_capital'] ?? null;
                $validated['precio_domicilio_capital'] = $validated['precio_domicilio_capital'] ?? null;
                $validated['precio_pickup_interior'] = $validated['precio_pickup_interior'] ?? null;
                $validated['precio_domicilio_interior'] = $validated['precio_domicilio_interior'] ?? null;

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
                                'sort_order' => $index + 1,
                            ]);
                            $existingVariantIds[] = $variant->id;
                        }
                    } else {
                        // Crear nueva variante
                        $variant = ProductVariant::create([
                            'product_id' => $product->id,
                            'sku' => $product->slug.'-'.uniqid(),
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

                // Eliminar variantes que ya no están en la lista
                $product->variants()->whereNotIn('id', $existingVariantIds)->delete();
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
    public function destroy(Product $product): RedirectResponse
    {
        try {
            $product->delete();

            return redirect()
                ->route('menu.products.index')
                ->with('success', 'Producto eliminado exitosamente.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al eliminar el producto: '.$e->getMessage()]);
        }
    }
}
