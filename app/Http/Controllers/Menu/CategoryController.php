<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\AttachProductToCategoryRequest;
use App\Http\Requests\Menu\StoreCategoryRequest;
use App\Http\Requests\Menu\UpdateCategoryProductPricesRequest;
use App\Http\Requests\Menu\UpdateCategoryRequest;
use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Services\Menu\VariantGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Category::query();

        // Ordenar siempre por sort_order para el drag & drop
        $query->orderBy('sort_order', 'asc');

        $categories = $query->get();

        return Inertia::render('menu/categories/index', [
            'categories' => $categories,
            'stats' => [
                'total_categories' => $categories->count(),
                'active_categories' => $categories->where('is_active', true)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('menu/categories/create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Generar sort_order automáticamente
        $maxOrder = Category::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 1;

        Category::create($validated);

        return redirect()->route('menu.categories.index')
            ->with('success', 'Categoría creada exitosamente.');
    }

    public function show(Category $category): Response
    {
        $category->load(['products' => function ($query) {
            $query->active()->ordered()->take(10);
        }]);

        return Inertia::render('menu/categories/show', [
            'category' => $category,
        ]);
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('menu/categories/edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();

        $category->update($validated);

        return redirect()->route('menu.categories.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('menu.categories.index')
            ->with('success', 'Categoría eliminada exitosamente.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->categories as $category) {
            Category::where('id', $category['id'])->update(['sort_order' => $category['sort_order']]);
        }

        return redirect()->back()
            ->with('success', 'Orden actualizado exitosamente.');
    }

    /**
     * Asociar un producto a la categoría
     * - Si la categoría NO usa variantes: se requieren los 4 precios en el request
     * - Si la categoría SÍ usa variantes: se generan automáticamente las variantes
     */
    public function attachProduct(AttachProductToCategoryRequest $request, Category $category, VariantGeneratorService $variantGenerator): RedirectResponse
    {
        $validated = $request->validated();

        $product = Product::findOrFail($validated['product_id']);

        // Verificar si ya está asociado
        if ($category->products()->where('product_id', $product->id)->exists()) {
            return redirect()->back()
                ->with('error', 'El producto ya está asociado a esta categoría.');
        }

        // Calcular sort_order si no se proporciona
        if (! isset($validated['sort_order'])) {
            $maxOrder = $category->products()->max('category_product.sort_order') ?? 0;
            $validated['sort_order'] = $maxOrder + 1;
        }

        // Asociar producto con precios en pivot (si no usa variantes)
        $pivotData = ['sort_order' => $validated['sort_order']];

        if (! $category->uses_variants) {
            $pivotData['precio_pickup_capital'] = $validated['precio_pickup_capital'];
            $pivotData['precio_domicilio_capital'] = $validated['precio_domicilio_capital'];
            $pivotData['precio_pickup_interior'] = $validated['precio_pickup_interior'];
            $pivotData['precio_domicilio_interior'] = $validated['precio_domicilio_interior'];
        }

        $category->products()->attach($product->id, $pivotData);

        // Si la categoría usa variantes, generar variantes automáticamente
        if ($category->uses_variants) {
            $variantsCreated = $variantGenerator->generateVariantsForProduct($product);

            return redirect()->back()
                ->with('success', "Producto agregado. Se crearon {$variantsCreated} variantes. Ahora asigna precios a cada variante.");
        }

        return redirect()->back()
            ->with('success', 'Producto agregado exitosamente con sus precios.');
    }

    /**
     * Desasociar un producto de la categoría
     */
    public function detachProduct(Category $category, Product $product): RedirectResponse
    {
        if (! $category->products()->where('product_id', $product->id)->exists()) {
            return redirect()->back()
                ->with('error', 'El producto no está asociado a esta categoría.');
        }

        $category->products()->detach($product->id);

        return redirect()->back()
            ->with('success', 'Producto removido de la categoría.');
    }

    /**
     * Actualizar precios de un producto en la categoría (solo para categorías sin variantes)
     */
    public function updateProductPrices(UpdateCategoryProductPricesRequest $request, Category $category, Product $product): RedirectResponse
    {
        if ($category->uses_variants) {
            return redirect()->back()
                ->with('error', 'No se pueden actualizar precios aquí. Esta categoría usa variantes. Actualiza los precios en cada variante.');
        }

        if (! $category->products()->where('product_id', $product->id)->exists()) {
            return redirect()->back()
                ->with('error', 'El producto no está asociado a esta categoría.');
        }

        $validated = $request->validated();

        $category->products()->updateExistingPivot($product->id, $validated);

        return redirect()->back()
            ->with('success', 'Precios actualizados exitosamente.');
    }
}
