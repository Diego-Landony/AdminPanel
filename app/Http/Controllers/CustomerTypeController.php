<?php

namespace App\Http\Controllers;

use App\Models\CustomerType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $customerTypes = CustomerType::query()
            ->withCount('customers')
            ->ordered()
            ->get();

        return Inertia::render('customers/types/index', [
            'customer_types' => $customerTypes,
            'stats' => [
                'total_types' => $customerTypes->count(),
                'active_types' => $customerTypes->where('is_active', true)->count(),
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('customers/types/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:customer_types',
            'display_name' => 'required|string|max:100',
            'points_required' => 'required|integer|min:0',
            'multiplier' => 'required|numeric|min:1|max:10',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        CustomerType::create($validated);

        return redirect()->route('customer-types.index')
            ->with('success', 'Tipo de cliente creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomerType $customerType): Response
    {
        $customerType->load(['customers' => function ($query) {
            $query->latest()->take(10);
        }]);

        return Inertia::render('customers/types/show', [
            'customer_type' => $customerType,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CustomerType $customerType): Response
    {
        return Inertia::render('customers/types/edit', [
            'customer_type' => $customerType,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomerType $customerType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:customer_types,name,' . $customerType->id,
            'display_name' => 'required|string|max:100',
            'points_required' => 'required|integer|min:0',
            'multiplier' => 'required|numeric|min:1|max:10',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $customerType->update($validated);

        return redirect()->route('customer-types.index')
            ->with('success', 'Tipo de cliente actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerType $customerType): RedirectResponse
    {
        // Check if there are customers using this type
        if ($customerType->customers()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar este tipo de cliente porque tiene clientes asignados.');
        }

        $customerType->delete();

        return redirect()->route('customer-types.index')
            ->with('success', 'Tipo de cliente eliminado exitosamente.');
    }
}
