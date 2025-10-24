<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreComboRequest;
use App\Http\Requests\Menu\UpdateComboRequest;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ComboController extends Controller
{
    /**
     * Muestra el listado de combos con filtros y stats
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $query = Combo::query()
            ->with(['items.product:id,name', 'category:id,name'])
            ->withCount('items')
            ->orderBy('sort_order', 'asc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $combos = $query->get();

        $allCombos = Combo::query();
        $activeCombos = Combo::query()->where('is_active', true);
        $availableCombos = Combo::query()->available();

        return Inertia::render('menu/combos/index', [
            'combos' => $combos,
            'stats' => [
                'total_combos' => $allCombos->count(),
                'active_combos' => $activeCombos->count(),
                'available_combos' => $availableCombos->count(),
            ],
            'filters' => [
                'search' => $request->input('search'),
            ],
        ]);
    }

    /**
     * Muestra formulario de creación
     */
    public function create(): Response
    {
        return Inertia::render('menu/combos/create', [
            'products' => Product::query()
                ->with([
                    'category:id,name,uses_variants',
                    'variants' => function ($query) {
                        $query->where('is_active', true)
                            ->orderBy('sort_order')
                            ->select('id', 'product_id', 'name', 'size', 'precio_pickup_capital');
                    },
                ])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'has_variants']),
            'categories' => Category::query()
                ->where('is_active', true)
                ->where('is_combo_category', true)
                ->ordered()
                ->get(['id', 'name', 'sort_order']),
        ]);
    }

    /**
     * Almacena un nuevo combo con sus items
     */
    public function store(StoreComboRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $combo = null;

        DB::transaction(function () use ($validated, $items, &$combo) {
            $combo = Combo::create($validated);

            if (! empty($items)) {
                foreach ($items as $item) {
                    $combo->items()->create([
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'sort_order' => $item['sort_order'] ?? 0,
                    ]);
                }
            }
        });

        return redirect()->route('menu.combos.index')
            ->with('success', 'Combo creado exitosamente.');
    }

    /**
     * Muestra un combo específico
     */
    public function show(Combo $combo): Response
    {
        $combo->load([
            'items.product',
            'category:id,name',
        ]);

        return Inertia::render('menu/combos/show', [
            'combo' => $combo,
        ]);
    }

    /**
     * Muestra formulario de edición
     */
    public function edit(Combo $combo): Response
    {
        $combo->load([
            'items.product',
            'items.variant',
            'category:id,name',
        ]);

        return Inertia::render('menu/combos/edit', [
            'combo' => $combo,
            'products' => Product::query()
                ->with([
                    'category:id,name,uses_variants',
                    'variants' => function ($query) {
                        $query->where('is_active', true)
                            ->orderBy('sort_order')
                            ->select('id', 'product_id', 'name', 'size', 'precio_pickup_capital');
                    },
                ])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'category_id', 'has_variants']),
            'categories' => Category::query()
                ->where('is_active', true)
                ->where('is_combo_category', true)
                ->ordered()
                ->get(['id', 'name', 'sort_order']),
        ]);
    }

    /**
     * Actualiza un combo existente
     */
    public function update(UpdateComboRequest $request, Combo $combo): RedirectResponse
    {
        $validated = $request->validated();

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        DB::transaction(function () use ($validated, $items, $combo) {
            $combo->update($validated);

            $combo->items()->delete();

            if (! empty($items)) {
                foreach ($items as $item) {
                    $combo->items()->create([
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'sort_order' => $item['sort_order'] ?? 0,
                    ]);
                }
            }
        });

        return redirect()->route('menu.combos.index')
            ->with('success', 'Combo actualizado exitosamente.');
    }

    /**
     * Elimina un combo
     */
    public function destroy(Combo $combo): RedirectResponse
    {
        try {
            $combo->delete();

            return redirect()->route('menu.combos.index')
                ->with('success', 'Combo eliminado exitosamente.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al eliminar el combo: '.$e->getMessage()]);
        }
    }

    /**
     * Activa o desactiva un combo
     */
    public function toggle(Combo $combo): RedirectResponse
    {
        $combo->update([
            'is_active' => ! $combo->is_active,
        ]);

        $status = $combo->is_active ? 'activado' : 'desactivado';

        return redirect()->back()
            ->with('success', "Combo {$status} exitosamente.");
    }

    /**
     * Reordena los combos
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'combos' => ['required', 'array'],
            'combos.*.id' => ['required', 'integer', 'exists:combos,id'],
            'combos.*.sort_order' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($validated['combos'] as $comboData) {
            Combo::where('id', $comboData['id'])->update([
                'sort_order' => $comboData['sort_order'],
            ]);
        }

        return redirect()->back()
            ->with('success', 'Orden actualizado exitosamente.');
    }
}
