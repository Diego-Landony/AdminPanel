<?php

namespace App\Http\Resources\Api\V1\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComboItemOptionResource extends JsonResource
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
            'product_name' => $this->product?->name,
            'description' => $this->product?->description,
            'variant_name' => $this->variant?->name,
            'image_url' => $this->product?->getImageUrl(),
            'sort_order' => $this->sort_order,
        ];
    }
}
