<?php

namespace App\Services;

use App\Exceptions\Delivery\AddressOutsideDeliveryZoneException;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerNit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de Gestión de Órdenes
 *
 * Maneja todas las operaciones relacionadas con órdenes:
 * - Creación de órdenes desde carrito
 * - Gestión de estados
 * - Cancelación de órdenes
 * - Funcionalidad de reordenar
 * - Consulta de órdenes activas e historial
 */
class OrderService
{
    public function __construct(
        private OrderNumberGenerator $numberGenerator,
        private PointsService $pointsService,
        private CartService $cartService,
        private DeliveryValidationService $deliveryValidation
    ) {}

    /**
     * Crear orden desde carrito
     *
     * @param  Cart  $cart  Carrito a convertir en orden
     * @param  array  $data  Datos de la orden: restaurant_id, service_type, delivery_address_id (opcional),
     *                       payment_method, nit_id (opcional), notes (opcional)
     * @return Order Orden creada con todas las relaciones cargadas
     *
     * @throws \InvalidArgumentException Si el carrito está vacío o los datos son inválidos
     */
    public function createFromCart(Cart $cart, array $data): Order
    {
        $validation = $this->cartService->validateCart($cart);
        if (! $validation['valid']) {
            throw new \InvalidArgumentException('El carrito no es válido: '.implode(', ', $validation['messages']));
        }

        $serviceType = $data['service_type'] ?? $cart->service_type;

        if ($serviceType === 'pickup' && isset($data['scheduled_pickup_time'])) {
            $scheduledTime = \Carbon\Carbon::parse($data['scheduled_pickup_time']);
            $minimumTime = now()->addMinutes(30);

            if ($scheduledTime->lt($minimumTime)) {
                throw new \InvalidArgumentException('La hora de recogida debe ser al menos 30 minutos desde ahora.');
            }
        }

        if ($serviceType === 'delivery') {
            if (empty($data['delivery_address_id'])) {
                throw new \InvalidArgumentException('La dirección de entrega es requerida para delivery');
            }

            $address = CustomerAddress::where('id', $data['delivery_address_id'])
                ->where('customer_id', $cart->customer_id)
                ->firstOrFail();

            $result = $this->deliveryValidation->validateDeliveryAddress($address);

            if (! $result->isValid) {
                throw new AddressOutsideDeliveryZoneException(
                    $address->latitude,
                    $address->longitude,
                    $result->errorMessage ?? 'La dirección está fuera de las zonas de entrega'
                );
            }

            $data['restaurant_id'] = $result->restaurant->id;

            if ($cart->zone !== $result->zone) {
                $cart->update(['zone' => $result->zone]);
            }
        }

        return DB::transaction(function () use ($cart, $data) {
            $customer = $cart->customer;

            $deliveryAddressSnapshot = null;
            if (isset($data['delivery_address_id'])) {
                $address = CustomerAddress::findOrFail($data['delivery_address_id']);
                if ($address->customer_id !== $customer->id) {
                    throw new \InvalidArgumentException('La dirección no pertenece al cliente');
                }
                $deliveryAddressSnapshot = $address->toArray();
            }

            $nitSnapshot = null;
            if (isset($data['nit_id'])) {
                $nit = CustomerNit::findOrFail($data['nit_id']);
                if ($nit->customer_id !== $customer->id) {
                    throw new \InvalidArgumentException('El NIT no pertenece al cliente');
                }
                $nitSnapshot = $nit->toArray();
            }

            $orderNumber = $this->numberGenerator->generate();

            $summary = $this->cartService->getCartSummary($cart);

            $pointsToEarn = $this->pointsService->calculatePointsToEarn($summary['total']);

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'restaurant_id' => $data['restaurant_id'] ?? $cart->restaurant_id,
                'service_type' => $data['service_type'] ?? $cart->service_type,
                'zone' => $cart->zone,
                'delivery_address_id' => $data['delivery_address_id'] ?? null,
                'delivery_address_snapshot' => $deliveryAddressSnapshot,
                'subtotal' => $summary['subtotal'],
                'discount_total' => $summary['discounts'],
                'delivery_fee' => $summary['delivery_fee'],
                'tax' => 0,
                'total' => $summary['total'],
                'status' => Order::STATUS_PENDING,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'estimated_ready_at' => now()->addMinutes(30),
                'scheduled_pickup_time' => $data['scheduled_pickup_time'] ?? null,
                'points_earned' => $pointsToEarn,
                'nit_id' => $data['nit_id'] ?? null,
                'nit_snapshot' => $nitSnapshot,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($cart->items as $cartItem) {
                $productSnapshot = null;
                if ($cartItem->isProduct()) {
                    $productSnapshot = [
                        'product_id' => $cartItem->product_id,
                        'name' => $cartItem->product->name,
                        'description' => $cartItem->product->description,
                        'category_id' => $cartItem->product->category_id,
                        'category' => $cartItem->product->category?->name,
                        'variant_id' => $cartItem->variant_id,
                        'variant' => $cartItem->variant?->name,
                    ];
                } elseif ($cartItem->isCombo()) {
                    $productSnapshot = [
                        'combo_id' => $cartItem->combo_id,
                        'name' => $cartItem->combo->name,
                        'description' => $cartItem->combo->description,
                        'items' => $cartItem->combo->items?->map(fn ($item) => [
                            'product_id' => $item->product_id,
                            'product_name' => $item->product?->name,
                            'variant_id' => $item->variant_id,
                            'variant_name' => $item->variant?->name,
                            'quantity' => $item->quantity,
                        ])->toArray(),
                    ];
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'variant_id' => $cartItem->variant_id,
                    'combo_id' => $cartItem->combo_id,
                    'product_snapshot' => $productSnapshot,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'options_price' => 0,
                    'subtotal' => $cartItem->subtotal,
                    'selected_options' => $cartItem->selected_options,
                    'combo_selections' => $cartItem->combo_selections,
                    'notes' => $cartItem->notes,
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'previous_status' => null,
                'new_status' => Order::STATUS_PENDING,
                'changed_by_type' => 'customer',
                'changed_by_id' => $customer->id,
                'notes' => 'Orden creada',
                'created_at' => now(),
            ]);

            $cart->update(['status' => 'converted']);

            return $order->load(['items', 'customer', 'restaurant', 'statusHistory']);
        });
    }

