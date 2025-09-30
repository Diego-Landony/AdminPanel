<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\{HandlesExceptions, HasDataTableFeatures};
use App\Http\Requests\CustomerType\{StoreCustomerTypeRequest, UpdateCustomerTypeRequest};
use App\Models\CustomerType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerTypeController extends Controller
{
    use HandlesExceptions, HasDataTableFeatures;

    /**
     * Campos permitidos para ordenamiento
     */
    protected array $allowedSortFields = ['name', 'points_required', 'multiplier', 'customers_count', 'is_active', 'created_at'];
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // Obtener parámetros usando trait
        $params = $this->getPaginationParams($request);

        // Query base
        $query = CustomerType::query()->withCount('customers');

        // Aplicar búsqueda usando trait
        $query = $this->applySearch($query, $params['search'], ['name']);

        // Aplicar ordenamiento usando trait
        $fieldMappings = [
            'type' => 'name',
        ];

        if (! empty($params['multiple_sort_criteria'])) {
            $query = $this->applyMultipleSorting($query, $params['multiple_sort_criteria'], $fieldMappings);
        } else {
            // Si no hay sort específico, usar el scope ordered() del modelo
            if ($params['sort_field'] === 'sort_order' || empty($params['sort_field'])) {
                $query->ordered();
            } else {
                $query = $this->applySorting(
                    $query,
                    $params['sort_field'],
                    $params['sort_direction'],
                    $fieldMappings
                );
            }
        }

        $customerTypes = $query->paginate($params['per_page'])->appends($request->all());

        // Get stats from the base query (without pagination)
        $allCustomerTypes = CustomerType::select(['id', 'is_active'])->get();

        return Inertia::render('customers/types/index', [
            'customer_types' => $customerTypes,
            'stats' => [
                'total_types' => $allCustomerTypes->count(),
                'active_types' => $allCustomerTypes->where('is_active', true)->count(),
            ],
            'filters' => $this->buildFiltersResponse($params),
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
    public function store(StoreCustomerTypeRequest $request): RedirectResponse|\Inertia\Response
    {
        // If request contains search/filter parameters, redirect to index method
        if ($request->hasAny(['search', 'per_page', 'sort_field', 'sort_direction', 'page'])) {
            return $this->index($request);
        }

        return $this->executeWithExceptionHandling(
            operation: function () use ($request) {
                CustomerType::create($request->validated());

                return redirect()->route('customer-types.index')
                    ->with('success', 'Tipo de cliente creado exitosamente');
            },
            context: 'crear',
            entity: 'tipo de cliente'
        );
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
    public function update(UpdateCustomerTypeRequest $request, CustomerType $customerType): RedirectResponse
    {
        return $this->executeWithExceptionHandling(
            operation: function () use ($request, $customerType) {
                $customerType->update($request->validated());

                return back()->with('success', 'Tipo de cliente actualizado exitosamente');
            },
            context: 'actualizar',
            entity: 'tipo de cliente'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerType $customerType): RedirectResponse
    {
        return $this->executeWithExceptionHandling(
            operation: function () use ($customerType) {
                // Check if there are customers using this type
                if ($customerType->customers()->exists()) {
                    return back()->with('error', 'No se puede eliminar este tipo de cliente porque tiene clientes asignados');
                }

                $customerTypeName = $customerType->name;
                $customerType->delete();

                return back()->with('success', "Tipo de cliente '{$customerTypeName}' eliminado exitosamente");
            },
            context: 'eliminar',
            entity: 'tipo de cliente'
        );
    }
}
