<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\UpdateProductVariantRequest;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de Variantes de Producto
 *
 * Maneja el CRUD de variantes con sus 4 precios y configuración de Sub del Día.
 * Las variantes se generan automáticamente desde CategoryController->attachProduct(),
 * pero aquí se pueden editar precios, activar/desactivar, y configurar Sub del Día.
 */
class ProductVariantController extends Controller
{
    /**
     * Listar todas las variantes de un producto
     */
    public function index(Request $request, Product $product): Response
    {
        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 15);
        $sortField = $request->get('sort_field', 'sort_order');
        $sortDirection = $request->get('sort_direction', 'asc');

        $query = $product->variants();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('size', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        if (in_array($sortField, ['sku', 'name', 'size', 'precio_pickup_capital', 'is_active', 'sort_order'])) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('sort_order');
        }

        $variants = $query->paginate($perPage);

        // Stats
        $allVariants = $product->variants;

        return Inertia::render('menu/products/variants/index', [
            'product' => $product,
            'variants' => $variants,
            'stats' => [
                'total_variants' => $allVariants->count(),
                'active_variants' => $allVariants->where('is_active', true)->count(),
                'daily_specials' => $allVariants->where('is_daily_special', true)->count(),
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
     * Mostrar formulario de edición de variante
     * Aquí se editan los 4 precios regulares, los 4 precios de Sub del Día,
     * y la configuración de días activos para Sub del Día
     */
    public function edit(Product $product, ProductVariant $variant): Response
    {
        // Verificar que la variante pertenece al producto
        if ($variant->product_id !== $product->id) {
            abort(404);
        }

        return Inertia::render('menu/products/variants/edit', [
            'product' => $product,
            'variant' => $variant,
            'daysOfWeek' => [
                ['value' => 0, 'label' => 'Domingo'],
                ['value' => 1, 'label' => 'Lunes'],
                ['value' => 2, 'label' => 'Martes'],
                ['value' => 3, 'label' => 'Miércoles'],
                ['value' => 4, 'label' => 'Jueves'],
                ['value' => 5, 'label' => 'Viernes'],
                ['value' => 6, 'label' => 'Sábado'],
            ],
        ]);
    }

    /**
     * Actualizar variante (precios y configuración)
     */
    public function update(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        // Verificar que la variante pertenece al producto
        if ($variant->product_id !== $product->id) {
            abort(404);
        }

        $validated = $request->validated();

        // Si is_daily_special es false, limpiar días y precios especiales
        if (! ($validated['is_daily_special'] ?? false)) {
            $validated['daily_special_days'] = null;
            $validated['daily_special_precio_pickup_capital'] = null;
            $validated['daily_special_precio_domicilio_capital'] = null;
            $validated['daily_special_precio_pickup_interior'] = null;
            $validated['daily_special_precio_domicilio_interior'] = null;
        }

        try {
            $variant->update($validated);

            return redirect()
                ->route('menu.products.variants.index', $product)
                ->with('success', 'Variante actualizada exitosamente.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al actualizar la variante: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Eliminar variante
     * NOTA: Solo se debe permitir eliminar si no está siendo usada en pedidos
     */
    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        // Verificar que la variante pertenece al producto
        if ($variant->product_id !== $product->id) {
            abort(404);
        }

        try {
            // TODO: Verificar si la variante está en pedidos antes de eliminar
            // if ($variant->orderItems()->exists()) {
            //     return back()->with('error', 'No se puede eliminar la variante porque está en pedidos.');
            // }

            $variant->delete();

            return redirect()
                ->route('menu.products.variants.index', $product)
                ->with('success', 'Variante eliminada exitosamente.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al eliminar la variante: '.$e->getMessage()]);
        }
    }

    /**
     * Reordenar variantes
     */
    public function reorder(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'variants' => 'required|array',
            'variants.*.id' => 'required|exists:product_variants,id',
            'variants.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['variants'] as $variantData) {
            ProductVariant::where('id', $variantData['id'])
                ->where('product_id', $product->id) // Seguridad: solo del producto actual
                ->update(['sort_order' => $variantData['sort_order']]);
        }

        $count = count($validated['variants']);

        // Log de reordenamiento manual
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'event_type' => 'reordered',
            'target_model' => ProductVariant::class,
            'target_id' => null,
            'description' => "Variantes de producto reordenadas ({$count} elementos)",
            'old_values' => null,
            'new_values' => ['items_count' => $count],
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->back()
            ->with('success', 'Orden actualizado exitosamente.');
    }

    /**
     * Actualización rápida de precios (para tabla inline editing)
     */
    public function quickUpdatePrices(Request $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        if ($variant->product_id !== $product->id) {
            abort(404);
        }

        $validated = $request->validate([
            'precio_pickup_capital' => 'required|numeric|min:0',
            'precio_domicilio_capital' => 'required|numeric|min:0',
            'precio_pickup_interior' => 'required|numeric|min:0',
            'precio_domicilio_interior' => 'required|numeric|min:0',
        ]);

        $variant->update($validated);

        return redirect()->back()
            ->with('success', 'Precios actualizados.');
    }
}
