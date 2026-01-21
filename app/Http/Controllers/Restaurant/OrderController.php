<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Http\Traits\ResolvesOrderOptions;
use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Notifications\OrderStatusChangedNotification;
use App\Services\PointsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    use ResolvesOrderOptions;

    public function __construct(
        protected PointsService $pointsService
    ) {}

    /**
     * Lista de ordenes del restaurante
     */
    public function index(Request $request): Response
    {
        $restaurantId = auth('restaurant')->user()->restaurant_id;

        $status = $request->get('status', '');
        $serviceType = $request->get('service_type', '');
        $perPage = $request->get('per_page', 20);

        // Filtro de fecha - por defecto mostrar ordenes de hoy
        $date = $request->get('date', now()->format('Y-m-d'));

        $query = Order::where('restaurant_id', $restaurantId)
            ->with(['customer:id,first_name,last_name,phone,subway_card', 'driver:id,name,phone', 'items'])
            ->orderBy('created_at', 'desc');

        // Filtrar por fecha
        if ($date) {
            $startOfDay = \Carbon\Carbon::parse($date)->startOfDay();
            $endOfDay = \Carbon\Carbon::parse($date)->endOfDay();
            $query->whereBetween('created_at', [$startOfDay, $endOfDay]);
        }

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
                'customer' => $order->customer ? [
                    'full_name' => $order->customer->full_name,
                    'phone' => $order->customer->phone,
                    'subway_card' => $order->customer->subway_card,
                ] : null,
                'driver' => $order->driver ? [
                    'name' => $order->driver->name,
                ] : null,
                'status' => $order->status,
                'service_type' => $order->service_type,
                'payment_method' => $order->payment_method,
                'total' => $order->total ?? 0,
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
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'estimated_ready_at' => $order->estimated_ready_at,
            ]);

        // Contadores por estado (filtrados por fecha)
        $baseQuery = Order::where('restaurant_id', $restaurantId);
        if ($date) {
            $startOfDay = \Carbon\Carbon::parse($date)->startOfDay();
            $endOfDay = \Carbon\Carbon::parse($date)->endOfDay();
            $baseQuery->whereBetween('created_at', [$startOfDay, $endOfDay]);
        }

        $statusCounts = [
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'preparing' => (clone $baseQuery)->where('status', 'preparing')->count(),
            'ready' => (clone $baseQuery)->where('status', 'ready')->count(),
            'out_for_delivery' => (clone $baseQuery)->where('status', 'out_for_delivery')->count(),
        ];

        // Obtener motoristas disponibles del restaurante para asignacion inline
        $availableDrivers = Driver::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->where('is_available', true)
            ->select(['id', 'name', 'phone'])
            ->get();

        return Inertia::render('restaurant/orders/index', [
            'orders' => $orders,
            'status_counts' => $statusCounts,
            'filters' => [
                'status' => $status,
                'service_type' => $serviceType,
                'date' => $date,
                'per_page' => (int) $perPage,
            ],
            'available_drivers' => $availableDrivers,
            'config' => [
                'polling_interval' => config('restaurant.polling_interval', 15),
                'auto_print_new_orders' => config('restaurant.auto_print_new_orders', false),
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
            'customer:id,first_name,last_name,email,phone,subway_card',
            'driver:id,name,phone',
            'items.product:id,name',
            'statusHistory',
            'restaurant:id,name',
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
                'restaurant_name' => $order->restaurant?->name,
                'status' => $order->status,
                'service_type' => $order->service_type,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'subtotal' => $order->subtotal ?? 0,
                'discount' => $order->discount_total ?? 0,
                'total' => $order->total ?? 0,
                'notes' => $order->notes,
                'delivery_address' => $order->delivery_address_snapshot,
                'created_at' => $order->created_at,
                'estimated_ready_at' => $order->estimated_ready_at,
                'ready_at' => $order->ready_at,
                'customer' => $order->customer ? [
                    'full_name' => $order->customer->full_name,
                    'email' => $order->customer->email,
                    'phone' => $order->customer->phone,
                    'subway_card' => $order->customer->subway_card,
                ] : null,
                'driver' => $order->driver ? [
                    'id' => $order->driver->id,
                    'name' => $order->driver->name,
                    'phone' => $order->driver->phone,
                ] : null,
                'driver_id' => $order->driver_id,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->product_snapshot['name'] ?? $item->product?->name ?? 'Producto',
                    'variant' => $item->product_snapshot['variant'] ?? null,
                    'category' => $item->product_snapshot['category'] ?? null,
                    'quantity' => $item->quantity ?? 1,
                    'unit_price' => $item->unit_price ?? 0,
                    'options_price' => $item->options_price ?? 0,
                    'total_price' => $item->subtotal ?? 0,
                    'notes' => $item->notes,
                    'options' => $this->resolveSelectedOptions($item->selected_options ?? []),
                ]),
                'status_history' => $order->statusHistory->map(fn ($h) => [
                    'id' => $h->id,
                    'status' => $h->new_status,
                    'notes' => $h->notes,
                    'changed_by_type' => $h->changed_by_type,
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
            $order->customer->notify(new OrderStatusChangedNotification($order, 'pending'));
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
            $order->customer->notify(new OrderStatusChangedNotification($order, 'preparing'));
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

        // Marcar motorista como no disponible
        $driver->update(['is_available' => false]);

        $this->logStatusChange($order, 'ready', 'out_for_delivery', "Asignado a {$driver->name}");

        // Notificar al cliente
        if ($order->customer) {
            $order->customer->notify(new OrderStatusChangedNotification($order, 'ready'));
        }

        return back()->with('success', "Orden asignada a {$driver->name}");
    }

    /**
     * Marcar orden pickup como completada (ready -> completed)
     */
    public function markCompleted(Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'ready' || $order->service_type !== 'pickup') {
            return back()->with('error', 'Esta orden no puede marcarse como completada');
        }

        $order->update([
            'status' => 'completed',
            'delivered_at' => now(),
        ]);

        $this->logStatusChange($order, 'ready', 'completed', 'Cliente recogio su orden');

        // Acreditar puntos al cliente
        if ($order->customer) {
            $order->load('customer');
            $this->pointsService->creditPoints($order->customer, $order);
            $order->customer->update(['last_purchase_at' => now()]);
        }

        // Notificar al cliente
        if ($order->customer) {
            $order->customer->notify(new OrderStatusChangedNotification($order, 'ready'));
        }

        return back()->with('success', 'Orden marcada como completada');
    }

    /**
     * Marcar orden delivery como completada (out_for_delivery -> completed)
     */
    public function markDelivered(Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'out_for_delivery' || $order->service_type !== 'delivery') {
            return back()->with('error', 'Esta orden no puede marcarse como entregada');
        }

        $order->update([
            'status' => 'completed',
            'delivered_at' => now(),
        ]);

        $this->logStatusChange($order, 'out_for_delivery', 'completed', 'Orden entregada al cliente');

        // Marcar motorista como disponible nuevamente
        if ($order->driver_id) {
            Driver::where('id', $order->driver_id)->update(['is_available' => true]);
        }

        // Acreditar puntos al cliente
        if ($order->customer) {
            $order->load('customer');
            $this->pointsService->creditPoints($order->customer, $order);
            $order->customer->update(['last_purchase_at' => now()]);
        }

        // Notificar al cliente
        if ($order->customer) {
            $order->customer->notify(new OrderStatusChangedNotification($order, 'out_for_delivery'));
        }

        return back()->with('success', 'Orden marcada como entregada');
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
            'changed_by_type' => 'restaurant',
            'changed_by_id' => auth('restaurant')->id(),
            'notes' => $notes,
        ]);
    }
}
