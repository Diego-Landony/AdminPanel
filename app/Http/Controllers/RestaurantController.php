<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\{HandlesExceptions, HasDataTableFeatures};
use App\Http\Requests\Restaurant\{StoreRestaurantRequest, UpdateRestaurantRequest};
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantController extends Controller
{
    use HandlesExceptions, HasDataTableFeatures;

    /**
     * Campos permitidos para ordenamiento
     */
    protected array $allowedSortFields = ['name', 'address', 'sort_order', 'created_at'];
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // Obtener parámetros usando trait
        $params = $this->getPaginationParams($request);

        // Query base
        $query = Restaurant::query()
            ->select([
                'id',
                'name',
                'address',
                'latitude',
                'longitude',
                'is_active',
                'delivery_active',
                'pickup_active',
                'phone',
                'email',
                'schedule',
                'minimum_order_amount',
                'estimated_delivery_time',
                'geofence_kml',
                'created_at',
                'updated_at',
            ]);

        // Aplicar búsqueda usando trait
        $query = $this->applySearch($query, $params['search'], [
            'name',
            'address',
            'phone',
            'email',
        ]);

        // Aplicar ordenamiento usando trait
        $fieldMappings = [
            'restaurant' => 'name',
            'status' => 'CASE WHEN is_active = 1 THEN 1 ELSE 2 END',
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

        $restaurants = $query->paginate($params['per_page'])
            ->appends($request->all())
            ->through(function ($restaurant) {
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'address' => $restaurant->address,
                    'latitude' => $restaurant->latitude,
                    'longitude' => $restaurant->longitude,
                    'is_active' => $restaurant->is_active,
                    'delivery_active' => $restaurant->delivery_active,
                    'pickup_active' => $restaurant->pickup_active,
                    'phone' => $restaurant->phone,
                    'email' => $restaurant->email,
                    'minimum_order_amount' => $restaurant->minimum_order_amount,
                    'estimated_delivery_time' => $restaurant->estimated_delivery_time,
                    'geofence_kml' => $restaurant->geofence_kml,
                    'status_text' => $restaurant->status_text,
                    'today_schedule' => $restaurant->today_schedule,
                    'is_open_now' => $restaurant->isOpenNow(),
                    'has_geofence' => $restaurant->hasGeofence(),
                    'coordinates' => $restaurant->coordinates,
                    'created_at' => $restaurant->created_at,
                    'updated_at' => $restaurant->updated_at,
                ];
            });

        $totalStats = Restaurant::select(['id', 'is_active', 'delivery_active', 'pickup_active'])->get();

        return Inertia::render('restaurants/index', [
            'restaurants' => $restaurants,
            'total_restaurants' => $totalStats->count(),
            'active_restaurants' => $totalStats->where('is_active', true)->count(),
            'delivery_restaurants' => $totalStats->where('delivery_active', true)->where('is_active', true)->count(),
            'pickup_restaurants' => $totalStats->where('pickup_active', true)->where('is_active', true)->count(),
            'filters' => $this->buildFiltersResponse($params),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('restaurants/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRestaurantRequest $request): RedirectResponse|\Inertia\Response
    {
        // If request contains search/filter parameters, redirect to index method
        if ($request->hasAny(['search', 'per_page', 'sort_field', 'sort_direction', 'page'])) {
            return $this->index($request);
        }

        return $this->executeWithExceptionHandling(
            operation: function () use ($request) {
                Restaurant::create($request->validated());

                return redirect()->route('restaurants.index')
                    ->with('success', 'Restaurante creado exitosamente');
            },
            context: 'crear',
            entity: 'restaurante'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Restaurant $restaurant): Response
    {
        return Inertia::render('restaurants/edit', [
            'restaurant' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'address' => $restaurant->address,
                'latitude' => $restaurant->latitude,
                'longitude' => $restaurant->longitude,
                'is_active' => $restaurant->is_active,
                'delivery_active' => $restaurant->delivery_active,
                'pickup_active' => $restaurant->pickup_active,
                'phone' => $restaurant->phone,
                'email' => $restaurant->email,
                'schedule' => $restaurant->schedule,
                'minimum_order_amount' => $restaurant->minimum_order_amount,
                'estimated_delivery_time' => $restaurant->estimated_delivery_time,
                'geofence_kml' => $restaurant->geofence_kml,
                'created_at' => $restaurant->created_at,
                'updated_at' => $restaurant->updated_at,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant): RedirectResponse
    {
        return $this->executeWithExceptionHandling(
            operation: function () use ($request, $restaurant) {
                $restaurant->update($request->validated());

                return back()->with('success', 'Restaurante actualizado exitosamente');
            },
            context: 'actualizar',
            entity: 'restaurante'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Restaurant $restaurant): RedirectResponse
    {
        return $this->executeWithExceptionHandling(
            operation: function () use ($restaurant) {
                $restaurantName = $restaurant->name;
                $restaurant->delete();

                return back()->with('success', "Restaurante '{$restaurantName}' eliminado exitosamente");
            },
            context: 'eliminar',
            entity: 'restaurante'
        );
    }
}
