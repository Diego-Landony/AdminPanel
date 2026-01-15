<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
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

        // Estadisticas
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

        // Ordenes recientes
        $recentOrders = Order::where('restaurant_id', $restaurantId)
            ->with(['customer:id,first_name,last_name,email,phone'])
            ->whereIn('status', ['pending', 'preparing', 'ready', 'out_for_delivery'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer?->full_name ?? 'Cliente',
                'status' => $order->status,
                'service_type' => $order->service_type,
                'total' => $order->total,
                'created_at' => $order->created_at,
            ]);

        return Inertia::render('restaurant/dashboard', [
            'stats' => $stats,
            'recent_orders' => $recentOrders,
        ]);
    }
}
