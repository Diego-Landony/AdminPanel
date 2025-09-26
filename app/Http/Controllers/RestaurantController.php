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

        $query = Restaurant::query()
            ->select([
                'id',
                'name',
                'description',
                'address',
                'is_active',
                'delivery_active',
                'pickup_active',
                'phone',
                'minimum_order_amount',
                'delivery_fee',
                'estimated_delivery_time',
                'rating',
                'total_reviews',
                'sort_order',
                'created_at',
                'updated_at',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($sortField === 'restaurant') {
            $query->orderBy('name', $sortDirection);
        } elseif ($sortField === 'status') {
            $query->orderByRaw('
                CASE
                    WHEN is_active = 1 THEN 1
                    ELSE 2
                END '.($sortDirection === 'asc' ? 'ASC' : 'DESC'));
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $restaurants = $query->paginate($perPage)
            ->appends($request->all())
            ->through(function ($restaurant) {
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'description' => $restaurant->description,
                    'address' => $restaurant->address,
                    'is_active' => $restaurant->is_active,
                    'delivery_active' => $restaurant->delivery_active,
                    'pickup_active' => $restaurant->pickup_active,
                    'phone' => $restaurant->phone,
                    'minimum_order_amount' => $restaurant->minimum_order_amount,
                    'delivery_fee' => $restaurant->delivery_fee,
                    'estimated_delivery_time' => $restaurant->estimated_delivery_time,
                    'rating' => $restaurant->rating,
                    'total_reviews' => $restaurant->total_reviews,
                    'sort_order' => $restaurant->sort_order,
                    'status_text' => $restaurant->status_text,
                    'today_schedule' => $restaurant->today_schedule,
                    'is_open_now' => $restaurant->isOpenNow(),
                    'rating_stars' => $restaurant->rating_stars,
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
            'description' => 'nullable|string',
            'address' => 'required|string|max:255',
            'is_active' => 'boolean',
            'delivery_active' => 'boolean',
            'pickup_active' => 'boolean',
            'phone' => 'nullable|string|max:255',
            'schedule' => 'nullable|array',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'estimated_delivery_time' => 'nullable|integer|min:1',
            'email' => 'nullable|email|max:255',
            'manager_name' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
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
                'description' => $restaurant->description,
                'address' => $restaurant->address,
                'is_active' => $restaurant->is_active,
                'delivery_active' => $restaurant->delivery_active,
                'pickup_active' => $restaurant->pickup_active,
                'phone' => $restaurant->phone,
                'schedule' => $restaurant->schedule,
                'minimum_order_amount' => $restaurant->minimum_order_amount,
                'delivery_fee' => $restaurant->delivery_fee,
                'estimated_delivery_time' => $restaurant->estimated_delivery_time,
                'image' => $restaurant->image,
                'email' => $restaurant->email,
                'manager_name' => $restaurant->manager_name,
                'rating' => $restaurant->rating,
                'total_reviews' => $restaurant->total_reviews,
                'sort_order' => $restaurant->sort_order,
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
            'description' => 'nullable|string',
            'address' => 'required|string|max:255',
            'is_active' => 'boolean',
            'delivery_active' => 'boolean',
            'pickup_active' => 'boolean',
            'phone' => 'nullable|string|max:255',
            'schedule' => 'nullable|array',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'estimated_delivery_time' => 'nullable|integer|min:1',
            'email' => 'nullable|email|max:255',
            'manager_name' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
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
}
