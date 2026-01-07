<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\StorePromotionalBannerRequest;
use App\Http\Requests\Marketing\UpdatePromotionalBannerRequest;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\PromotionalBanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PromotionalBannerController extends Controller
{
    public function index(): Response
    {
        $banners = PromotionalBanner::query()
            ->ordered()
            ->get()
            ->map(fn ($banner) => [
                ...$banner->toArray(),
                'image_url' => $banner->getImageUrl(),
                'is_valid_now' => $banner->isValidNow(),
            ]);

        return Inertia::render('marketing/banners/index', [
            'banners' => $banners,
            'stats' => [
                'total' => $banners->count(),
                'active' => $banners->where('is_active', true)->count(),
                'horizontal' => $banners->where('orientation', 'horizontal')->count(),
                'vertical' => $banners->where('orientation', 'vertical')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('marketing/banners/create', [
            'linkOptions' => $this->getLinkOptions(),
        ]);
    }

    public function store(StorePromotionalBannerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('marketing/banners', 'public');
        }

        // Set sort order
        $maxOrder = PromotionalBanner::max('sort_order') ?? 0;
        $data['sort_order'] = $maxOrder + 1;

        // Clean weekdays if not weekdays validity type
        if ($data['validity_type'] !== 'weekdays') {
            $data['weekdays'] = null;
        }

        // Clean date fields if permanent
        if ($data['validity_type'] === 'permanent') {
            $data['valid_from'] = null;
            $data['valid_until'] = null;
        }

        PromotionalBanner::create($data);

        return redirect()->route('marketing.banners.index')
            ->with('success', 'Banner creado exitosamente.');
    }

    public function edit(PromotionalBanner $banner): Response
    {
        return Inertia::render('marketing/banners/edit', [
            'banner' => [
                ...$banner->toArray(),
                'image_url' => $banner->getImageUrl(),
            ],
            'linkOptions' => $this->getLinkOptions(),
        ]);
    }

    public function update(UpdatePromotionalBannerRequest $request, PromotionalBanner $banner): RedirectResponse
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $data['image'] = $request->file('image')->store('marketing/banners', 'public');
        }

        // Clean weekdays if not weekdays validity type
        if ($data['validity_type'] !== 'weekdays') {
            $data['weekdays'] = null;
        }

        // Clean date fields if permanent
        if ($data['validity_type'] === 'permanent') {
            $data['valid_from'] = null;
            $data['valid_until'] = null;
        }

        $banner->update($data);

        return redirect()->route('marketing.banners.index')
            ->with('success', 'Banner actualizado exitosamente.');
    }

    public function destroy(PromotionalBanner $banner): RedirectResponse
    {
        // Delete image
        if ($banner->image) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        return redirect()->route('marketing.banners.index')
            ->with('success', 'Banner eliminado exitosamente.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'banners' => 'required|array',
            'banners.*.id' => 'required|exists:promotional_banners,id',
            'banners.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->banners as $item) {
            PromotionalBanner::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return redirect()->back()
            ->with('success', 'Orden actualizado exitosamente.');
    }

    public function toggleActive(PromotionalBanner $banner): RedirectResponse
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        $status = $banner->is_active ? 'activado' : 'desactivado';

        return redirect()->back()
            ->with('success', "Banner {$status} exitosamente.");
    }

    /**
     * Get available link options for the form.
     */
    private function getLinkOptions(): array
    {
        return [
            'products' => Product::active()
                ->ordered()
                ->get(['id', 'name'])
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name]),
            'combos' => Combo::active()
                ->available()
                ->ordered()
                ->get(['id', 'name'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
            'categories' => Category::active()
                ->ordered()
                ->get(['id', 'name'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
        ];
    }
}
