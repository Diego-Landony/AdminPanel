<?php

namespace App\Services\Driver;

use App\Events\OrderStatusUpdated;
use App\Exceptions\DeliveryLocationException;
use App\Exceptions\DriverHasActiveOrderException;
use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DriverOrderService
{
    public function __construct(
        protected DriverLocationService $locationService
    ) {}

    /**
     * Get pending orders assigned to the driver (status: ready).
     *
     * Returns orders that are ready for pickup and assigned to this driver.
     *
     * @param  Driver  $driver  The driver to get orders for
     * @return Collection<int, Order>
     */
    public function getPendingOrders(Driver $driver): Collection
    {
        return Order::query()
            ->assignedToDriver($driver->id)
            ->pendingDelivery()
            ->with(['customer', 'restaurant', 'items', 'deliveryAddress'])
            ->orderBy('ready_at')
            ->get();
    }

    /**
     * Get the active order for the driver (status: out_for_delivery).
     *
     * @param  Driver  $driver  The driver to get the active order for
     * @return Order|null The active order or null if none
     */
    public function getActiveOrder(Driver $driver): ?Order
    {
        return Order::query()
            ->assignedToDriver($driver->id)
            ->activeDelivery()
            ->with(['customer', 'restaurant', 'items', 'deliveryAddress'])
            ->first();
    }

    /**
     * Accept an assigned order. Transition: ready -> out_for_delivery.
     *
     * Uses database transaction with row locking to prevent race conditions.
     *
     * @param  Driver  $driver  The driver accepting the order
     * @param  Order  $order  The order to accept
     * @return Order The updated order
     *
     * @throws DriverHasActiveOrderException If the driver already has an active order
     * @throws InvalidArgumentException If the order cannot be accepted
     */
    public function acceptOrder(Driver $driver, Order $order): Order
    {
        return DB::transaction(function () use ($driver, $order) {
            // Lock the order row to prevent race conditions
            $order = Order::lockForUpdate()->find($order->id);

            // Check if driver already has an active order (with lock)
            $activeOrder = Order::query()
                ->lockForUpdate()
                ->assignedToDriver($driver->id)
                ->activeDelivery()
                ->first();

            if ($activeOrder) {
                throw new DriverHasActiveOrderException($driver, $activeOrder);
            }

            if ($order->driver_id !== $driver->id) {
                throw new InvalidArgumentException('Esta orden no está asignada a este motorista.');
            }

            if ($order->status !== Order::STATUS_READY) {
                throw new InvalidArgumentException('La orden no puede ser aceptada en su estado actual.');
            }

            $previousStatus = $order->status;

            $order->update([
                'status' => Order::STATUS_OUT_FOR_DELIVERY,
                'picked_up_at' => now(),
                'accepted_by_driver_at' => now(),
            ]);

            $this->recordStatusChange(
                $order,
                $previousStatus,
                Order::STATUS_OUT_FOR_DELIVERY,
                'Orden aceptada por el motorista'
            );

            // Disparar evento para WebSocket
            event(new OrderStatusUpdated($order, $previousStatus));

            // Notificar al cliente que su orden va en camino
            if ($order->customer) {
                $order->customer->notify(new OrderStatusChangedNotification($order, $previousStatus));
            }

            return $order->fresh(['customer', 'restaurant', 'items', 'deliveryAddress']);
        });
    }

    /**
     * Complete a delivery. Transition: out_for_delivery -> delivered -> completed.
     *
     * Uses database transaction to ensure both status updates succeed or fail together.
     *
     * @param  Driver  $driver  The driver completing the delivery
     * @param  Order  $order  The order being delivered
     * @param  float  $latitude  GPS latitude of the driver
     * @param  float  $longitude  GPS longitude of the driver
     * @param  string|null  $notes  Optional delivery notes
     * @return Order The updated order
     *
     * @throws InvalidArgumentException If the order cannot be delivered
     * @throws DeliveryLocationException If the driver is out of range
     */
    public function completeDelivery(
        Driver $driver,
        Order $order,
        float $latitude,
        float $longitude,
        ?string $notes = null
    ): Order {
        if ($order->driver_id !== $driver->id) {
            throw new InvalidArgumentException('Esta orden no está asignada a este motorista.');
        }

        if ($order->status !== Order::STATUS_OUT_FOR_DELIVERY) {
            throw new InvalidArgumentException('La orden no puede ser entregada en su estado actual.');
        }

        $this->validateDeliveryLocation($order, $latitude, $longitude);

        $this->locationService->updateLocation($driver, $latitude, $longitude);

        return DB::transaction(function () use ($order, $notes) {
            $previousStatus = $order->status;
            $now = now();

            // Single update for both delivered and completed status
            $order->update([
                'status' => Order::STATUS_COMPLETED,
                'delivered_at' => $now,
                'completed_at' => $now,
            ]);

            // Record delivered status change
            $this->recordStatusChange(
                $order,
                $previousStatus,
                Order::STATUS_DELIVERED,
                $notes
            );

            // Record completed status change
            $this->recordStatusChange(
                $order,
                Order::STATUS_DELIVERED,
                Order::STATUS_COMPLETED,
                'Orden completada automáticamente tras entrega'
            );

            // Disparar evento para WebSocket (completed)
            event(new OrderStatusUpdated($order, $previousStatus));

            // Notificar al cliente que su orden fue entregada
            if ($order->customer) {
                $order->customer->notify(new OrderStatusChangedNotification($order, $previousStatus));
            }

            return $order->fresh(['customer', 'restaurant', 'items', 'deliveryAddress']);
        });
    }

    /**
     * Validate that the driver is within delivery range of the destination.
     *
     * @param  Order  $order  The order with delivery address
     * @param  float  $latitude  Driver's current latitude
     * @param  float  $longitude  Driver's current longitude
     *
     * @throws DeliveryLocationException If the driver is out of range
     */
    private function validateDeliveryLocation(
        Order $order,
        float $latitude,
        float $longitude
    ): void {
        $deliveryAddress = $order->delivery_address_snapshot;

        if (! isset($deliveryAddress['latitude'], $deliveryAddress['longitude'])) {
            return;
        }

        $destinationLat = (float) $deliveryAddress['latitude'];
        $destinationLon = (float) $deliveryAddress['longitude'];

        $distance = $this->locationService->calculateDistance(
            $latitude,
            $longitude,
            $destinationLat,
            $destinationLon
        );

        $maxDistance = $this->locationService->getMaxDeliveryDistance();

        if ($distance > $maxDistance) {
            throw new DeliveryLocationException($distance, $maxDistance);
        }
    }

    /**
     * Record a status change in the order status history.
     *
     * @param  Order  $order  The order whose status changed
     * @param  string  $previousStatus  The previous status
     * @param  string  $newStatus  The new status
     * @param  string|null  $notes  Optional notes about the change
     */
    private function recordStatusChange(
        Order $order,
        string $previousStatus,
        string $newStatus,
        ?string $notes = null
    ): void {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'changed_by_type' => 'driver',
            'changed_by_id' => $order->driver_id,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}
