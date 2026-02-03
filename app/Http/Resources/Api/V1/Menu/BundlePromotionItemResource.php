<?php

namespace App\Http\Resources\Api\V1\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BundlePromotionItemResource extends JsonResource
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

            // Relationships - formato igual a ComboItemResource
            'product' => $this->when($this->relationLoaded('product') && $this->product !== null, function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'image_url' => $this->product->getImageUrl(),
                ];
            }),
            'variant' => $this->when($this->relationLoaded('variant') && $this->variant !== null, function () {
                return ProductVariantResource::make($this->variant);
            }),
            'options' => $this->when(
                $this->is_choice_group,
                BundlePromotionItemOptionResource::collection($this->whenLoaded('options'))
            ),
        ];
    }
}
