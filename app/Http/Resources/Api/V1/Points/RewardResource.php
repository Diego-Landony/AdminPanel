<?php

namespace App\Http\Resources\Api\V1\Points;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardResource extends JsonResource
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
            'type' => $this->resource instanceof \App\Models\Menu\Product
                ? 'product'
                : ($this->resource instanceof \App\Models\Menu\ProductVariant
                    ? 'product_variant'
                    : 'combo'),
            'name' => $this->name,
            'description' => $this->description ?? null,
            'image' => $this->image ?? null,
            'points_cost' => $this->points_cost,
            'is_active' => $this->is_active ?? true,
            'variant_info' => $this->when(
                $this->resource instanceof \App\Models\Menu\ProductVariant,
                [
                    'sku' => $this->sku ?? null,
                    'size' => $this->size ?? null,
                ]
            ),
        ];
    }
}
