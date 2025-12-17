<?php

namespace App\Http\Resources\Api\V1\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'uses_variants' => (bool) $this->uses_variants,
            'variant_definitions' => $this->variant_definitions,
            'is_combo_category' => (bool) $this->is_combo_category,
            'sort_order' => $this->sort_order,

            // Relationships
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
