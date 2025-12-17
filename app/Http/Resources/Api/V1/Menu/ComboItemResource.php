<?php

namespace App\Http\Resources\Api\V1\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComboItemResource extends JsonResource
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
            'is_choice_group' => $this->is_choice_group,
            'choice_label' => $this->choice_label,
            'quantity' => $this->quantity,
            'sort_order' => $this->sort_order,

            // Relationships
            'product' => ProductResource::make($this->whenLoaded('product')),
            'variant' => ProductVariantResource::make($this->whenLoaded('variant')),
            'options' => $this->when(
                $this->is_choice_group,
                ComboItemOptionResource::collection($this->whenLoaded('options'))
            ),
        ];
    }
}
