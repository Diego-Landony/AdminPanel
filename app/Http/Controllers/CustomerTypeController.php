<?php

namespace App\Http\Controllers;

use App\Models\CustomerType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $sortCriteria = $request->get('sort_criteria');

        // Parse multiple sort criteria if provided
        $multipleSortCriteria = [];
        if ($sortCriteria) {
            $decoded = json_decode($sortCriteria, true);
            if (is_array($decoded)) {
                $multipleSortCriteria = $decoded;
            }
        }

        $query = CustomerType::query()
            ->withCount('customers');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Aplicar ordenamiento múltiple si está disponible
        if (!empty($multipleSortCriteria)) {
            foreach ($multipleSortCriteria as $criteria) {
                $field = $criteria['field'] ?? 'created_at';
                $direction = $criteria['direction'] ?? 'desc';

                if ($field === 'type') {
                    $query->orderBy('name', $direction);
                } elseif (in_array($field, ['points_required', 'multiplier', 'customers_count', 'is_active', 'created_at'])) {
                    $query->orderBy($field, $direction);
                } else {
                    $query->orderBy($field, $direction);
                }
            }
        } else {
            // Fallback a ordenamiento único
            if ($sortField === 'type') {
                $query->orderBy('name', $sortDirection);
            } elseif (in_array($sortField, ['points_required', 'multiplier', 'customers_count', 'is_active', 'created_at'])) {
                $query->orderBy($sortField, $sortDirection);
            } else {
                $query->ordered();
            }
        }

        $customerTypes = $query->paginate($perPage);

        // Get stats from the base query (without pagination)
        $allCustomerTypes = CustomerType::query()->withCount('customers')->get();

        return Inertia::render('customers/types/index', [
            'customer_types' => $customerTypes,
            'stats' => [
                'total_types' => $allCustomerTypes->count(),
                'active_types' => $allCustomerTypes->where('is_active', true)->count(),
            ],
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
                'sort_criteria' => $multipleSortCriteria,
            ],
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
    public function store(Request $request): RedirectResponse|\Inertia\Response
    {
        // If request contains search/filter parameters, redirect to index method
        if ($request->hasAny(['search', 'per_page', 'sort_field', 'sort_direction', 'page'])) {
            return $this->index($request);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:customer_types',
            'points_required' => 'required|integer|min:0',
            'multiplier' => 'required|numeric|min:1|max:10',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
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
            'name' => 'required|string|max:100|unique:customer_types,name,'.$customerType->id,
            'points_required' => 'required|integer|min:0',
            'multiplier' => 'required|numeric|min:1|max:10',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
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
