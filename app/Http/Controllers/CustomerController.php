<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\{HandlesExceptions, HasDataTableFeatures};
use App\Http\Requests\Customer\{StoreCustomerRequest, UpdateCustomerRequest};
use App\Models\Customer;
use App\Models\CustomerType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para la gestión completa de clientes del sistema
 * Proporciona funcionalidades CRUD para clientes
 */
class CustomerController extends Controller
{
    use HandlesExceptions, HasDataTableFeatures;

    /**
     * Campos permitidos para ordenamiento
     */
    protected array $allowedSortFields = ['full_name', 'email', 'created_at', 'last_activity_at', 'puntos', 'last_purchase_at'];

    /**
     * Muestra la lista de todos los clientes del sistema
     */
    public function index(Request $request): Response
    {
        // Obtener parámetros usando trait
        $params = $this->getPaginationParams($request);

        // Query base con eager loading optimizado
        $query = Customer::with(['customerType' => function ($query) {
            $query->select('id', 'name', 'color', 'multiplier', 'points_required');
        }])
            ->select([
                'id',
                'full_name',
                'email',
                'subway_card',
                'birth_date',
                'gender',
                'customer_type_id',
                'phone',
                'location',
                'email_verified_at',
                'created_at',
                'updated_at',
                'last_activity_at',
                'last_purchase_at',
                'puntos',
                'puntos_updated_at',
            ]);

        // Aplicar búsqueda usando trait
        $query = $this->applySearch($query, $params['search'], [
            'full_name',
            'email',
            'subway_card',
            'phone',
            'customerType' => function ($roleQuery, $search) {
                $roleQuery->where('name', 'like', "%{$search}%");
            },
        ]);

        // Aplicar ordenamiento usando trait
        $fieldMappings = [
            'customer' => 'full_name',
            'status' => $this->getStatusSortExpression('asc'),
            'last_purchase' => 'last_purchase_at',
        ];

        if (! empty($params['multiple_sort_criteria'])) {
            $query = $this->applyMultipleSorting($query, $params['multiple_sort_criteria'], $fieldMappings);
        } else {
            $query = $this->applySorting(
                $query,
                $params['sort_field'],
                $params['sort_direction'],
                $fieldMappings
            );
        }

        // Paginar y obtener clientes
        $customers = $query->paginate($params['per_page'])
            ->appends($request->all())
            ->through(function ($customer) {
                // Actualizar tipo de cliente basado en puntos
                if ($customer->puntos !== null) {
                    $customer->updateCustomerType();
                }

                return [
                    'id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'email' => $customer->email,
                    'subway_card' => $customer->subway_card,
                    'birth_date' => $customer->birth_date,
                    'gender' => $customer->gender,
                    'customer_type' => $customer->customerType ? [
                        'id' => $customer->customerType->id,
                        'name' => $customer->customerType->name,
                        'color' => $customer->customerType->color,
                        'multiplier' => $customer->customerType->multiplier,
                    ] : null,
                    'client_type' => $customer->customerType?->name ?? 'regular',
                    'phone' => $customer->phone,
                    'location' => $customer->location,
                    'email_verified_at' => $customer->email_verified_at,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at,
                    'last_activity' => $customer->last_activity_at,
                    'last_purchase' => $customer->last_purchase_at,
                    'puntos' => $customer->puntos ?? 0,
                    'puntos_updated_at' => $customer->puntos_updated_at,
                    'is_online' => $customer->is_online,
                    'status' => $customer->status,
                ];
            });

        // Obtener estadísticas del total (sin paginación) con tipos de cliente
        $totalStats = Customer::with('customerType')
            ->select([
                'id',
                'email_verified_at',
                'last_activity_at',
                'customer_type_id',
            ])->get();

        // Obtener todos los tipos de clientes con estadísticas
        $customerTypes = CustomerType::active()->ordered()->get();
        $customerTypeStats = $customerTypes->map(function ($type) use ($totalStats) {
            $count = $totalStats->filter(function ($customer) use ($type) {
                return $customer->customer_type_id === $type->id;
            })->count();

            return [
                'id' => $type->id,
                'name' => $type->name,
                'color' => $type->color,
                'customer_count' => $count,
            ];
        });

        // Contar tipos específicos usando la nueva relación (compatibilidad)
        $premiumCount = $totalStats->filter(function ($customer) {
            return $customer->customerType && in_array($customer->customerType->name, ['premium', 'gold', 'platinum']);
        })->count();

        $vipCount = $totalStats->filter(function ($customer) {
            return $customer->customerType && $customer->customerType->name === 'platinum';
        })->count();

        return Inertia::render('customers/index', [
            'customers' => $customers,
            'total_customers' => $totalStats->count(),
            'verified_customers' => $totalStats->where('email_verified_at', '!=', null)->count(),
            'online_customers' => $totalStats->filter(fn ($customer) => $customer->is_online)->count(),
            'premium_customers' => $premiumCount,
            'vip_customers' => $vipCount,
            'customer_type_stats' => $customerTypeStats,
            'filters' => $this->buildFiltersResponse($params),
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo cliente
     */
    public function create(): Response
    {
        $customerTypes = CustomerType::active()->ordered()->get();

        return Inertia::render('customers/create', [
            'customer_types' => $customerTypes,
        ]);
    }

    /**
     * Almacena un nuevo cliente
     */
    public function store(StoreCustomerRequest $request): RedirectResponse|\Inertia\Response
    {
        // If request contains search/filter parameters, redirect to index method
        if ($request->hasAny(['search', 'per_page', 'sort_field', 'sort_direction', 'page'])) {
            return $this->index($request);
        }

        return $this->executeWithExceptionHandling(
            operation: function () use ($request) {
                // Get default customer type if not provided
                $customerTypeId = $request->customer_type_id ?? CustomerType::getDefault()?->id;

                $customer = Customer::create([
                    'full_name' => $request->full_name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'subway_card' => $request->subway_card,
                    'birth_date' => $request->birth_date,
                    'gender' => $request->gender,
                    'customer_type_id' => $customerTypeId,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'location' => $request->location,
                    'nit' => $request->nit,
                    'email_verified_at' => now(),
                    'timezone' => 'America/Guatemala',
                ]);

                // Actualizar tipo de cliente automáticamente basado en puntos
                $customer->updateCustomerType();

                return redirect()->route('customers.index')
                    ->with('success', 'Cliente creado exitosamente');
            },
            context: 'crear',
            entity: 'cliente'
        );
    }

    /**
     * Muestra el formulario para editar un cliente
     */
    public function edit(Customer $customer): Response
    {
        $customerData = [
            'id' => $customer->id,
            'full_name' => $customer->full_name,
            'email' => $customer->email,
            'subway_card' => $customer->subway_card,
            'birth_date' => $customer->birth_date ? $customer->birth_date->format('Y-m-d') : null,
            'gender' => $customer->gender,
            'customer_type_id' => $customer->customer_type_id,
            'customer_type' => $customer->customerType ? [
                'id' => $customer->customerType->id,
                'name' => $customer->customerType->name,
            ] : null,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'location' => $customer->location,
            'nit' => $customer->nit,
            'email_verified_at' => $customer->email_verified_at ? $customer->email_verified_at->toISOString() : null,
            'created_at' => $customer->created_at ? $customer->created_at->toISOString() : null,
            'updated_at' => $customer->updated_at ? $customer->updated_at->toISOString() : null,
            'last_activity_at' => $customer->last_activity_at ? $customer->last_activity_at->toISOString() : null,
        ];

        return Inertia::render('customers/edit', [
            'customer' => $customerData,
        ]);
    }

    /**
     * Actualiza un cliente existente
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        return $this->executeWithExceptionHandling(
            operation: function () use ($request, $customer) {
                $customerData = [
                    'full_name' => $request->full_name,
                    'email' => $request->email,
                    'subway_card' => $request->subway_card,
                    'birth_date' => $request->birth_date,
                ];

                // Campos opcionales
                if ($request->has('gender')) {
                    $customerData['gender'] = $request->gender;
                }
                if ($request->has('customer_type_id')) {
                    $customerData['customer_type_id'] = $request->customer_type_id;
                }
                if ($request->has('phone')) {
                    $customerData['phone'] = $request->phone;
                }
                if ($request->has('address')) {
                    $customerData['address'] = $request->address;
                }
                if ($request->has('location')) {
                    $customerData['location'] = $request->location;
                }
                if ($request->has('nit')) {
                    $customerData['nit'] = $request->nit;
                }

                // Solo actualizar contraseña si se proporciona
                if ($request->filled('password')) {
                    $customerData['password'] = Hash::make($request->password);
                }

                // Si el email cambió, marcar como no verificado
                if ($customer->email !== $request->email) {
                    $customerData['email_verified_at'] = null;
                }

                $customer->update($customerData);

                return back()->with('success', 'Cliente actualizado exitosamente');
            },
            context: 'actualizar',
            entity: 'cliente'
        );
    }

    /**
     * Elimina un cliente
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        return $this->executeWithExceptionHandling(
            operation: function () use ($customer) {
                $customerName = $customer->full_name;
                $customer->delete();

                return back()->with('success', "Cliente '{$customerName}' eliminado exitosamente");
            },
            context: 'eliminar',
            entity: 'cliente'
        );
    }
}
