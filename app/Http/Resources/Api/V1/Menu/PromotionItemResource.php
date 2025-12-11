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
            'special_price_capital' => $this->special_price_capital ? (float) $this->special_price_capital : null,
            'special_price_interior' => $this->special_price_interior ? (float) $this->special_price_interior : null,
            'discount_percentage' => $this->discount_percentage ? (float) $this->discount_percentage : null,
            'service_type' => $this->service_type,
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
}
