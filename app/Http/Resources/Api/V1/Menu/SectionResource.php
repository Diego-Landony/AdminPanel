<?php

namespace App\Http\Resources\Api\V1\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'is_required' => $this->is_required,
            'allow_multiple' => $this->allow_multiple,
            'min_selections' => $this->min_selections,
            'max_selections' => $this->max_selections,

            // Relationships
            'options' => SectionOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
