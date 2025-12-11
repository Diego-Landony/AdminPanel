<?php

namespace App\Http\Resources\Api\V1\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => null, // Promotions don't have images
            'type' => $this->type,
            'prices' => [
                'bundle_capital' => $this->special_bundle_price_capital ? (float) $this->special_bundle_price_capital : null,
                'bundle_interior' => $this->special_bundle_price_interior ? (float) $this->special_bundle_price_interior : null,
            ],
            'valid_from' => $this->valid_from,
            'valid_until' => $this->valid_until,
            'time_from' => $this->time_from,
            'time_until' => $this->time_until,
            'weekdays' => $this->weekdays,
            'sort_order' => $this->sort_order,

            // Relationships
            'items' => PromotionItemResource::collection($this->whenLoaded('items')),
            'bundle_items' => BundlePromotionItemResource::collection($this->whenLoaded('bundleItems')),
        ];
    }
}
