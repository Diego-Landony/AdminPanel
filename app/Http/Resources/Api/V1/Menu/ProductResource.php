<?php

namespace App\Http\Resources\Api\V1\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'image_url' => $this->getImageUrl(),
            'category_id' => $this->category_id,
            'has_variants' => (bool) $this->has_variants,
            'prices' => [
                'pickup_capital' => (float) $this->precio_pickup_capital,
                'domicilio_capital' => (float) $this->precio_domicilio_capital,
                'pickup_interior' => (float) $this->precio_pickup_interior,
                'domicilio_interior' => (float) $this->precio_domicilio_interior,
            ],
            'is_redeemable' => (bool) $this->is_redeemable,
            'points_cost' => $this->points_cost,
            'sort_order' => $this->sort_order,

            // Relationships
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
            'badges' => BadgeResource::collection($this->whenLoaded('badges')),
        ];
    }
}
