<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\Menu\BadgeType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BadgeTypeController extends Controller
{
    public function index(): Response
    {
        $badgeTypes = BadgeType::query()
            ->withCount('productBadges')
            ->ordered()
            ->get();

        return Inertia::render('menu/badge-types/index', [
            'badgeTypes' => $badgeTypes,
            'stats' => [
                'total' => $badgeTypes->count(),
                'active' => $badgeTypes->where('is_active', true)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('menu/badge-types/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:30',
            'is_active' => 'boolean',
        ]);

        $maxOrder = BadgeType::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 1;

        BadgeType::create($validated);

        return redirect()->route('menu.badge-types.index')
            ->with('success', 'Badge creado exitosamente.');
    }

    public function edit(BadgeType $badgeType): Response
    {
        return Inertia::render('menu/badge-types/edit', [
            'badgeType' => $badgeType,
        ]);
    }

    public function update(Request $request, BadgeType $badgeType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:30',
            'is_active' => 'boolean',
        ]);

        $badgeType->update($validated);

        return redirect()->route('menu.badge-types.index')
            ->with('success', 'Badge actualizado exitosamente.');
    }

    public function destroy(BadgeType $badgeType): RedirectResponse
    {
        $badgeType->delete();

        return redirect()->route('menu.badge-types.index')
            ->with('success', 'Badge eliminado exitosamente.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'badge_types' => 'required|array',
            'badge_types.*.id' => 'required|exists:badge_types,id',
            'badge_types.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->badge_types as $item) {
            BadgeType::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return redirect()->back()
            ->with('success', 'Orden actualizado exitosamente.');
    }
}
