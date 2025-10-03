<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\Promotion;
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
    public function index(Request $request): Response
    {
        $query = Promotion::query()
            ->withCount('items');

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

        // Filtro por aplicabilidad
        if ($request->filled('applies_to')) {
            $query->where('applies_to', $request->input('applies_to'));
        }

        // Filtro por vigencia actual
        if ($request->boolean('only_valid')) {
            $query->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('valid_from')
                        ->orWhere('valid_from', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('valid_until')
                        ->orWhere('valid_until', '>=', now());
                });
        }

        // Ordenamiento
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        if ($sortField === 'promotion') {
            $query->orderBy('name', $sortDirection);
        } elseif (in_array($sortField, ['type', 'is_active', 'applies_to', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection);
        } elseif ($sortField === 'items_count') {
            $query->orderBy('items_count', $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = (int) $request->input('per_page', 10);
        $promotions = $query->paginate($perPage)->withQueryString();

        // Calcular estadísticas
        $allPromotions = Promotion::all();
        $activePromotions = $allPromotions->where('is_active', true);

        // Promociones válidas ahora (activas + vigentes)
        $validNowPromotions = $activePromotions->filter(function ($promotion) {
            return $promotion->isValidNow();
        });

        return Inertia::render('menu/promotions/index', [
            'promotions' => $promotions,
            'stats' => [
                'total_promotions' => $allPromotions->count(),
                'active_promotions' => $activePromotions->count(),
                'valid_now_promotions' => $validNowPromotions->count(),
            ],
            'filters' => [
                'search' => $request->input('search'),
                'type' => $request->input('type'),
                'is_active' => $request->input('is_active'),
                'applies_to' => $request->input('applies_to'),
                'only_valid' => $request->input('only_valid'),
                'per_page' => $perPage,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
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
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:two_for_one,percentage_discount',
            'discount_value' => 'nullable|numeric|min:0|max:100',
            'applies_to' => 'required|in:product,category',
            'is_permanent' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'has_time_restriction' => 'boolean',
            'time_from' => 'nullable|date_format:H:i',
            'time_until' => 'nullable|date_format:H:i',
            'active_days' => 'nullable|array',
            'active_days.*' => 'integer|min:0|max:6',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.category_id' => 'nullable|exists:categories,id',
        ]);

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        DB::transaction(function () use ($validated, $items, &$promotion) {
            $promotion = Promotion::create($validated);

            // Crear los items
            if (! empty($items)) {
                foreach ($items as $item) {
                    $promotion->items()->create([
                        'product_id' => $item['product_id'] ?? null,
                        'category_id' => $item['category_id'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('menu.promotions.index')
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
            'items.product',
            'items.category',
        ]);

        return Inertia::render('menu/promotions/edit', [
            'promotion' => $promotion,
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
     * Actualiza una promoción existente
     */
    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:two_for_one,percentage_discount',
            'discount_value' => 'nullable|numeric|min:0|max:100',
            'applies_to' => 'required|in:product,category',
            'is_permanent' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'has_time_restriction' => 'boolean',
            'time_from' => 'nullable|date_format:H:i',
            'time_until' => 'nullable|date_format:H:i',
            'active_days' => 'nullable|array',
            'active_days.*' => 'integer|min:0|max:6',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.category_id' => 'nullable|exists:categories,id',
        ]);

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
                        'category_id' => $item['category_id'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->route('menu.promotions.index')
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
    public function toggle(Promotion $promotion): RedirectResponse
    {
        $promotion->update([
            'is_active' => ! $promotion->is_active,
        ]);

        $status = $promotion->is_active ? 'activada' : 'desactivada';

        return redirect()->back()
            ->with('success', "Promoción {$status} exitosamente.");
    }
}
