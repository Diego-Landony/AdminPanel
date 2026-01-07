<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportReason;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SupportReasonController extends Controller
{
    public function index(): Response
    {
        $reasons = SupportReason::ordered()->withCount('tickets')->get();

        return Inertia::render('support/reasons/index', [
            'reasons' => $reasons,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $maxOrder = SupportReason::max('sort_order') ?? 0;

        SupportReason::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'Motivo creado exitosamente.');
    }

    public function update(Request $request, SupportReason $reason): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $reason->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'is_active' => $validated['is_active'] ?? $reason->is_active,
        ]);

        return back()->with('success', 'Motivo actualizado exitosamente.');
    }

    public function destroy(SupportReason $reason): RedirectResponse
    {
        if ($reason->tickets()->exists()) {
            return back()->with('error', 'No se puede eliminar un motivo con tickets asociados.');
        }

        $reason->delete();

        return back()->with('success', 'Motivo eliminado exitosamente.');
    }

    public function updateOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:support_reasons,id'],
        ]);

        foreach ($validated['order'] as $index => $id) {
            SupportReason::where('id', $id)->update(['sort_order' => $index]);
        }

        return back()->with('success', 'Orden actualizado.');
    }
}
