<?php

namespace App\Http\Resources\Api\V1\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para lista de historial de entregas del driver
 *
 * @mixin \App\Models\Order
 */
class DriverHistoryResource extends JsonResource
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
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'delivery_time_minutes' => $this->calculateDeliveryTimeMinutes(),
            'customer_name' => $this->customer?->name ?? 'Cliente',
            'delivery_address' => [
                'formatted' => $addressSnapshot['formatted_address'] ?? $addressSnapshot['address'] ?? null,
            ],
            'total' => (float) $this->total,
            'payment_method' => $this->payment_method,
            'rating' => $this->delivery_person_rating,
            'tip' => $this->tip !== null ? (float) $this->tip : null,
        ];
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
