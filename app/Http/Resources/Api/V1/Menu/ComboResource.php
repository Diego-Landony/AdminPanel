<?php

namespace App\Http\Resources\Api\V1\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComboResource extends JsonResource
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
            'image_url' => $this->image,
            'prices' => [
                'pickup_capital' => (float) $this->precio_pickup_capital,
                'domicilio_capital' => (float) $this->precio_domicilio_capital,
                'pickup_interior' => (float) $this->precio_pickup_interior,
                'domicilio_interior' => (float) $this->precio_domicilio_interior,
            ],
            'is_available' => $this->isAvailable(),
            'category_id' => $this->category_id,
            'sort_order' => $this->sort_order,

            // Relationships
            'items' => ComboItemResource::collection($this->whenLoaded('items')),
            'badges' => BadgeResource::collection($this->whenLoaded('badges')),
        ];
    }
}
