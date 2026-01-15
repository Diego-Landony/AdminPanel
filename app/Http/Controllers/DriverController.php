<?php

namespace App\Http\Controllers;

use App\Http\Requests\Driver\StoreDriverRequest;
use App\Http\Requests\Driver\UpdateDriverRequest;
use App\Models\Driver;
use App\Models\Restaurant;
use App\Services\DriverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    public function __construct(
        protected DriverService $driverService
    ) {}

    /**
     * Muestra la lista de todos los motoristas.
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 15);
        $sortField = $request->get('sort_field', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $restaurantId = $request->get('restaurant_id');

        $query = Driver::query()
            ->select([
                'id',
                'restaurant_id',
                'name',
                'email',
                'phone',
                'is_active',
                'is_available',
                'last_activity_at',
                'created_at',
                'updated_at',
            ])
            ->with('restaurant:id,name');

        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('restaurant', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($sortField === 'restaurant') {
            $query->join('restaurants', 'drivers.restaurant_id', '=', 'restaurants.id')
                ->orderBy('restaurants.name', $sortDirection)
                ->select('drivers.*');
        } elseif ($sortField === 'status') {
            $query->orderByRaw('
                CASE
                    WHEN is_active = 0 THEN 3
                    WHEN is_available = 1 THEN 1
                    ELSE 2
                END '.($sortDirection === 'asc' ? 'ASC' : 'DESC'));
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $drivers = $query->paginate($perPage)
            ->appends($request->all())
            ->through(function ($driver) {
                return [
                    'id' => $driver->id,
                    'restaurant_id' => $driver->restaurant_id,
                    'restaurant' => $driver->restaurant ? [
                        'id' => $driver->restaurant->id,
                        'name' => $driver->restaurant->name,
                    ] : null,
                    'name' => $driver->name,
                    'email' => $driver->email,
                    'phone' => $driver->phone,
                    'is_active' => $driver->is_active,
                    'is_available' => $driver->is_available,
                    'is_online' => $driver->is_online,
                    'status' => $driver->status,
                    'last_activity_at' => $driver->last_activity_at,
                    'created_at' => $driver->created_at,
                    'updated_at' => $driver->updated_at,
                ];
            });

        // Estadisticas generales
        $statsQuery = Driver::query();
        if ($restaurantId) {
            $statsQuery->where('restaurant_id', $restaurantId);
        }

        $totalStats = $statsQuery->select(['id', 'is_active', 'is_available'])->get();

        $restaurants = Restaurant::active()->ordered()->get(['id', 'name']);

        return Inertia::render('drivers/index', [
            'drivers' => $drivers,
            'total_drivers' => $totalStats->count(),
            'active_drivers' => $totalStats->where('is_active', true)->count(),
            'available_drivers' => $totalStats->where('is_active', true)->where('is_available', true)->count(),
            'restaurants' => $restaurants,
            'filters' => [
                'search' => $search,
                'per_page' => (int) $perPage,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
                'restaurant_id' => $restaurantId ? (int) $restaurantId : null,
            ],
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo motorista.
     */
    public function create(): Response
    {
        $restaurants = Restaurant::active()->ordered()->get(['id', 'name']);

        return Inertia::render('drivers/create', [
            'restaurants' => $restaurants,
        ]);
    }

    /**
     * Almacena un nuevo motorista.
     */
    public function store(StoreDriverRequest $request): RedirectResponse
    {
        $this->driverService->create($request->validated());

        return redirect()->route('drivers.index')
            ->with('success', 'Motorista creado exitosamente.');
    }

    /**
     * Muestra el detalle de un motorista.
     */
    public function show(Driver $driver): Response
    {
        $driver->load(['restaurant:id,name', 'orders' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return Inertia::render('drivers/show', [
            'driver' => [
                'id' => $driver->id,
                'restaurant_id' => $driver->restaurant_id,
                'restaurant' => $driver->restaurant ? [
                    'id' => $driver->restaurant->id,
                    'name' => $driver->restaurant->name,
                ] : null,
                'name' => $driver->name,
                'email' => $driver->email,
                'phone' => $driver->phone,
                'is_active' => $driver->is_active,
                'is_available' => $driver->is_available,
                'is_online' => $driver->is_online,
                'status' => $driver->status,
                'current_latitude' => $driver->current_latitude,
                'current_longitude' => $driver->current_longitude,
                'last_location_update' => $driver->last_location_update,
                'last_activity_at' => $driver->last_activity_at,
                'created_at' => $driver->created_at,
                'updated_at' => $driver->updated_at,
                'recent_orders' => $driver->orders->map(fn ($order) => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'total' => $order->total,
                    'created_at' => $order->created_at,
                ]),
            ],
        ]);
    }

    /**
     * Muestra el formulario para editar un motorista.
     */
    public function edit(Driver $driver): Response
    {
        $restaurants = Restaurant::active()->ordered()->get(['id', 'name']);

        return Inertia::render('drivers/edit', [
            'driver' => [
                'id' => $driver->id,
                'restaurant_id' => $driver->restaurant_id,
                'name' => $driver->name,
                'email' => $driver->email,
                'phone' => $driver->phone,
                'is_active' => $driver->is_active,
                'is_available' => $driver->is_available,
                'created_at' => $driver->created_at,
                'updated_at' => $driver->updated_at,
                'last_login_at' => $driver->last_login_at,
            ],
            'restaurants' => $restaurants,
        ]);
    }

    /**
     * Actualiza un motorista existente.
     */
    public function update(UpdateDriverRequest $request, Driver $driver): RedirectResponse
    {
        $this->driverService->update($driver, $request->validated());

        return back()->with('success', 'Motorista actualizado exitosamente.');
    }

    /**
     * Elimina un motorista.
     */
    public function destroy(Driver $driver): RedirectResponse
    {
        $driverName = $driver->name;
        $this->driverService->delete($driver);

        return back()->with('success', "Motorista '{$driverName}' eliminado exitosamente.");
    }

    /**
     * Cambia la disponibilidad de un motorista.
     */
    public function toggleAvailability(Driver $driver): RedirectResponse
    {
        $this->driverService->toggleAvailability($driver);

        $status = $driver->fresh()->is_available ? 'disponible' : 'no disponible';

        return back()->with('success', "El motorista ahora esta {$status}.");
    }
}
