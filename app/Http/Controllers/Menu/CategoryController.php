<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreCategoryRequest;
use App\Http\Requests\Menu\UpdateCategoryRequest;
use App\Models\Menu\Category;
use App\Services\Menu\VariantSyncService;
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

    public function update(UpdateCategoryRequest $request, Category $category, VariantSyncService $variantSync): RedirectResponse
    {
        $oldDefinitions = $category->variant_definitions ?? [];
        $validated = $request->validated();

        try {
            \DB::transaction(function () use ($category, $validated, $oldDefinitions, $variantSync) {
                $category->update($validated);

                // Si variant_definitions cambió, sincronizar con productos
                if (isset($validated['variant_definitions']) && $validated['uses_variants']) {
                    $newDefinitions = $validated['variant_definitions'];

                    if ($oldDefinitions !== $newDefinitions) {
                        $changes = $variantSync->syncCategoryVariants($category, $oldDefinitions, $newDefinitions);

                        // Log de cambios para debugging
                        if (! empty($changes['added']) || ! empty($changes['renamed']) || ! empty($changes['removed'])) {
                            \Log::info('Variantes sincronizadas', [
                                'category_id' => $category->id,
                                'changes' => $changes,
                            ]);
                        }
                    }
                }
            });

            return redirect()->route('menu.categories.index')
                ->with('success', 'Categoría actualizada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['variant_definitions' => $e->getMessage()]);
        }
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
}
