<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerType;
use Carbon\Carbon;
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

        // Query base con relación de tipo de cliente
        $query = Customer::with('customerType')
            ->select([
                'id',
                'full_name',
                'email',
                'subway_card',
                'birth_date',
                'gender',
                'client_type',
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

        // Aplicar búsqueda global si existe
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subway_card', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('client_type', 'like', "%{$search}%");
            });
        }

        // Aplicar ordenamiento
        if ($sortField === 'full_name') {
            $query->orderBy('full_name', $sortDirection);
        } elseif ($sortField === 'status') {
            // Sintaxis compatible con MariaDB/MySQL
            $query->orderByRaw("
                CASE
                    WHEN last_activity_at IS NULL THEN 4
                    WHEN last_activity_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1
                    WHEN last_activity_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 2
                    ELSE 3
                END " . ($sortDirection === 'asc' ? 'ASC' : 'DESC'));
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        // Paginar y obtener clientes
        $customers = $query->paginate($perPage)
            ->appends($request->all()) // Preservar filtros en paginación
            ->through(function ($customer) {
                $isOnline = $this->isCustomerOnline($customer->last_activity_at);
                $status = $this->getCustomerStatus($customer->last_activity_at);

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
                    'client_type' => $customer->client_type, // Mantener para compatibilidad
                    'customer_type' => $customer->customerType ? [
                        'id' => $customer->customerType->id,
                        'name' => $customer->customerType->name,
                        'display_name' => $customer->customerType->display_name,
                        'color' => $customer->customerType->color,
                        'multiplier' => $customer->customerType->multiplier,
                    ] : null,
                    'phone' => $customer->phone,
                    'location' => $customer->location,
                    'email_verified_at' => $customer->email_verified_at,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at,
                    'last_activity' => $customer->last_activity_at,
                    'last_purchase' => $customer->last_purchase_at,
                    'puntos' => $customer->puntos ?? 0,
                    'puntos_updated_at' => $customer->puntos_updated_at,
                    'is_online' => $isOnline,
                    'status' => $status,
                ];
            });

        // Obtener estadísticas del total (sin paginación) con tipos de cliente
        $totalStats = Customer::with('customerType')
            ->select([
                'id',
                'email_verified_at',
                'last_activity_at',
                'client_type',
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
                'display_name' => $type->display_name,
                'color' => $type->color,
                'count' => $count,
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
                return $this->isCustomerOnline($customer->last_activity_at);
            })->count(),
            'premium_customers' => $premiumCount,
            'vip_customers' => $vipCount,
            'customer_type_stats' => $customerTypeStats,
            'filters' => [
                'search' => $search,
                'per_page' => (int) $perPage,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
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
    public function store(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:customers',
                'password' => 'required|string|min:8|confirmed',
                'subway_card' => 'required|string|max:255|unique:customers',
                'birth_date' => 'required|date|before:today',
                'gender' => 'nullable|string|max:50',
                'client_type' => 'nullable|string|max:50',
                'phone' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:1000',
                'location' => 'nullable|string|max:255',
                'nit' => 'nullable|string|max:255',
            ]);

            $customer = Customer::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'subway_card' => $request->subway_card,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'client_type' => $request->client_type ?? 'regular',
                'customer_type_id' => $request->customer_type_id,
                'phone' => $request->phone,
                'address' => $request->address,
                'location' => $request->location,
                'nit' => $request->nit,
                'email_verified_at' => now(),
                'timezone' => 'America/Guatemala',
            ]);

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
        $customerData = [
            'id' => $customer->id,
            'full_name' => $customer->full_name,
            'email' => $customer->email,
            'subway_card' => $customer->subway_card,
            'birth_date' => $customer->birth_date ? $customer->birth_date->format('Y-m-d') : null,
            'gender' => $customer->gender,
            'client_type' => $customer->client_type,
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
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        try {
            $rules = [
                'full_name' => 'required|string|max:255',
                'email' => 'required|string|lowercase|email|max:255|unique:customers,email,'.$customer->id,
                'subway_card' => 'required|string|max:255|unique:customers,subway_card,'.$customer->id,
                'birth_date' => 'required|date|before:today',
                'gender' => 'nullable|string|max:50',
                'client_type' => 'nullable|string|max:50',
                'phone' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:1000',
                'location' => 'nullable|string|max:255',
                'nit' => 'nullable|string|max:255',
            ];

            // Solo validar contraseña si se proporciona
            if ($request->filled('password')) {
                $rules['password'] = 'string|min:8|confirmed';
            }

            $request->validate($rules);

            $customerData = [
                'full_name' => $request->full_name,
                'email' => $request->email,
                'subway_card' => $request->subway_card,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'client_type' => $request->client_type ?? 'regular',
                'phone' => $request->phone,
                'address' => $request->address,
                'location' => $request->location,
                'nit' => $request->nit,
            ];

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
            $customerName = $customer->full_name;
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

    /**
     * Determina si un cliente está en línea
     * En línea: Última actividad < 5 minutos
     */
    private function isCustomerOnline($lastActivityAt): bool
    {
        if (! $lastActivityAt) {
            return false;
        }

        $lastActivity = Carbon::parse($lastActivityAt)->utc();
        $now = Carbon::now()->utc();

        return $lastActivity->diffInMinutes($now) < 5;
    }

    /**
     * Obtiene el estado del cliente basado en su última actividad
     */
    private function getCustomerStatus($lastActivityAt): string
    {
        if (! $lastActivityAt) {
            return 'never';
        }

        $lastActivity = Carbon::parse($lastActivityAt)->utc();
        $now = Carbon::now()->utc();
        $minutesDiff = $lastActivity->diffInMinutes($now);

        if ($minutesDiff < 5) {
            return 'online';
        } elseif ($minutesDiff < 15) {
            return 'recent';
        } else {
            return 'offline';
        }
    }
}
