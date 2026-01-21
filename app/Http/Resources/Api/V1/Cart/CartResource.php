<?php

namespace App\Http\Resources\Api\V1\Cart;

use App\Http\Resources\Api\V1\Menu\RestaurantResource;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'restaurant' => RestaurantResource::make($this->whenLoaded('restaurant')),
            'service_type' => $this->service_type,
            'zone' => $this->zone,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'summary' => $this->when(method_exists($this->resource, 'calculateSummary'), function () {
                $summary = $this->calculateSummary();
                $total = (float) $summary['total'];

                // Calcular puntos a ganar usando el servicio de puntos
                $pointsToEarn = $this->calculatePointsToEarn($total);

                // Obtener tiempos estimados del restaurante
                $estimatedTimes = $this->getEstimatedTimes();

                return [
                    'subtotal' => (float) $summary['subtotal'],
                    'promotions_applied' => $summary['promotions_applied'] ?? [],
                    'discount_total' => (float) ($summary['discount_total'] ?? $summary['total_discount'] ?? 0),
                    'total' => $total,
                    'points_to_earn' => $pointsToEarn,
                    'estimated_pickup_time' => $estimatedTimes['pickup'],
                    'estimated_delivery_time' => $estimatedTimes['delivery'],
                ];
            }),
            'can_checkout' => $this->when(method_exists($this->resource, 'canCheckout'), fn () => (bool) $this->canCheckout()),
            'validation_messages' => $this->when(method_exists($this->resource, 'getValidationMessages'), fn () => $this->getValidationMessages()),
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Calcula los puntos que ganaría el cliente con esta compra.
     */
    protected function calculatePointsToEarn(float $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        $customer = $this->relationLoaded('customer') ? $this->customer : null;

        return app(PointsService::class)->calculatePointsToEarn($total, $customer);
    }

    /**
     * Obtiene los tiempos estimados del restaurante asociado.
     *
     * @return array{pickup: int|null, delivery: int|null}
     */
    protected function getEstimatedTimes(): array
    {
        $restaurant = $this->relationLoaded('restaurant') ? $this->restaurant : null;

        if (! $restaurant) {
            return [
                'pickup' => null,
                'delivery' => null,
            ];
        }

        return [
            'pickup' => $restaurant->estimated_pickup_time,
            'delivery' => $restaurant->estimated_delivery_time,
        ];
    }
}