    /**
     * Actualizar estado de la orden
     *
     * @param  Order  $order  Orden a actualizar
     * @param  string  $newStatus  Nuevo estado
     * @param  string|null  $notes  Notas sobre el cambio
     * @param  string  $changedByType  Tipo de usuario que realizó el cambio (customer, admin, system)
     * @param  int|null  $changedById  ID del usuario que realizó el cambio
     * @return Order Orden actualizada
     *
     * @throws \InvalidArgumentException Si la transición de estado no es válida
     */
    public function updateStatus(Order $order, string $newStatus, ?string $notes = null, string $changedByType = 'system', ?int $changedById = null): Order
    {
        $previousStatus = $order->status;

        if (! $this->validateStatusTransition($previousStatus, $newStatus)) {
            throw new \InvalidArgumentException("Transición de estado inválida: {$previousStatus} -> {$newStatus}");
        }

        DB::transaction(function () use ($order, $previousStatus, $newStatus, $notes, $changedByType, $changedById) {
            $order->update(['status' => $newStatus]);

            if ($newStatus === Order::STATUS_READY) {
                $order->update(['ready_at' => now()]);
            } elseif ($newStatus === Order::STATUS_DELIVERED) {
                $order->update(['delivered_at' => now()]);
            } elseif ($newStatus === Order::STATUS_COMPLETED) {
                $this->pointsService->creditPoints($order->customer, $order);
                $order->customer->update(['last_purchase_at' => now()]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'changed_by_type' => $changedByType,
                'changed_by_id' => $changedById,
                'notes' => $notes,
                'created_at' => now(),
            ]);
        });

        return $order->fresh();
    }

