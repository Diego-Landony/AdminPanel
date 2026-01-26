<?php

namespace App\Http\Resources\Api\V1\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'current_status' => $this->status,
            'current_status_label' => self::getStatusLabel($this->status),
            'can_cancel' => $this->when(method_exists($this->resource, 'canBeCancelled'), fn () => (bool) $this->canBeCancelled()),
            'history' => $this->whenLoaded('statusHistory', fn () => $this->statusHistory->map(fn ($h) => [
                'status' => $h->new_status,
                'status_label' => self::getStatusLabel($h->new_status),
                'previous_status' => $h->previous_status,
                'changed_by' => $h->changed_by_type,
                'notes' => $h->notes,
                'timestamp' => $h->created_at->toIso8601String(),
            ])
            ),
            'timestamps' => [
                'estimated_ready_at' => $this->estimated_ready_at?->toIso8601String(),
                'ready_at' => $this->ready_at?->toIso8601String(),
                'delivered_at' => $this->delivered_at?->toIso8601String(),
                'created_at' => $this->created_at->toIso8601String(),
                'updated_at' => $this->updated_at->toIso8601String(),
            ],
        ];
    }

    /**
     * Get user-friendly label for order status.
     */
    public static function getStatusLabel(?string $status): ?string
    {
        return match ($status) {
            'pending' => 'Orden recibida',
            'preparing' => 'Preparando tu orden',
            'ready' => 'Lista para recoger',
            'out_for_delivery' => 'En camino',
            'delivered' => 'Entregada',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            'refunded' => 'Reembolsada',
            default => $status,
        };
    }
}
