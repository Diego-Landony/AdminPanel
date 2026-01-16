<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $restaurantId = auth('restaurant')->user()->restaurant_id;
        $today = now()->startOfDay();

        // Estadisticas de ordenes
        $stats = [
            'pending_orders' => Order::where('restaurant_id', $restaurantId)
                ->where('status', 'pending')
                ->count(),
            'preparing_orders' => Order::where('restaurant_id', $restaurantId)
                ->where('status', 'preparing')
                ->count(),
            'ready_orders' => Order::where('restaurant_id', $restaurantId)
                ->where('status', 'ready')
                ->count(),
            'completed_today' => Order::where('restaurant_id', $restaurantId)
                ->where('status', 'completed')
                ->where('created_at', '>=', $today)
                ->count(),
            'total_today' => Order::where('restaurant_id', $restaurantId)
                ->where('created_at', '>=', $today)
                ->count(),
        ];

        // Estadisticas de ventas del dia (solo ordenes completadas)
        $salesStats = Order::where('restaurant_id', $restaurantId)
            ->where('created_at', '>=', $today)
            ->where('status', 'completed')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total ELSE 0 END), 0) as cash_total,
                COALESCE(SUM(CASE WHEN payment_method = 'card' THEN total ELSE 0 END), 0) as card_total,
                COALESCE(SUM(total), 0) as total_sales
            ")
            ->first();

        $stats['cash_sales_today'] = (float) $salesStats->cash_total;
        $stats['card_sales_today'] = (float) $salesStats->card_total;
        $stats['total_sales_today'] = (float) $salesStats->total_sales;

        // Ordenes activas (no completadas ni canceladas) - ordenadas por llegada (mas antiguas primero)
        $activeOrders = Order::where('restaurant_id', $restaurantId)
            ->with(['customer:id,first_name,last_name,email,phone', 'driver:id,name,phone'])
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer?->full_name ?? 'Cliente',
                'customer_phone' => $order->customer?->phone,
                'status' => $order->status,
                'service_type' => $order->service_type,
                'total' => $order->total,
                'payment_method' => $order->payment_method,
                'items_count' => $order->items()->count(),
                'driver' => $order->driver ? [
                    'id' => $order->driver->id,
                    'name' => $order->driver->name,
                ] : null,
                'created_at' => $order->created_at,
            ]);

        // Motoristas disponibles
        $availableDrivers = Driver::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->where('is_available', true)
            ->select(['id', 'name', 'phone'])
            ->get();

        return Inertia::render('restaurant/dashboard', [
            'stats' => $stats,
            'active_orders' => $activeOrders,
            'available_drivers' => $availableDrivers,
        ]);
    }
}
