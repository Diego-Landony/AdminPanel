<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\AssignDriverRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Models\Driver;
use App\Models\Menu\Section;
use App\Models\Menu\SectionOption;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\OrderManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        protected OrderManagementService $orderService
    ) {}

    /**
     * Muestra la lista de ordenes.
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 15);
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $status = $request->get('status');
        $serviceType = $request->get('service_type');
        $restaurantId = $request->get('restaurant_id');

        // Default a hoy si no se especifican fechas
        $today = now()->format('Y-m-d');
        $dateFrom = $request->get('date_from', $today);
        $dateTo = $request->get('date_to', $today);

        $filters = [
            'search' => $search,
            'status' => $status,
            'service_type' => $serviceType,
            'restaurant_id' => $restaurantId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'per_page' => $perPage,
        ];

        $ordersQuery = $this->orderService->getAllOrders($filters);

        // Transformar los datos para la vista
        $orders = $ordersQuery->through(function ($order) {
            return $this->transformOrder($order);
        });

        // Obtener estadisticas de HOY (siempre del dia actual)
        $statistics = $this->orderService->getTodayStatistics($restaurantId ? (int) $restaurantId : null);

        // Lista de restaurantes para filtro
        $restaurants = Restaurant::active()->ordered()->get(['id', 'name']);

        // Configuracion de polling
        $pollingConfig = [
            'polling_interval' => (int) config('app.admin_polling_interval', 30),
            'enabled' => true,
        ];

        return Inertia::render('orders/index', [
            'orders' => $orders,
            'restaurants' => $restaurants,
            'statistics' => $statistics,
            'statuses' => $this->getStatuses(),
            'service_types' => $this->getServiceTypes(),
            'polling_config' => $pollingConfig,
            'filters' => [
                'search' => $search,
                'per_page' => (int) $perPage,
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
                'status' => $status,
                'service_type' => $serviceType,
                'restaurant_id' => $restaurantId ? (int) $restaurantId : null,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * Endpoint de polling para actualizaciones en tiempo real (admin).
     */
    public function poll(Request $request): JsonResponse
    {
        $restaurantId = $request->get('restaurant_id');

        $orders = $this->orderService->getActiveOrdersForPolling(
            $restaurantId ? (int) $restaurantId : null
        );

        return response()->json([
            'orders' => $orders,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Muestra el detalle de una orden.
     */
    public function show(Order $order): Response
    {
        $order->load([
            'customer',
            'restaurant:id,name',
            'driver:id,name,phone',
            'items.product:id,name',
            'statusHistory' => fn ($q) => $q->latest(),
            'review',
        ]);

        // Verificar si se puede cambiar el restaurante
        $notAllowedStatuses = [
            Order::STATUS_READY,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_DELIVERED,
            Order::STATUS_COMPLETED,
        ];
        $canChangeRestaurant = ! in_array($order->status, $notAllowedStatuses);

        // Verificar si se puede cancelar la orden
        $notAllowedForCancel = [
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
        ];
        $canCancel = ! in_array($order->status, $notAllowedForCancel);

        // Lista de restaurantes para cambio
        $restaurants = Restaurant::active()->ordered()->get(['id', 'name']);

        return Inertia::render('orders/show', [
            'order' => $this->transformOrderDetail($order),
            'restaurants' => $restaurants,
            'can_change_restaurant' => $canChangeRestaurant,
            'can_cancel' => $canCancel,
        ]);
    }

    /**
     * Asigna un motorista a una orden.
     */
    public function assignDriver(AssignDriverRequest $request, Order $order): RedirectResponse
    {
        $driver = Driver::findOrFail($request->validated('driver_id'));

        $this->orderService->assignDriver($order, $driver);

        return back()->with('success', "Motorista '{$driver->name}' asignado a la orden exitosamente.");
    }

    /**
     * Actualiza el estado de una orden.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();

        $this->orderService->updateOrderStatus($order, $data['status'], $data['notes'] ?? null);

        $statusLabel = $this->getStatusLabel($data['status']);

        return back()->with('success', "Estado de la orden actualizado a '{$statusLabel}'.");
    }

    /**
     * Cambia el restaurante de una orden.
     * Solo se permite si la orden no ha sido marcada como lista.
     */
    public function changeRestaurant(Request $request, Order $order): RedirectResponse
    {
        // Validar que la orden no esté lista o más avanzada
        $notAllowedStatuses = [
            Order::STATUS_READY,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_DELIVERED,
            Order::STATUS_COMPLETED,
        ];

        if (in_array($order->status, $notAllowedStatuses)) {
            return back()->with('error', 'No se puede cambiar el restaurante de una orden que ya está lista o entregada.');
        }

        $request->validate([
            'restaurant_id' => ['required', 'exists:restaurants,id'],
        ]);

        $newRestaurant = Restaurant::findOrFail($request->restaurant_id);
        $oldRestaurant = $order->restaurant;

        // Cambiar el restaurante
        $order->update([
            'restaurant_id' => $newRestaurant->id,
            'driver_id' => null, // Quitar motorista asignado ya que pertenece al restaurante anterior
            'assigned_to_driver_at' => null,
        ]);

        return back()->with('success', "Restaurante cambiado de '{$oldRestaurant->name}' a '{$newRestaurant->name}'.");
    }

    /**
     * Cancela una orden.
     * Solo el admin puede cancelar ordenes.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        // Validar que la orden no esté ya cancelada, completada o reembolsada
        $notAllowedStatuses = [
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
        ];

        if (in_array($order->status, $notAllowedStatuses)) {
            return back()->with('error', 'Esta orden no puede ser cancelada.');
        }

        $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ]);

        // Si tiene motorista asignado, liberarlo
        if ($order->driver_id) {
            Driver::where('id', $order->driver_id)->update(['is_available' => true]);
        }

        // Cancelar la orden
        $order->update([
            'status' => Order::STATUS_CANCELLED,
            'cancellation_reason' => $request->cancellation_reason,
            'driver_id' => null,
            'assigned_to_driver_at' => null,
        ]);

        // Registrar en historial
        $order->statusHistory()->create([
            'previous_status' => $order->getOriginal('status'),
            'new_status' => Order::STATUS_CANCELLED,
            'changed_by_type' => 'admin',
            'changed_by_id' => auth()->id(),
            'notes' => $request->cancellation_reason,
        ]);

        // Notificar al cliente si existe
        if ($order->customer) {
            $order->customer->notify(new \App\Notifications\OrderStatusChangedNotification($order, $order->getOriginal('status')));
        }

        return back()->with('success', 'Orden cancelada exitosamente.');
    }

    /**
     * Transforma una orden para la lista.
     *
     * @return array<string, mixed>
     */
    protected function transformOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->full_name,
                'email' => $order->customer->email,
            ] : null,
            'restaurant' => $order->restaurant ? [
                'id' => $order->restaurant->id,
                'name' => $order->restaurant->name,
            ] : null,
            'driver' => $order->driver ? [
                'id' => $order->driver->id,
                'name' => $order->driver->name,
            ] : null,
            'service_type' => $order->service_type,
            'service_type_label' => $this->getServiceTypeLabel($order->service_type),
            'status' => $order->status,
            'status_label' => $this->getStatusLabel($order->status),
            'total' => $order->total,
            'items_count' => $order->items->count(),
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'can_be_cancelled' => ! in_array($order->status, [
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
                Order::STATUS_REFUNDED,
            ]),
        ];
    }

    /**
     * Transforma una orden para el detalle.
     *
     * @return array<string, mixed>
     */
    protected function transformOrderDetail(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'full_name' => $order->customer->full_name,
                'email' => $order->customer->email,
                'phone' => $order->customer->phone,
            ] : null,
            'restaurant' => $order->restaurant ? [
                'id' => $order->restaurant->id,
                'name' => $order->restaurant->name,
            ] : null,
            'restaurant_name' => $order->restaurant?->name,
            'driver' => $order->driver ? [
                'id' => $order->driver->id,
                'name' => $order->driver->name,
                'phone' => $order->driver->phone,
            ] : null,
            'driver_id' => $order->driver_id,
            'service_type' => $order->service_type,
            'service_type_label' => $this->getServiceTypeLabel($order->service_type),
            'zone' => $order->zone,
            'delivery_address' => $order->delivery_address_snapshot,
            'subtotal' => $order->subtotal ?? 0,
            'discount' => $order->discount_total ?? 0,
            'total' => $order->total ?? 0,
            'status' => $order->status,
            'status_label' => $this->getStatusLabel($order->status),
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'paid_at' => $order->paid_at,
            'estimated_ready_at' => $order->estimated_ready_at,
            'ready_at' => $order->ready_at,
            'delivered_at' => $order->delivered_at,
            'assigned_to_driver_at' => $order->assigned_to_driver_at,
            'picked_up_at' => $order->picked_up_at,
            'points_earned' => $order->points_earned,
            'nit_snapshot' => $order->nit_snapshot,
            'notes' => $order->notes,
            'cancellation_reason' => $order->cancellation_reason,
            'scheduled_for' => $order->scheduled_for,
            'scheduled_pickup_time' => $order->scheduled_pickup_time,
            'delivery_person_rating' => $order->delivery_person_rating,
            'delivery_person_comment' => $order->delivery_person_comment,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->product_snapshot['name'] ?? $item->product?->name ?? 'Producto',
                'variant' => $item->product_snapshot['variant'] ?? null,
                'category' => $item->product_snapshot['category'] ?? null,
                'quantity' => $item->quantity ?? 1,
                'unit_price' => $item->unit_price ?? 0,
                'options_price' => $item->options_price ?? 0,
                'options_breakdown' => $item->product_snapshot['options_breakdown'] ?? null,
                'total_price' => $item->subtotal ?? 0,
                'options' => $this->resolveSelectedOptions($item->selected_options ?? []),
                'notes' => $item->notes,
            ]),
            'status_history' => $order->statusHistory->map(fn ($history) => [
                'id' => $history->id,
                'status' => $history->new_status,
                'status_label' => $history->new_status ? $this->getStatusLabel($history->new_status) : null,
                'previous_status' => $history->previous_status,
                'notes' => $history->notes,
                'changed_by_type' => $history->changed_by_type,
                'created_at' => $history->created_at,
            ]),
            'review' => $order->review ? [
                'id' => $order->review->id,
                'rating' => $order->review->rating,
                'comment' => $order->review->comment,
                'created_at' => $order->review->created_at,
            ] : null,
            'can_be_assigned_to_driver' => $order->canBeAssignedToDriver(),
            'has_driver' => $order->hasDriver(),
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ];
    }

    /**
     * Obtiene la etiqueta del estado.
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            Order::STATUS_PENDING => 'Recibida',
            Order::STATUS_PREPARING => 'Preparando',
            Order::STATUS_READY => 'Lista',
            Order::STATUS_OUT_FOR_DELIVERY => 'En camino',
            Order::STATUS_DELIVERED => 'Entregada',
            Order::STATUS_COMPLETED => 'Completada',
            Order::STATUS_CANCELLED => 'Cancelada',
            Order::STATUS_REFUNDED => 'Reembolsada',
            default => $status,
        };
    }

    /**
     * Obtiene la etiqueta del tipo de servicio.
     */
    protected function getServiceTypeLabel(string $serviceType): string
    {
        return match ($serviceType) {
            'delivery' => 'Delivery',
            'pickup' => 'Recoger',
            default => $serviceType,
        };
    }

    /**
     * Obtiene la etiqueta del tipo de vehiculo.
     */
    protected function getVehicleTypeLabel(string $vehicleType): string
    {
        return match ($vehicleType) {
            'motorcycle' => 'Motocicleta',
            'bicycle' => 'Bicicleta',
            'car' => 'Auto',
            default => $vehicleType,
        };
    }

    /**
     * Resuelve los nombres de las opciones seleccionadas
     *
     * @param  array<int, array{section_id: int, option_id: int}>  $selectedOptions
     * @return array<int, array{section_name: string, name: string, price: float}>
     */
    protected function resolveSelectedOptions(array $selectedOptions): array
    {
        if (empty($selectedOptions)) {
            return [];
        }

        $sectionIds = collect($selectedOptions)->pluck('section_id')->unique()->values()->all();
        $optionIds = collect($selectedOptions)->pluck('option_id')->unique()->values()->all();

        $sections = Section::whereIn('id', $sectionIds)->pluck('title', 'id');
        $options = SectionOption::whereIn('id', $optionIds)->get()->keyBy('id');

        return collect($selectedOptions)->map(function ($selection) use ($sections, $options) {
            $option = $options->get($selection['option_id']);

            return [
                'section_name' => $sections->get($selection['section_id'], 'Sección'),
                'name' => $option?->name ?? 'Opción',
                'price' => (float) ($option?->price_modifier ?? 0),
            ];
        })->all();
    }

    /**
     * Obtiene los estados disponibles.
     *
     * @return array<array{value: string, label: string}>
     */
    protected function getStatuses(): array
    {
        return [
            ['value' => Order::STATUS_PENDING, 'label' => 'Recibida'],
            ['value' => Order::STATUS_PREPARING, 'label' => 'Preparando'],
            ['value' => Order::STATUS_READY, 'label' => 'Lista'],
            ['value' => Order::STATUS_OUT_FOR_DELIVERY, 'label' => 'En camino'],
            ['value' => Order::STATUS_DELIVERED, 'label' => 'Entregada'],
            ['value' => Order::STATUS_COMPLETED, 'label' => 'Completada'],
            ['value' => Order::STATUS_CANCELLED, 'label' => 'Cancelada'],
            ['value' => Order::STATUS_REFUNDED, 'label' => 'Reembolsada'],
        ];
    }

    /**
     * Obtiene los tipos de servicio disponibles.
     *
     * @return array<array{value: string, label: string}>
     */
    protected function getServiceTypes(): array
    {
        return [
            ['value' => 'delivery', 'label' => 'Delivery'],
            ['value' => 'pickup', 'label' => 'Recoger'],
        ];
    }
}
