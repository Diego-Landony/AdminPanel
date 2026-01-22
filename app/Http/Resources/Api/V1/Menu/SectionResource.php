<?php

namespace App\Http\Resources\Api\V1\Menu;

use App\Http\Resources\Concerns\CastsNullableNumbers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    use CastsNullableNumbers;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'is_required' => $this->is_required,
            'allow_multiple' => $this->allow_multiple,
            'min_selections' => $this->min_selections,
            'max_selections' => $this->max_selections,
            'sort_order' => $this->sort_order,

            // Bundle pricing
            'bundle_discount_enabled' => $this->bundle_discount_enabled,
            'bundle_size' => $this->bundle_size,
            'bundle_discount_amount' => $this->toFloatOrNull($this->bundle_discount_amount),

            // Relationships
            'options' => SectionOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
