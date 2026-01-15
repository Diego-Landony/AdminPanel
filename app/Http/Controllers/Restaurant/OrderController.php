<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * Lista de ordenes del restaurante
     */
    public function index(Request $request): Response
    {
        $restaurantId = auth('restaurant')->user()->restaurant_id;

        $status = $request->get('status', '');
        $serviceType = $request->get('service_type', '');
        $perPage = $request->get('per_page', 20);

        $query = Order::where('restaurant_id', $restaurantId)
            ->with(['customer:id,first_name,last_name,phone', 'driver:id,name,phone'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($serviceType) {
            $query->where('service_type', $serviceType);
        }

        $orders = $query->paginate($perPage)
            ->appends($request->all())
            ->through(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer?->full_name ?? 'Cliente',
                'customer_phone' => $order->customer?->phone,
                'driver_name' => $order->driver?->name,
                'status' => $order->status,
                'service_type' => $order->service_type,
                'total' => $order->total,
                'items_count' => $order->items()->count(),
                'created_at' => $order->created_at,
                'estimated_ready_at' => $order->estimated_ready_at,
            ]);

        // Contadores por estado
        $statusCounts = [
            'pending' => Order::where('restaurant_id', $restaurantId)->where('status', 'pending')->count(),
            'preparing' => Order::where('restaurant_id', $restaurantId)->where('status', 'preparing')->count(),
            'ready' => Order::where('restaurant_id', $restaurantId)->where('status', 'ready')->count(),
            'out_for_delivery' => Order::where('restaurant_id', $restaurantId)->where('status', 'out_for_delivery')->count(),
        ];

        return Inertia::render('restaurant/orders/index', [
            'orders' => $orders,
            'status_counts' => $statusCounts,
            'filters' => [
                'status' => $status,
                'service_type' => $serviceType,
                'per_page' => (int) $perPage,
            ],
        ]);
    }

    /**
     * Detalle de una orden
     */
    public function show(Order $order): Response
    {
        // Verificar que la orden pertenece al restaurante
        $this->authorizeOrder($order);

        $order->load([
            'customer:id,first_name,last_name,email,phone',
            'driver:id,name,phone',
            'items.product:id,name',
            'statusHistory',
        ]);

        // Obtener motoristas disponibles del restaurante
        $availableDrivers = Driver::where('restaurant_id', auth('restaurant')->user()->restaurant_id)
            ->where('is_active', true)
            ->where('is_available', true)
            ->select(['id', 'name', 'phone'])
            ->get();

        return Inertia::render('restaurant/orders/show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'service_type' => $order->service_type,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'subtotal' => $order->subtotal,
                'delivery_fee' => $order->delivery_fee,
                'discount' => $order->discount_total,
                'total' => $order->total,
                'notes' => $order->notes,
                'delivery_address' => $order->delivery_address_snapshot,
                'created_at' => $order->created_at,
                'estimated_ready_at' => $order->estimated_ready_at,
                'ready_at' => $order->ready_at,
                'customer' => $order->customer ? [
                    'name' => $order->customer->full_name,
                    'email' => $order->customer->email,
                    'phone' => $order->customer->phone,
                ] : null,
                'driver' => $order->driver ? [
                    'id' => $order->driver->id,
                    'name' => $order->driver->name,
                    'phone' => $order->driver->phone,
                ] : null,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->product_snapshot['name'] ?? $item->product?->name ?? 'Producto',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'notes' => $item->notes,
                    'options' => $item->selected_options,
                ]),
                'status_history' => $order->statusHistory->map(fn ($h) => [
                    'id' => $h->id,
                    'status' => $h->new_status,
                    'notes' => $h->notes,
                    'created_at' => $h->created_at,
                ]),
            ],
            'available_drivers' => $availableDrivers,
            'can_accept' => $order->status === 'pending',
            'can_mark_ready' => $order->status === 'preparing',
            'can_assign_driver' => $order->status === 'ready' && $order->service_type === 'delivery' && ! $order->driver_id,
        ]);
    }

    /**
     * Aceptar orden (pending -> preparing)
     */
    public function accept(Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'pending') {
            return back()->with('error', 'Esta orden no puede ser aceptada');
        }

        $order->update([
            'status' => 'preparing',
            'estimated_ready_at' => now()->addMinutes(
                $order->service_type === 'delivery'
                    ? $order->restaurant->estimated_delivery_time
                    : $order->restaurant->estimated_pickup_time
            ),
        ]);

        $this->logStatusChange($order, 'pending', 'preparing');

        // Notificar al cliente
        if ($order->customer) {
            $order->customer->notify(new OrderStatusChangedNotification($order));
        }

        return back()->with('success', 'Orden aceptada. A preparar!');
    }

    /**
     * Marcar orden como lista (preparing -> ready)
     */
    public function markReady(Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'preparing') {
            return back()->with('error', 'Esta orden no puede marcarse como lista');
        }

        $order->update([
            'status' => 'ready',
            'ready_at' => now(),
        ]);

        $this->logStatusChange($order, 'preparing', 'ready');

        // Notificar al cliente
        if ($order->customer) {
            $order->customer->notify(new OrderStatusChangedNotification($order));
        }

        return back()->with('success', 'Orden marcada como lista');
    }

    /**
     * Asignar motorista a la orden
     */
    public function assignDriver(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'ready' || $order->service_type !== 'delivery') {
            return back()->with('error', 'No se puede asignar motorista a esta orden');
        }

        $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
        ]);

        $driver = Driver::findOrFail($request->driver_id);

        // Verificar que el driver pertenece al mismo restaurante
        if ($driver->restaurant_id !== auth('restaurant')->user()->restaurant_id) {
            return back()->with('error', 'Este motorista no pertenece a tu restaurante');
        }

        $order->update([
            'driver_id' => $driver->id,
            'status' => 'out_for_delivery',
            'assigned_to_driver_at' => now(),
        ]);

        $this->logStatusChange($order, 'ready', 'out_for_delivery', "Asignado a {$driver->name}");

        // Notificar al cliente
        if ($order->customer) {
            $order->customer->notify(new OrderStatusChangedNotification($order));
        }

        return back()->with('success', "Orden asignada a {$driver->name}");
    }

    /**
     * Verifica que la orden pertenece al restaurante del usuario
     */
    protected function authorizeOrder(Order $order): void
    {
        if ($order->restaurant_id !== auth('restaurant')->user()->restaurant_id) {
            abort(403, 'No tienes acceso a esta orden');
        }
    }

    /**
     * Registra el cambio de estado en el historial
     */
    protected function logStatusChange(Order $order, string $from, string $to, ?string $notes = null): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'previous_status' => $from,
            'new_status' => $to,
            'changed_by_type' => 'restaurant_user',
            'changed_by_id' => auth('restaurant')->id(),
            'notes' => $notes,
        ]);
    }
}
