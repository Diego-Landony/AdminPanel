<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Convert full class name to short name (e.g., App\Models\Menu\Product -> Product)
        $shortType = class_basename($this->favorable_type);

        return [
            'id' => $this->id,
            'favorable_type' => $shortType,
            'favorable_id' => $this->favorable_id,
            'favorable' => $this->when($this->relationLoaded('favorable'), function () {
                return [
                    'id' => $this->favorable->id,
                    'name' => $this->favorable->name,
                    'description' => $this->favorable->description ?? null,
                    'price' => $this->favorable->price ?? null,
                    'image_url' => $this->favorable->image ?? null,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