    /**
     * Cancelar orden
     *
     * @param  Order  $order  Orden a cancelar
     * @param  string  $reason  Razón de la cancelación
     * @return Order Orden cancelada
     *
     * @throws \InvalidArgumentException Si la orden no puede ser cancelada
     */
    public function cancel(Order $order, string $reason): Order
    {
        if (! $order->canBeCancelled()) {
            throw new \InvalidArgumentException('La orden no puede ser cancelada en su estado actual');
        }

        DB::transaction(function () use ($order, $reason) {
            $previousStatus = $order->status;

            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'previous_status' => $previousStatus,
                'new_status' => Order::STATUS_CANCELLED,
                'changed_by_type' => 'customer',
                'changed_by_id' => $order->customer_id,
                'notes' => "Cancelada: {$reason}",
                'created_at' => now(),
            ]);
        });

        return $order->fresh();
    }

    /**
     * Reordenar - crear carrito con mismos items de una orden
     *
     * @param  Order  $order  Orden a reordenar
     * @param  Customer  $customer  Cliente que reordenará
     * @return Cart Carrito creado con los items de la orden
     */
    public function reorder(Order $order, Customer $customer): Cart
    {
        $cart = $this->cartService->getOrCreateCart($customer);

        $this->cartService->clearCart($cart);

        if ($order->restaurant_id) {
            $cart->update([
                'restaurant_id' => $order->restaurant_id,
                'service_type' => $order->service_type,
                'zone' => $order->zone,
            ]);
        }

        foreach ($order->items as $orderItem) {
            if (! $orderItem->product_id && ! $orderItem->combo_id) {
                continue;
            }

            $itemData = [
                'quantity' => $orderItem->quantity,
                'selected_options' => $orderItem->selected_options,
                'combo_selections' => $orderItem->combo_selections,
                'notes' => $orderItem->notes,
            ];

            if ($orderItem->combo_id) {
                $itemData['combo_id'] = $orderItem->combo_id;
            } else {
                $itemData['product_id'] = $orderItem->product_id;
                if ($orderItem->variant_id) {
                    $itemData['variant_id'] = $orderItem->variant_id;
                }
            }

            try {
                $this->cartService->addItem($cart, $itemData);
            } catch (\Exception $e) {
                continue;
            }
        }

        return $cart->fresh(['items.product', 'items.variant', 'items.combo']);
    }

    /**
     * Obtener órdenes activas del cliente
     *
     * @param  Customer  $customer  Cliente
     * @return Collection Colección de órdenes activas
     */
    public function getActiveOrders(Customer $customer): Collection
    {
        return Order::forCustomer($customer->id)
            ->active()
            ->with(['items', 'restaurant', 'statusHistory'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtener historial de órdenes del cliente
     *
     * @param  Customer  $customer  Cliente
     * @param  int  $perPage  Cantidad de resultados por página
     * @return LengthAwarePaginator Órdenes paginadas
     */
    public function getHistory(Customer $customer, int $perPage = 15): LengthAwarePaginator
    {
        return Order::forCustomer($customer->id)
            ->with(['items', 'restaurant'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Validar transición de estado
     *
     * @param  string  $current  Estado actual
     * @param  string  $new  Nuevo estado
     * @return bool True si la transición es válida
     */
    private function validateStatusTransition(string $current, string $new): bool
    {
        $validTransitions = [
            Order::STATUS_PENDING => [Order::STATUS_PREPARING, Order::STATUS_CANCELLED],
            Order::STATUS_PREPARING => [Order::STATUS_READY],
            Order::STATUS_READY => [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_COMPLETED],
            Order::STATUS_OUT_FOR_DELIVERY => [Order::STATUS_DELIVERED],
            Order::STATUS_DELIVERED => [Order::STATUS_COMPLETED],
            Order::STATUS_COMPLETED => [],
            Order::STATUS_CANCELLED => [],
            Order::STATUS_REFUNDED => [],
        ];

        return in_array($new, $validTransitions[$current] ?? []);
    }
}
