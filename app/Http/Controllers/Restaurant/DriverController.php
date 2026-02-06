<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    public function index(Request $request): Response
    {
        $restaurantId = auth('restaurant')->user()->restaurant_id;

        $drivers = Driver::where('restaurant_id', $restaurantId)
            ->withCount(['orders as active_orders_count' => function ($query) {
                $query->whereIn('status', ['out_for_delivery']);
            }])
            ->orderBy('name')
            ->get()
            ->map(fn ($driver) => [
                'id' => $driver->id,
                'name' => $driver->name,
                'email' => $driver->email,
                'is_active' => $driver->is_active,
                'is_available' => $driver->is_available,
                'active_orders_count' => $driver->active_orders_count,
            ]);

        return Inertia::render('restaurant/drivers/index', [
            'drivers' => $drivers,
            'total_drivers' => $drivers->count(),
            'available_drivers' => $drivers->where('is_available', true)->count(),
        ]);
    }
}
