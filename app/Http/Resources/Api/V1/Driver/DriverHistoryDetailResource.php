<?php

namespace App\Http\Resources\Api\V1\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para detalle completo de una entrega pasada del driver
 *
 * @mixin \App\Models\Order
 */
class DriverHistoryDetailResource extends JsonResource
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
            'restaurant' => $this->when($this->relationLoaded('restaurant') && $this->restaurant, function () {
                return [
                    'id' => $this->restaurant->id,
                    'name' => $this->restaurant->name,
                    'address' => $this->restaurant->address,
                ];
            }),
            'customer' => [
                'name' => $this->customer?->name ?? 'Cliente',
            ],
            'delivery_address' => [
                'formatted' => $addressSnapshot['formatted_address'] ?? $addressSnapshot['address'] ?? null,
                'reference' => $addressSnapshot['reference'] ?? null,
            ],
            'items' => $this->when($this->relationLoaded('items'), function () {
                return $this->items->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'subtotal' => (float) $item->subtotal,
                        'options' => $item->options ?? [],
                    ];
                });
            }),
            'summary' => [
                'items_count' => $this->items->count(),
                'subtotal' => (float) $this->subtotal,
                'discount' => (float) $this->discount_total,
                'total' => (float) $this->total,
            ],
            'payment' => [
                'method' => $this->payment_method,
                'status' => $this->payment_status,
            ],
            'delivery' => [
                'time_minutes' => $this->calculateDeliveryTimeMinutes(),
                'rating' => $this->delivery_person_rating,
                'comment' => $this->delivery_person_comment,
                'tip' => $this->tip !== null ? (float) $this->tip : null,
            ],
            'timestamps' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'assigned_at' => $this->assigned_to_driver_at?->toIso8601String(),
                'accepted_at' => $this->accepted_by_driver_at?->toIso8601String(),
                'picked_up_at' => $this->picked_up_at?->toIso8601String(),
                'delivered_at' => $this->delivered_at?->toIso8601String(),
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

    /**
     * Calculate delivery time in minutes (from accepted to delivered).
     */
    private function calculateDeliveryTimeMinutes(): ?int
    {
        if ($this->accepted_by_driver_at === null || $this->delivered_at === null) {
            return null;
        }

        return (int) $this->accepted_by_driver_at->diffInMinutes($this->delivered_at);
    }
}
