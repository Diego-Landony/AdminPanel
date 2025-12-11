<?php

namespace App\Http\Resources\Api\V1\Cart;

use App\Http\Resources\Api\V1\Menu\RestaurantResource;
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

                return [
                    'subtotal' => (float) $summary['subtotal'],
                    'promotions_applied' => $summary['promotions_applied'] ?? [],
                    'total_discount' => (float) ($summary['total_discount'] ?? 0),
                    'delivery_fee' => (float) ($summary['delivery_fee'] ?? 0),
                    'total' => (float) $summary['total'],
                ];
            }),
            'can_checkout' => $this->when(method_exists($this->resource, 'canCheckout'), fn () => (bool) $this->canCheckout()),
            'validation_messages' => $this->when(method_exists($this->resource, 'getValidationMessages'), fn () => $this->getValidationMessages()),
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
        ];
    }
}
