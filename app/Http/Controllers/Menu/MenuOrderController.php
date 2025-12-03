<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\Menu\BadgeType;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MenuOrderController extends Controller
{
    public function index(): Response
    {
        $categories = Category::orderBy('sort_order')->get();

        $menuStructure = $categories->map(function ($category) {
            $items = $category->is_combo_category
                ? Combo::where('category_id', $category->id)
                    ->orderBy('sort_order')
                    ->with(['activeBadges.badgeType'])
                    ->get()
                    ->map(fn ($combo) => [
                        'id' => $combo->id,
                        'name' => $combo->name,
                        'image' => $combo->image,
                        'is_active' => $combo->is_active,
                        'sort_order' => $combo->sort_order,
                        'badges' => $combo->activeBadges->map(fn ($b) => [
                            'id' => $b->id,
                            'badge_type_id' => $b->badge_type_id,
                            'validity_type' => $b->validity_type,
                            'valid_from' => $b->valid_from?->format('Y-m-d'),
                            'valid_until' => $b->valid_until?->format('Y-m-d'),
                            'weekdays' => $b->weekdays,
                            'badge_type' => [
                                'id' => $b->badgeType->id,
                                'name' => $b->badgeType->name,
                                'color' => $b->badgeType->color,
                            ],
                        ]),
                    ])
                : Product::where('category_id', $category->id)
                    ->orderBy('sort_order')
                    ->with(['activeBadges.badgeType'])
                    ->get()
                    ->map(fn ($product) => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'image' => $product->image,
                        'is_active' => $product->is_active,
                        'sort_order' => $product->sort_order,
                        'badges' => $product->activeBadges->map(fn ($b) => [
                            'id' => $b->id,
                            'badge_type_id' => $b->badge_type_id,
                            'validity_type' => $b->validity_type,
                            'valid_from' => $b->valid_from?->format('Y-m-d'),
                            'valid_until' => $b->valid_until?->format('Y-m-d'),
                            'weekdays' => $b->weekdays,
                            'badge_type' => [
                                'id' => $b->badgeType->id,
                                'name' => $b->badgeType->name,
                                'color' => $b->badgeType->color,
                            ],
                        ]),
                    ]);

            return [
                'category' => $category->only(['id', 'name', 'is_active', 'is_combo_category', 'sort_order']),
                'items' => $items,
                'item_type' => $category->is_combo_category ? 'combo' : 'product',
            ];
        })->values();

        $badgeTypes = BadgeType::active()->ordered()->get(['id', 'name', 'color']);

        return Inertia::render('menu/order/index', [
            'menuStructure' => $menuStructure,
            'badgeTypes' => $badgeTypes,
            'stats' => [
                'total_categories' => $categories->count(),
                'active_categories' => $categories->where('is_active', true)->count(),
                'total_products' => Product::count(),
                'total_combos' => Combo::count(),
            ],
        ]);
    }

    public function updateBadges(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_type' => 'required|in:product,combo',
            'item_id' => 'required|integer',
            'badges' => 'array',
            'badges.*.badge_type_id' => 'required|exists:badge_types,id',
            'badges.*.validity_type' => 'required|in:permanent,date_range,weekdays',
            'badges.*.valid_from' => 'required_if:badges.*.validity_type,date_range|nullable|date',
            'badges.*.valid_until' => 'required_if:badges.*.validity_type,date_range|nullable|date|after_or_equal:badges.*.valid_from',
            'badges.*.weekdays' => 'required_if:badges.*.validity_type,weekdays|nullable|array|min:1',
            'badges.*.weekdays.*' => 'integer|min:1|max:7',
        ]);

        $model = $validated['item_type'] === 'combo'
            ? Combo::findOrFail($validated['item_id'])
            : Product::findOrFail($validated['item_id']);

        $model->syncBadges($validated['badges'] ?? []);

        return redirect()->back()
            ->with('success', 'Badges actualizados exitosamente.');
    }

    public function toggleItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_type' => 'required|in:product,combo',
            'item_id' => 'required|integer',
        ]);

        $model = $validated['item_type'] === 'combo'
            ? Combo::findOrFail($validated['item_id'])
            : Product::findOrFail($validated['item_id']);

        $model->update(['is_active' => ! $model->is_active]);

        $status = $model->is_active ? 'activado' : 'desactivado';
        $type = $validated['item_type'] === 'combo' ? 'Combo' : 'Producto';

        return redirect()->back()
            ->with('success', "{$type} {$status} exitosamente.");
    }

    public function toggleCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $category = Category::findOrFail($validated['category_id']);
        $category->update(['is_active' => ! $category->is_active]);

        $status = $category->is_active ? 'activada' : 'desactivada';

        return redirect()->back()
            ->with('success', "Categoría {$status} exitosamente.");
    }
}
