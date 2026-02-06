<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Http\Traits\ResolvesOrderOptions;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ResolvesOrderOptions;

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
                ->whereIn('status', ['completed', 'delivered'])
                ->where('created_at', '>=', $today)
                ->count(),
            'total_today' => Order::where('restaurant_id', $restaurantId)
                ->where('created_at', '>=', $today)
                ->count(),
        ];

        // Estadisticas de ventas del dia (ordenes completadas y entregadas)
        $salesStats = Order::where('restaurant_id', $restaurantId)
            ->where('created_at', '>=', $today)
            ->whereIn('status', ['completed', 'delivered'])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN total ELSE 0 END), 0) as cash_total,
                COALESCE(SUM(CASE WHEN payment_method = 'card' THEN total ELSE 0 END), 0) as card_total,
                COALESCE(SUM(total), 0) as total_sales
            ")
            ->first();

        $stats['cash_sales_today'] = (float) $salesStats->cash_total;
        $stats['card_sales_today'] = (float) $salesStats->card_total;
        $stats['total_sales_today'] = (float) $salesStats->total_sales;

        // Ordenes activas - ordenadas por prioridad de estado y luego más recientes primero
        $activeOrders = Order::where('restaurant_id', $restaurantId)
            ->with(['customer:id,first_name,last_name,email,phone,subway_card', 'driver:id,name', 'items', 'restaurant:id,name'])
            ->whereIn('status', ['pending', 'preparing', 'ready', 'out_for_delivery'])
            ->orderByRaw("FIELD(status, 'pending', 'preparing', 'ready', 'out_for_delivery')")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'restaurant_name' => $order->restaurant?->name,
                'customer_name' => $order->customer?->full_name ?? 'Cliente',
                'customer_phone' => $order->customer?->phone,
                'customer_subway_card' => $order->customer?->subway_card,
                'customer_email' => $order->customer?->email,
                'status' => $order->status,
                'service_type' => $order->service_type,
                'subtotal' => $order->subtotal,
                'discount' => $order->discount_total,
                'total' => $order->total,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'notes' => $order->notes,
                'delivery_address' => $order->delivery_address_snapshot,
                'items_count' => $order->items->count(),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->product_snapshot['name'] ?? 'Producto',
                    'variant' => $item->product_snapshot['variant'] ?? null,
                    'category' => $item->product_snapshot['category'] ?? null,
                    'quantity' => $item->quantity ?? 1,
                    'unit_price' => $item->unit_price ?? 0,
                    'options_price' => $item->options_price ?? 0,
                    'total_price' => $item->subtotal ?? 0,
                    'notes' => $item->notes,
                    'options' => $this->resolveSelectedOptions($item->selected_options ?? []),
                ]),
                'driver' => $order->driver ? [
                    'id' => $order->driver->id,
                    'name' => $order->driver->name,
                ] : null,
                'created_at' => $order->created_at,
                'estimated_ready_at' => $order->estimated_ready_at,
            ]);

        // Motoristas disponibles
        $availableDrivers = Driver::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->where('is_available', true)
            ->select(['id', 'name'])
            ->get();

        return Inertia::render('restaurant/dashboard', [
            'restaurant_id' => $restaurantId,
            'stats' => $stats,
            'active_orders' => $activeOrders,
            'available_drivers' => $availableDrivers,
            'config' => [
                'polling_interval' => config('restaurant.polling_interval'),
                'auto_print_new_orders' => config('restaurant.auto_print_new_orders'),
            ],
        ]);
    }

    /**
     * Endpoint de polling para detectar nuevas órdenes
     * Retorna solo los IDs de órdenes activas para comparación rápida
     */
    public function poll(): JsonResponse
    {
        $restaurantId = auth('restaurant')->user()->restaurant_id;

        $activeOrders = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['pending', 'preparing', 'ready', 'out_for_delivery'])
            ->orderBy('created_at', 'asc')
            ->get(['id', 'order_number', 'status', 'created_at']);

        return response()->json([
            'orders' => $activeOrders->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'created_at' => $order->created_at->toISOString(),
            ]),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
