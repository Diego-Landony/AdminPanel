<?php

namespace App\Http\Resources\Api\V1\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para lista de órdenes del driver
 *
 * Estructura estandarizada igual que DriverOrderDetailResource
 *
 * @mixin \App\Models\Order
 */
class DriverOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $addressSnapshot = $this->delivery_address_snapshot ?? [];

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'assigned_at' => $this->assigned_to_driver_at?->toIso8601String(),
            'restaurant' => $this->when($this->relationLoaded('restaurant') && $this->restaurant, function () {
                return [
                    'id' => $this->restaurant->id,
                    'name' => $this->restaurant->name,
                    'address' => $this->restaurant->address,
                    'phone' => $this->restaurant->phone,
                    'coordinates' => [
                        'latitude' => $this->restaurant->latitude ? (float) $this->restaurant->latitude : null,
                        'longitude' => $this->restaurant->longitude ? (float) $this->restaurant->longitude : null,
                    ],
                ];
            }),
            'customer' => [
                'name' => $this->customer?->full_name ?? $this->customer?->name ?? 'Cliente',
                'phone' => $this->customer?->phone,
            ],
            'delivery_address' => [
                'formatted' => $addressSnapshot['formatted_address'] ?? $addressSnapshot['address'] ?? $addressSnapshot['address_line'] ?? null,
                'latitude' => $addressSnapshot['latitude'] ?? null,
                'longitude' => $addressSnapshot['longitude'] ?? null,
                'reference' => $addressSnapshot['reference'] ?? $addressSnapshot['delivery_notes'] ?? null,
            ],
            'summary' => [
                'items_count' => $this->relationLoaded('items') ? $this->items->count() : 0,
                'subtotal' => (float) $this->subtotal,
                'discount' => (float) $this->discount_total,
                'total' => (float) $this->total,
            ],
            'payment' => [
                'method' => $this->payment_method,
                'status' => $this->payment_status,
                'is_paid' => $this->payment_status === 'paid',
                'amount_to_collect' => $this->payment_status !== 'paid' ? (float) $this->total : 0,
            ],
            'timestamps' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'ready_at' => $this->ready_at?->toIso8601String(),
            ],
            'notes' => $this->notes,
        ];
    }

    /**
     * Get human-readable status label.
     */
    protected function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'preparing' => 'Preparando',
            'ready' => 'Lista para envío',
            'out_for_delivery' => 'En camino',
            'delivered' => 'Entregada',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            'refunded' => 'Reembolsada',
            default => ucfirst($this->status),
        };
    }
}
