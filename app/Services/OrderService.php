<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Events\RequestOrderReview;
use App\Exceptions\Delivery\AddressOutsideDeliveryZoneException;
use App\Exceptions\Order\PromotionExpiredException;
use App\Exceptions\Order\RestaurantClosedException;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerNit;
use App\Models\Menu\Promotion;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPromotion;
use App\Models\OrderStatusHistory;
use App\Notifications\OrderCreatedNotification;
use App\Notifications\OrderStatusChangedNotification;
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

        // Obtener tiempos estimados del restaurante
        $restaurant = \App\Models\Restaurant::find($data['restaurant_id'] ?? $cart->restaurant_id);
        if (! $restaurant) {
            throw new \InvalidArgumentException('Restaurante no encontrado');
        }

        // Validar monto mínimo de pedido
        $summary = $this->cartService->getCartSummary($cart);
        $orderTotal = $summary['total'];
        $minimumAmount = (float) ($restaurant->minimum_order_amount ?? 0);

        if ($minimumAmount > 0 && $orderTotal < $minimumAmount) {
            throw new \App\Exceptions\Order\MinimumOrderAmountException(
                $minimumAmount,
                $orderTotal,
                $restaurant->name
            );
        }

        // Validar horario de atención del restaurante
        if (! $restaurant->canAcceptOrdersNow($serviceType)) {
            throw new RestaurantClosedException(
                $restaurant->name,
                $serviceType,
                $restaurant->getClosingTimeToday(),
                $restaurant->getLastOrderTime($serviceType),
                $restaurant->getNextOpenTime()['time'] ?? null
            );
        }

        $estimatedPickupMinutes = $restaurant->estimated_pickup_time ?? 30;
        $estimatedDeliveryMinutes = $restaurant->estimated_delivery_time ?? 45;

        // Determinar tiempo estimado según tipo de servicio
        $estimatedMinutes = $serviceType === 'pickup' ? $estimatedPickupMinutes : $estimatedDeliveryMinutes;

        if ($serviceType === 'pickup') {
            if (isset($data['scheduled_pickup_time'])) {
                // La hora ya fue validada en CreateOrderRequest.
                // Aquí solo verificamos que no haya pasado significativamente (más de 2 minutos)
                // para cubrir casos extremos donde el request tardó mucho.
                $scheduledTime = \Carbon\Carbon::parse($data['scheduled_pickup_time']);
                $absoluteMinimum = now()->subMinutes(2);

                if ($scheduledTime->lt($absoluteMinimum)) {
                    throw new \InvalidArgumentException('La hora de recogida ya no está disponible. Por favor selecciona una nueva hora.');
                }
                // Usar la hora TAL COMO EL USUARIO LA SELECCIONÓ (ya validada en CreateOrderRequest)
            } else {
                // Si no se especificó hora, usar la hora mínima como default
                $data['scheduled_pickup_time'] = now()->addMinutes($estimatedPickupMinutes)->toIso8601String();
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

            // Manejar hora de entrega programada
            if (isset($data['scheduled_delivery_time'])) {
                // La hora ya fue validada en CreateOrderRequest.
                // Aquí solo verificamos que no haya pasado significativamente (más de 2 minutos)
                // para cubrir casos extremos donde el request tardó mucho.
                $scheduledTime = \Carbon\Carbon::parse($data['scheduled_delivery_time']);
                $absoluteMinimum = now()->subMinutes(2);

                if ($scheduledTime->lt($absoluteMinimum)) {
                    throw new \InvalidArgumentException('La hora de entrega ya no está disponible. Por favor selecciona una nueva hora.');
                }
                // Usar la hora TAL COMO EL USUARIO LA SELECCIONÓ (ya validada en CreateOrderRequest)
            } else {
                // Si no se especificó hora, usar la hora mínima como default
                $data['scheduled_delivery_time'] = now()->addMinutes($estimatedDeliveryMinutes)->toIso8601String();
            }
        }

        return DB::transaction(function () use ($cart, $data, $estimatedMinutes) {
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

            $restaurantId = $data['restaurant_id'] ?? $cart->restaurant_id;
            $orderNumber = $this->numberGenerator->generate($restaurantId);

            $summary = $this->cartService->getCartSummary($cart);

            // Validar que las promociones aplicadas sigan vigentes
            $this->validateAppliedPromotions($summary['item_discounts']);

            $pointsToEarn = $this->pointsService->calculatePointsToEarn($summary['total'], $customer);

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'restaurant_id' => $restaurantId,
                'service_type' => $data['service_type'] ?? $cart->service_type,
                'zone' => $cart->zone,
                'delivery_address_id' => $data['delivery_address_id'] ?? null,
                'delivery_address_snapshot' => $deliveryAddressSnapshot,
                'subtotal' => $summary['subtotal'],
                'discount_total' => $summary['discounts'],
                'total' => $summary['total'],
                'status' => Order::STATUS_PENDING,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'estimated_ready_at' => now()->addMinutes($estimatedMinutes),
                'scheduled_pickup_time' => $data['scheduled_pickup_time'] ?? null,
                'scheduled_for' => $data['scheduled_delivery_time'] ?? null,
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

                // Capturar información de promoción aplicada (si existe)
                $promotionId = null;
                $promotionSnapshot = null;

                if (isset($summary['item_discounts'][$cartItem->id]['applied_promotion'])) {
                    $appliedPromo = $summary['item_discounts'][$cartItem->id]['applied_promotion'];
                    $itemDiscount = $summary['item_discounts'][$cartItem->id];

                    $promotionId = $appliedPromo['id'];
                    $promotionSnapshot = [
                        'id' => $appliedPromo['id'],
                        'name' => $appliedPromo['name'],
                        'type' => $appliedPromo['type'],
                        'value' => $appliedPromo['value'],
                        'discount_amount' => $itemDiscount['discount_amount'],
                        'original_price' => $itemDiscount['original_price'],
                        'final_price' => $itemDiscount['final_price'],
                        'is_daily_special' => $itemDiscount['is_daily_special'] ?? false,
                    ];
                }

                // Calcular precio de extras con desglose de bundle
                $optionsBreakdown = $cartItem->getOptionsTotalWithBundle();
                $optionsPrice = $optionsBreakdown['total'];
                $optionsSavings = $optionsBreakdown['savings'];

                // Agregar desglose de extras al product_snapshot
                if ($productSnapshot && ($optionsPrice > 0 || $optionsSavings > 0)) {
                    $productSnapshot['options_breakdown'] = [
                        'items_total' => $optionsPrice + $optionsSavings,
                        'bundle_discount' => $optionsSavings,
                        'final' => $optionsPrice,
                    ];
                }

                // subtotal incluye: (precio_base * cantidad) + (extras * cantidad)
                $itemSubtotal = (float) $cartItem->subtotal + ($optionsPrice * $cartItem->quantity);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'variant_id' => $cartItem->variant_id,
                    'combo_id' => $cartItem->combo_id,
                    'product_snapshot' => $productSnapshot,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'options_price' => $optionsPrice,
                    'subtotal' => $itemSubtotal,
                    'selected_options' => $cartItem->selected_options,
                    'combo_selections' => $cartItem->combo_selections,
                    'notes' => $cartItem->notes,
                    'promotion_id' => $promotionId,
                    'promotion_snapshot' => $promotionSnapshot,
                ]);
            }

            // Consolidar promociones aplicadas a nivel de orden
            $this->createOrderPromotions($order, $summary['item_discounts']);

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

            return $order;
        });

        // Notificar al cliente que su orden fue recibida
        if ($order->customer) {
            $order->customer->notify(new OrderCreatedNotification($order));
        }

        return $order->load(['items', 'customer', 'restaurant', 'statusHistory']);
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

        if (! $this->validateStatusTransition($previousStatus, $newStatus, $order->service_type)) {
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

        event(new OrderStatusUpdated($order, $previousStatus));

        // Solicitar calificación cuando la orden se completa
        if ($newStatus === Order::STATUS_COMPLETED) {
            event(new RequestOrderReview($order));
        }

        if ($order->customer) {
            $order->customer->notify(new OrderStatusChangedNotification($order, $previousStatus));
        }

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
     * @param  string  $serviceType  Tipo de servicio (pickup o delivery)
     * @return bool True si la transición es válida
     */
    private function validateStatusTransition(string $current, string $new, string $serviceType): bool
    {
        $validTransitions = [
            Order::STATUS_PENDING => [Order::STATUS_PREPARING, Order::STATUS_CANCELLED],
            Order::STATUS_PREPARING => [Order::STATUS_READY, Order::STATUS_CANCELLED],
            Order::STATUS_COMPLETED => [],
            Order::STATUS_CANCELLED => [],
            Order::STATUS_REFUNDED => [],
        ];

        // Transiciones específicas según tipo de servicio
        if ($serviceType === 'pickup') {
            $validTransitions[Order::STATUS_READY] = [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED];
        } else { // delivery
            $validTransitions[Order::STATUS_READY] = [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_CANCELLED];
            $validTransitions[Order::STATUS_OUT_FOR_DELIVERY] = [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED];
            $validTransitions[Order::STATUS_DELIVERED] = [Order::STATUS_COMPLETED];
        }

        return in_array($new, $validTransitions[$current] ?? []);
    }

    /**
     * Consolidar promociones aplicadas a nivel de orden
     *
     * Agrupa los descuentos por promoción y crea registros en order_promotions
     * para facilitar reportes y trazabilidad.
     *
     * @param  Order  $order  Orden creada
     * @param  array<int, array{discount_amount: float, applied_promotion?: array}>  $itemDiscounts  Descuentos por item del carrito
     */
    private function createOrderPromotions(Order $order, array $itemDiscounts): void
    {
        $promotionsApplied = collect($itemDiscounts)
            ->filter(fn ($discount) => isset($discount['applied_promotion']) && $discount['discount_amount'] > 0)
            ->groupBy(fn ($discount) => $discount['applied_promotion']['id'])
            ->map(function ($group) {
                $firstPromo = $group->first()['applied_promotion'];

                return [
                    'promotion_id' => $firstPromo['id'],
                    'promotion_type' => $firstPromo['type'],
                    'promotion_name' => $firstPromo['name'],
                    'discount_amount' => $group->sum('discount_amount'),
                    'description' => $this->buildPromotionDescription($firstPromo, $group),
                ];
            });

        foreach ($promotionsApplied as $promoData) {
            OrderPromotion::create([
                'order_id' => $order->id,
                'promotion_id' => $promoData['promotion_id'],
                'promotion_type' => $promoData['promotion_type'],
                'promotion_name' => $promoData['promotion_name'],
                'discount_amount' => $promoData['discount_amount'],
                'description' => $promoData['description'],
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Construir descripción de la promoción aplicada
     *
     * @param  array  $promo  Datos de la promoción
     * @param  \Illuminate\Support\Collection  $group  Items que usaron esta promoción
     */
    private function buildPromotionDescription(array $promo, $group): string
    {
        $itemCount = $group->count();
        $totalDiscount = $group->sum('discount_amount');

        return match ($promo['type']) {
            'two_for_one' => "2x1 aplicado a {$itemCount} item(s)",
            'percentage_discount' => "{$promo['value']} de descuento en {$itemCount} item(s)",
            'bundle_special' => "Bundle especial con {$itemCount} item(s)",
            'daily_special' => "Sub del día aplicado a {$itemCount} item(s)",
            default => "Descuento de Q{$totalDiscount} en {$itemCount} item(s)",
        };
    }

    /**
     * Validar que las promociones aplicadas sigan vigentes
     *
     * Verifica en tiempo real que cada promoción aplicada a los items del carrito
     * siga siendo válida al momento de crear la orden.
     *
     * @param  array<int, array{discount_amount: float, applied_promotion?: array}>  $itemDiscounts  Descuentos por item del carrito
     *
     * @throws PromotionExpiredException Si alguna promoción ya no está vigente
     */
    private function validateAppliedPromotions(array $itemDiscounts): void
    {
        $validatedPromotionIds = [];

        foreach ($itemDiscounts as $discount) {
            if (! isset($discount['applied_promotion']) || $discount['discount_amount'] <= 0) {
                continue;
            }

            $promotionId = $discount['applied_promotion']['id'];

            // Evitar validar la misma promoción múltiples veces
            if (in_array($promotionId, $validatedPromotionIds)) {
                continue;
            }

            $promotion = Promotion::find($promotionId);

            if (! $promotion || ! $promotion->isValidNow()) {
                throw new PromotionExpiredException(
                    $discount['applied_promotion']['name'],
                    $promotionId,
                    $promotion?->valid_until
                );
            }

            $validatedPromotionIds[] = $promotionId;
        }
    }
}
