<?php

namespace App\Http\Resources\Api\V1\Driver;

use App\Http\Resources\Api\V1\Driver\Concerns\FormatsDriverOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para lista de historial de entregas del driver
 *
 * Estructura estandarizada igual que DriverOrderResource
 *
 * @mixin \App\Models\Order
 */
class DriverHistoryResource extends JsonResource
{
    use FormatsDriverOrder;

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
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'assigned_at' => $this->assigned_to_driver_at?->toIso8601String(),
            'restaurant' => $this->when(
                $this->relationLoaded('restaurant') && $this->restaurant,
                fn () => $this->getRestaurantData()
            ),
            'customer' => $this->getCustomerData(),
            'delivery_address' => $this->getDeliveryAddressData(),
            'items' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->formatItemsForDriver()
            ),
            'total' => (float) $this->total,
            'payment' => $this->getPaymentData(),
            'delivery_notes' => $this->getDeliveryNotes(),
            'rating' => $this->delivery_person_rating,
            'delivery_time_minutes' => $this->calculateDeliveryTimeMinutes(),
            'timestamps' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'ready_at' => $this->ready_at?->toIso8601String(),
                'accepted_at' => $this->accepted_by_driver_at?->toIso8601String(),
                'delivered_at' => $this->delivered_at?->toIso8601String(),
            ],
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
