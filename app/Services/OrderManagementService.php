<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OrderManagementService
{
    /**
     * Asignar un motorista a una orden.
     */
    public function assignDriver(Order $order, Driver $driver): Order
    {
        $order->assignDriver($driver);

        $this->recordStatusChange(
            $order,
            $order->status,
            "Motorista asignado: {$driver->name}"
        );

        return $order->fresh(['driver']);
    }

    /**
     * Obtener ordenes para un restaurante con filtros.
     *
     * @param  array{
     *     status?: string|null,
     *     service_type?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     search?: string|null,
     *     per_page?: int
     * }  $filters
     */
    public function getOrdersForRestaurant(int $restaurantId, array $filters = []): LengthAwarePaginator
    {
        $query = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->with(['customer', 'driver', 'items']);

        return $this->applyFiltersAndPaginate($query, $filters);
    }

    /**
     * Obtener todas las ordenes con filtros (para admin).
     *
     * @param  array{
     *     restaurant_id?: int|null,
     *     status?: string|null,
     *     service_type?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     search?: string|null,
     *     per_page?: int
     * }  $filters
     */
    public function getAllOrders(array $filters = []): LengthAwarePaginator
    {
        $query = Order::query()
            ->with(['customer', 'restaurant', 'driver', 'items']);

        if (! empty($filters['restaurant_id'])) {
            $query->where('restaurant_id', $filters['restaurant_id']);
        }

        return $this->applyFiltersAndPaginate($query, $filters);
    }

    /**
     * Actualizar el estado de una orden.
     */
    public function updateOrderStatus(Order $order, string $status, ?string $notes = null): Order
    {
        $previousStatus = $order->status;

        $updateData = ['status' => $status];

        // Actualizar campos de timestamp segun el nuevo estado
        if ($status === Order::STATUS_READY) {
            $updateData['ready_at'] = now();
        } elseif ($status === Order::STATUS_DELIVERED) {
            $updateData['delivered_at'] = now();
        }

        $order->update($updateData);

        $this->recordStatusChange($order, $status, $notes, $previousStatus);

        return $order->fresh();
    }

    /**
     * Aplicar filtros y paginar la consulta.
     *
     * @param  array{
     *     status?: string|null,
     *     service_type?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     search?: string|null,
     *     per_page?: int
     * }  $filters
     */
    protected function applyFiltersAndPaginate(Builder $query, array $filters): LengthAwarePaginator
    {
        // Filtro por estado
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por tipo de servicio
        if (! empty($filters['service_type'])) {
            $query->where('service_type', $filters['service_type']);
        }

        // Filtro por fecha desde
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        // Filtro por fecha hasta
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Busqueda por numero de orden o cliente
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                        $customerQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Registrar un cambio de estado en el historial.
     */
    protected function recordStatusChange(
        Order $order,
        string $newStatus,
        ?string $notes = null,
        ?string $previousStatus = null
    ): void {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $newStatus,
            'previous_status' => $previousStatus,
            'notes' => $notes,
            'changed_at' => now(),
        ]);
    }

    /**
     * Obtener estadisticas de ordenes para un restaurante.
     *
     * @return array{
     *     total: int,
     *     pending: int,
     *     preparing: int,
     *     ready: int,
     *     out_for_delivery: int,
     *     completed: int,
     *     completed_today: int,
     *     cancelled: int
     * }
     */
    public function getOrderStatistics(?int $restaurantId = null): array
    {
        $query = Order::query();

        if ($restaurantId !== null) {
            $query->where('restaurant_id', $restaurantId);
        }

        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', Order::STATUS_PENDING)->count(),
            'preparing' => (clone $query)->where('status', Order::STATUS_PREPARING)->count(),
            'ready' => (clone $query)->where('status', Order::STATUS_READY)->count(),
            'out_for_delivery' => (clone $query)->where('status', Order::STATUS_OUT_FOR_DELIVERY)->count(),
            'completed' => (clone $query)->where('status', Order::STATUS_COMPLETED)->count(),
            'completed_today' => (clone $query)
                ->where('status', Order::STATUS_COMPLETED)
                ->whereDate('updated_at', today())
                ->count(),
            'cancelled' => (clone $query)->where('status', Order::STATUS_CANCELLED)->count(),
        ];
    }
}
