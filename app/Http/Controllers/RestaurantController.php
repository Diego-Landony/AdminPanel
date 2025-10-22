<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 10);
        $sortField = $request->get('sort_field', 'sort_order');
        $sortDirection = $request->get('sort_direction', 'asc');
        $sortCriteria = $request->get('sort_criteria');

        // Parse multiple sort criteria if provided
        $multipleSortCriteria = [];
        if ($sortCriteria) {
            $decoded = json_decode($sortCriteria, true);
            if (is_array($decoded)) {
                $multipleSortCriteria = $decoded;
            }
        }

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

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Aplicar ordenamiento múltiple si está disponible
        if (! empty($multipleSortCriteria)) {
            foreach ($multipleSortCriteria as $criteria) {
                $field = $criteria['field'] ?? 'name';
                $direction = $criteria['direction'] ?? 'asc';

                if ($field === 'restaurant') {
                    $query->orderBy('name', $direction);
                } elseif ($field === 'status') {
                    $query->orderByRaw('
                        CASE
                            WHEN is_active = 1 THEN 1
                            ELSE 2
                        END '.($direction === 'asc' ? 'ASC' : 'DESC'));
                } else {
                    $query->orderBy($field, $direction);
                }
            }
        } else {
            // Fallback a ordenamiento único
            if ($sortField === 'restaurant') {
                $query->orderBy('name', $sortDirection);
            } elseif ($sortField === 'status') {
                $query->orderByRaw('
                    CASE
                        WHEN is_active = 1 THEN 1
                        ELSE 2
                    END '.($sortDirection === 'asc' ? 'ASC' : 'DESC'));
            } else {
                $query->ordered();
            }
        }

        $restaurants = $query->paginate($perPage)
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
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('restaurants/create');
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

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'boolean',
            'delivery_active' => 'boolean',
            'pickup_active' => 'boolean',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'schedule' => 'nullable|array',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'estimated_delivery_time' => 'nullable|integer|min:1',
            'geofence_kml' => 'nullable|string',
        ]);

        Restaurant::create($request->all());

        return redirect()->route('restaurants.index')
            ->with('success', 'Restaurante creado exitosamente.');
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
    public function update(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'boolean',
            'delivery_active' => 'boolean',
            'pickup_active' => 'boolean',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'schedule' => 'nullable|array',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'estimated_delivery_time' => 'nullable|integer|min:1',
        ]);

        $restaurant->update($request->all());

        return back()->with('success', 'Restaurante actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Restaurant $restaurant): RedirectResponse
    {
        $restaurantName = $restaurant->name;
        $restaurant->delete();

        return back()->with('success', "Restaurante '{$restaurantName}' eliminado exitosamente.");
    }

    /**
     * Save geofence from map coordinates
     */
    public function saveGeofence(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $request->validate([
            'geofence_kml' => 'required|string',
        ]);

        $restaurant->update([
            'geofence_kml' => $request->geofence_kml,
        ]);

        return back()->with('success', 'Geocerca actualizada exitosamente.');
    }
}
