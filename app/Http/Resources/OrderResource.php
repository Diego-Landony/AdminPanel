<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->full_name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ]),
            'restaurant' => $this->whenLoaded('restaurant', fn () => [
                'id' => $this->restaurant->id,
                'name' => $this->restaurant->name,
            ]),
            'driver' => $this->whenLoaded('driver', fn () => $this->driver ? [
                'id' => $this->driver->id,
                'name' => $this->driver->name,
                'phone' => $this->driver->phone,
            ] : null),
            'service_type' => $this->service_type,
            'service_type_label' => $this->getServiceTypeLabel(),
            'zone' => $this->zone,
            'delivery_address_snapshot' => $this->delivery_address_snapshot,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'delivery_fee' => $this->delivery_fee,
            'tax' => $this->tax,
            'total' => $this->total,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'paid_at' => $this->paid_at,
            'estimated_ready_at' => $this->estimated_ready_at,
            'ready_at' => $this->ready_at,
            'delivered_at' => $this->delivered_at,
            'assigned_to_driver_at' => $this->assigned_to_driver_at,
            'picked_up_at' => $this->picked_up_at,
            'points_earned' => $this->points_earned,
            'nit_snapshot' => $this->nit_snapshot,
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'scheduled_for' => $this->scheduled_for,
            'scheduled_pickup_time' => $this->scheduled_pickup_time,
            'delivery_person_rating' => $this->delivery_person_rating,
            'delivery_person_comment' => $this->delivery_person_comment,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product?->name ?? $item->product_name ?? 'Producto',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'customizations' => $item->customizations,
            ])),
            'can_be_assigned_to_driver' => $this->canBeAssignedToDriver(),
            'has_driver' => $this->hasDriver(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Obtiene la etiqueta del estado.
     */
    protected function getStatusLabel(): string
    {
        return match ($this->status) {
            Order::STATUS_PENDING => 'Pendiente',
            Order::STATUS_CONFIRMED => 'Confirmada',
            Order::STATUS_PREPARING => 'En preparacion',
            Order::STATUS_READY => 'Lista',
            Order::STATUS_OUT_FOR_DELIVERY => 'En camino',
            Order::STATUS_DELIVERED => 'Entregada',
            Order::STATUS_COMPLETED => 'Completada',
            Order::STATUS_CANCELLED => 'Cancelada',
            Order::STATUS_REFUNDED => 'Reembolsada',
            default => $this->status,
        };
    }

    /**
     * Obtiene la etiqueta del tipo de servicio.
     */
    protected function getServiceTypeLabel(): string
    {
        return match ($this->service_type) {
            'delivery' => 'Delivery',
            'pickup' => 'Recoger',
            default => $this->service_type,
        };
    }
}
