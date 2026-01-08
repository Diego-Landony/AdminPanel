<?php

namespace App\Http\Resources\Api\V1\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'category_id' => $this->category_id,

            // 4 precios estandarizados (consistente con productos)
            'special_price_pickup_capital' => $this->special_price_pickup_capital ? (float) $this->special_price_pickup_capital : null,
            'special_price_delivery_capital' => $this->special_price_delivery_capital ? (float) $this->special_price_delivery_capital : null,
            'special_price_pickup_interior' => $this->special_price_pickup_interior ? (float) $this->special_price_pickup_interior : null,
            'special_price_delivery_interior' => $this->special_price_delivery_interior ? (float) $this->special_price_delivery_interior : null,

            'discount_percentage' => $this->discount_percentage ? (float) $this->discount_percentage : null,

            // Precios en formato estandarizado para el API
            'discounted_prices' => $this->getDiscountedPrices(),

            'validity_type' => $this->validity_type,
            'valid_from' => $this->valid_from,
            'valid_until' => $this->valid_until,
            'time_from' => $this->time_from,
            'time_until' => $this->time_until,
            'weekdays' => $this->weekdays,

            // Relationships
            'product' => ProductResource::make($this->whenLoaded('product')),
            'variant' => ProductVariantResource::make($this->whenLoaded('variant')),
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'combo' => ComboResource::make($this->whenLoaded('combo')),
        ];
    }

    /**
     * Get discounted prices in standardized format.
     * Returns 4 prices for consistency across the API.
     *
     * @return array<string, float|null>|null
     */
    private function getDiscountedPrices(): ?array
    {
        // Si hay precios especiales definidos, usarlos directamente
        if ($this->special_price_pickup_capital || $this->special_price_delivery_capital ||
            $this->special_price_pickup_interior || $this->special_price_delivery_interior) {
            return [
                'pickup_capital' => $this->special_price_pickup_capital ? (float) $this->special_price_pickup_capital : null,
                'delivery_capital' => $this->special_price_delivery_capital ? (float) $this->special_price_delivery_capital : null,
                'pickup_interior' => $this->special_price_pickup_interior ? (float) $this->special_price_pickup_interior : null,
                'delivery_interior' => $this->special_price_delivery_interior ? (float) $this->special_price_delivery_interior : null,
            ];
        }

        // Si hay porcentaje de descuento, los precios se calculan en ProductResource/ProductVariantResource
        return null;
    }
}
