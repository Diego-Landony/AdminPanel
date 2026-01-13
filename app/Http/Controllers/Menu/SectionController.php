<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menu\StoreSectionRequest;
use App\Http\Requests\Menu\UpdateSectionRequest;
use App\Models\Menu\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Section::query();

        // Ordenar siempre por sort_order para el drag & drop
        $query->orderBy('sort_order', 'asc');

        $sections = $query->get();

        return Inertia::render('menu/sections/index', [
            'sections' => $sections,
            'stats' => [
                'total_sections' => $sections->count(),
                'required_sections' => $sections->where('is_required', true)->count(),
                'total_options' => $sections->sum(fn ($s) => $s->options()->count()),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('menu/sections/create');
    }

    public function store(StoreSectionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $options = $validated['options'] ?? [];
        unset($validated['options']);

        // Generar sort_order automáticamente
        $maxOrder = Section::max('sort_order') ?? 0;
        $validated['sort_order'] = $maxOrder + 1;

        $section = Section::create($validated);

        // Crear las opciones
        if (! empty($options)) {
            foreach ($options as $index => $option) {
                $section->options()->create([
                    'name' => $option['name'],
                    'is_extra' => $option['is_extra'] ?? false,
                    'price_modifier' => $option['price_modifier'] ?? 0,
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()->route('menu.sections.index')
            ->with('success', 'Sección creada exitosamente.');
    }

    public function show(Section $section): Response
    {
        $section->load(['options', 'products' => function ($query) {
            $query->active()->ordered()->take(10);
        }]);

        return Inertia::render('menu/sections/show', [
            'section' => $section,
        ]);
    }

    public function edit(Section $section): Response
    {
        $section->load('options');

        return Inertia::render('menu/sections/edit', [
            'section' => $section,
        ]);
    }

    public function update(UpdateSectionRequest $request, Section $section): RedirectResponse
    {
        $validated = $request->validated();
        $options = $validated['options'] ?? [];
        unset($validated['options']);

        $section->update($validated);

        // Actualizar opciones: preservar IDs existentes para no romper carritos
        $existingOptionIds = $section->options()->pluck('id')->toArray();
        $updatedOptionIds = [];

        if (! empty($options)) {
            foreach ($options as $index => $option) {
                if (! empty($option['id']) && in_array($option['id'], $existingOptionIds)) {
                    // Actualizar opción existente
                    $section->options()->where('id', $option['id'])->update([
                        'name' => $option['name'],
                        'is_extra' => $option['is_extra'] ?? false,
                        'price_modifier' => $option['price_modifier'] ?? 0,
                        'sort_order' => $index,
                    ]);
                    $updatedOptionIds[] = $option['id'];
                } else {
                    // Crear nueva opción
                    $newOption = $section->options()->create([
                        'name' => $option['name'],
                        'is_extra' => $option['is_extra'] ?? false,
                        'price_modifier' => $option['price_modifier'] ?? 0,
                        'sort_order' => $index,
                    ]);
                    $updatedOptionIds[] = $newOption->id;
                }
            }
        }

        // Eliminar solo las opciones que fueron removidas
        $section->options()->whereNotIn('id', $updatedOptionIds)->delete();

        return redirect()->route('menu.sections.index')
            ->with('success', 'Sección actualizada exitosamente.');
    }

    public function destroy(Section $section): RedirectResponse
    {
        // Verificar si está en uso
        if ($section->products()->count() > 0) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar la sección porque está siendo usada por productos.');
        }

        $section->delete();

        return redirect()->route('menu.sections.index')
            ->with('success', 'Sección eliminada exitosamente.');
    }

    public function usage(Section $section): Response
    {
        $section->load(['products' => function ($query) {
            $query->with('category')->active()->ordered();
        }]);

        return Inertia::render('menu/sections/usage', [
            'section' => $section,
        ]);
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'sections' => 'required|array',
            'sections.*.id' => 'required|exists:sections,id',
            'sections.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->sections as $section) {
            Section::where('id', $section['id'])->update(['sort_order' => $section['sort_order']]);
        }

        $count = count($request->sections);

        // Log de reordenamiento manual
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'event_type' => 'reordered',
            'target_model' => Section::class,
            'target_id' => null,
            'description' => "Secciones reordenadas ({$count} elementos)",
            'old_values' => null,
            'new_values' => ['items_count' => $count],
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->back()
            ->with('success', 'Orden actualizado exitosamente.');
    }
}
