<?php

namespace App\Http\Controllers;

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
    /**
     * Muestra la lista de todos los clientes del sistema
     *
     * @param  Request  $request  - Request actual
     * @return \Inertia\Response - Vista de Inertia con la lista de clientes
     */
    public function index(Request $request): Response
    {
        // Obtener parámetros de búsqueda, paginación y ordenamiento
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);
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

        // Query base con relación de tipo de cliente y conteo de direcciones y NITs
        // IMPORTANTE: select() debe ir ANTES de withCount() para no sobrescribir las columnas de conteo
        $query = Customer::select([
            'id',
            'name',
            'email',
            'subway_card',
            'birth_date',
            'gender',
            'customer_type_id',
            'phone',
            'email_verified_at',
            'created_at',
            'updated_at',
            'last_activity_at',
            'last_purchase_at',
            'points',
            'points_updated_at',
        ])
            ->with('customerType')
            ->withCount('addresses', 'nits');

        // Aplicar búsqueda global si existe
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subway_card', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('customerType', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Aplicar ordenamiento múltiple si está disponible
        if (! empty($multipleSortCriteria)) {
            foreach ($multipleSortCriteria as $criteria) {
                $field = $criteria['field'] ?? 'created_at';
                $direction = $criteria['direction'] ?? 'desc';

                if ($field === 'customer' || $field === 'name') {
                    $query->orderBy('name', $direction);
                } elseif ($field === 'status') {
                    $query->orderByRaw('
                        CASE
                            WHEN last_activity_at IS NULL THEN 4
                            WHEN last_activity_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1
                            WHEN last_activity_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 2
                            ELSE 3
                        END '.($direction === 'asc' ? 'ASC' : 'DESC'));
                } elseif ($field === 'points') {
                    $query->orderBy('points', $direction);
                } elseif ($field === 'last_purchase') {
                    $query->orderBy('last_purchase_at', $direction);
                } else {
                    $query->orderBy($field, $direction);
                }
            }
        } else {
            // Fallback a ordenamiento único
            if ($sortField === 'customer' || $sortField === 'name') {
                $query->orderBy('name', $sortDirection);
            } elseif ($sortField === 'status') {
                $query->orderByRaw('
                    CASE
                        WHEN last_activity_at IS NULL THEN 4
                        WHEN last_activity_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1
                        WHEN last_activity_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 2
                        ELSE 3
                    END '.($sortDirection === 'asc' ? 'ASC' : 'DESC'));
            } elseif ($sortField === 'points') {
                $query->orderBy('points', $sortDirection);
            } elseif ($sortField === 'last_purchase') {
                $query->orderBy('last_purchase_at', $sortDirection);
            } else {
                $query->orderBy($sortField, $sortDirection);
            }
        }

        // Paginar y obtener clientes
        $customers = $query->paginate($perPage)
            ->appends($request->all()) // Preservar filtros en paginación
            ->through(function ($customer) {
                // Actualizar tipo de cliente basado en puntos
                if ($customer->points !== null) {
                    $customer->updateCustomerType();
                }

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
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
                    // ✅ Legacy compatibility: provide client_type as computed field
                    'client_type' => $customer->customerType?->name ?? 'regular',
                    'phone' => $customer->phone,
                    'addresses_count' => $customer->addresses_count ?? 0,
                    'nits_count' => $customer->nits_count ?? 0,
                    'email_verified_at' => $customer->email_verified_at,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at,
                    'last_activity' => $customer->last_activity_at,
                    'last_purchase' => $customer->last_purchase_at,
                    'points' => $customer->points ?? 0,
                    'points_updated_at' => $customer->points_updated_at,
                    'is_online' => $customer->is_online, // ✅ Usar accessor del modelo
                    'status' => $customer->status, // ✅ Usar accessor del modelo
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
            'online_customers' => $totalStats->filter(function ($customer) {
                return $customer->is_online; // ✅ Usar accessor del modelo
            })->count(),
            'premium_customers' => $premiumCount,
            'vip_customers' => $vipCount,
            'customer_type_stats' => $customerTypeStats,
            'filters' => [
                'search' => $search,
                'per_page' => (int) $perPage,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
                'sort_criteria' => $multipleSortCriteria,
            ],
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
    public function store(Request $request): RedirectResponse|\Inertia\Response
    {
        // If request contains search/filter parameters, redirect to index method
        if ($request->hasAny(['search', 'per_page', 'sort_field', 'sort_direction', 'page'])) {
            return $this->index($request);
        }

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:customers',
                'password' => 'required|string|min:6|confirmed',
                'subway_card' => 'nullable|string|max:255|unique:customers',
                'birth_date' => 'required|date|before:today',
                'gender' => 'nullable|string|max:50',
                'customer_type_id' => 'nullable|exists:customer_types,id',
                'phone' => 'nullable|string|max:255',
            ]);

            // Get default customer type if not provided
            $customerTypeId = $request->customer_type_id ?? CustomerType::getDefault()?->id;

            $customerData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'customer_type_id' => $customerTypeId,
                'phone' => $request->phone,
                'email_verified_at' => now(),
            ];

            if ($request->filled('subway_card')) {
                $customerData['subway_card'] = $request->subway_card;
            }

            $customer = Customer::create($customerData);

            // Actualizar tipo de cliente automáticamente basado en puntos
            $customer->updateCustomerType();

            return redirect()->route('customers.index')->with('success', 'Cliente creado exitosamente');
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error de base de datos al crear cliente: '.$e->getMessage());

            if (str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'UNIQUE constraint')) {
                return back()->with('error', 'El email o tarjeta subway ya están registrados en el sistema. Usa datos diferentes.');
            }

            return back()->with('error', 'Error de base de datos al crear el cliente. Verifica que los datos sean correctos.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error inesperado al crear cliente: '.$e->getMessage());

            return back()->with('error', 'Error inesperado al crear el cliente. Inténtalo de nuevo o contacta al administrador.');
        }
    }

    /**
     * Muestra el formulario para editar un cliente
     */
    public function edit(Customer $customer): Response
    {
        $customer->load('addresses', 'nits');

        $customerData = [
            'id' => $customer->id,
            'name' => $customer->name,
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
            'addresses' => $customer->addresses->map(function ($address) {
                return [
                    'id' => $address->id,
                    'label' => $address->label,
                    'address_line' => $address->address_line,
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                    'delivery_notes' => $address->delivery_notes,
                    'is_default' => $address->is_default,
                ];
            }),
            'nits' => $customer->nits->map(function ($nit) {
                return [
                    'id' => $nit->id,
                    'nit' => $nit->nit,
                    'nit_type' => $nit->nit_type,
                    'business_name' => $nit->business_name,
                    'is_default' => $nit->is_default,
                ];
            }),
            'email_verified_at' => $customer->email_verified_at ? $customer->email_verified_at->toISOString() : null,
            'created_at' => $customer->created_at ? $customer->created_at->toISOString() : null,
            'updated_at' => $customer->updated_at ? $customer->updated_at->toISOString() : null,
            'last_activity_at' => $customer->last_activity_at ? $customer->last_activity_at->toISOString() : null,
        ];

        $customerTypes = CustomerType::active()->ordered()->get();

        return Inertia::render('customers/edit', [
            'customer' => $customerData,
            'customer_types' => $customerTypes,
        ]);
    }

    /**
     * Actualiza un cliente existente
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|string|lowercase|email|max:255|unique:customers,email,'.$customer->id,
                'subway_card' => 'required|string|max:255|unique:customers,subway_card,'.$customer->id,
                'birth_date' => 'required|date|before:today',
                'gender' => 'nullable|string|max:50',
                'customer_type_id' => 'nullable|exists:customer_types,id',
                'phone' => 'nullable|string|max:255',
            ];

            // Solo validar contraseña si se proporciona
            if ($request->filled('password')) {
                $rules['password'] = 'string|min:6|confirmed';
            }

            $request->validate($rules);

            // Si el email cambió, marcar como no verificado
            $emailChanged = $customer->email !== $request->email;

            $customerData = [
                'name' => $request->name,
                'email' => $request->email,
                'subway_card' => $request->subway_card,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'customer_type_id' => $request->customer_type_id,
                'phone' => $request->phone,
            ];

            // Solo actualizar contraseña si se proporciona
            if ($request->filled('password')) {
                $customerData['password'] = Hash::make($request->password);
            }

            $customer->update($customerData);

            // Resetear verificación de email si cambió (después del update)
            if ($emailChanged) {
                $customer->email_verified_at = null;
                $customer->save();
            }

            return back()->with('success', 'Cliente actualizado exitosamente');
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error de base de datos al actualizar cliente: '.$e->getMessage());

            if (str_contains($e->getMessage(), 'UNIQUE constraint failed') ||
                str_contains($e->getMessage(), 'Duplicate entry') ||
                str_contains($e->getMessage(), 'UNIQUE constraint')) {
                return back()->with('error', 'El email o tarjeta subway ya están registrados por otro cliente. Usa datos diferentes.');
            }

            return back()->with('error', 'Error de base de datos al actualizar el cliente. Verifica que los datos sean correctos.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error inesperado al actualizar cliente: '.$e->getMessage());

            return back()->with('error', 'Error inesperado al actualizar el cliente. Inténtalo de nuevo o contacta al administrador.');
        }
    }

    /**
     * Elimina un cliente
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        try {
            $customerName = $customer->name;
            $customer->delete();

            return back()->with('success', "Cliente '{$customerName}' eliminado exitosamente");
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error de base de datos al eliminar cliente: '.$e->getMessage());

            if (str_contains($e->getMessage(), 'FOREIGN KEY constraint failed') ||
                str_contains($e->getMessage(), 'Cannot delete or update a parent row')) {
                return back()->with('error', 'No se puede eliminar el cliente porque tiene registros asociados.');
            }

            return back()->with('error', 'Error de base de datos al eliminar el cliente. Verifica que no tenga dependencias.');
        } catch (\Exception $e) {
            \Log::error('Error inesperado al eliminar cliente: '.$e->getMessage());

            return back()->with('error', 'Error inesperado al eliminar el cliente. Inténtalo de nuevo o contacta al administrador.');
        }
    }
}
