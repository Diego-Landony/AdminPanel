<?php

namespace App\Http\Resources\Api\V1\Menu;

use App\Http\Resources\Concerns\CastsNullableNumbers;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'type' => $this->type,
            'prices' => $this->when(
                $this->type === 'bundle_special',
                fn () => $this->buildBundlePrices($this->resource)
            ),
            'valid_from' => $this->valid_from,
            'valid_until' => $this->valid_until,
            'time_from' => $this->time_from,
            'time_until' => $this->time_until,
            'weekdays' => $this->weekdays,
            'sort_order' => $this->sort_order,

            // Badge para mostrar en UI (ej: "2x1", "15% OFF")
            'badge' => $this->when($this->badgeType, fn () => [
                'name' => $this->badgeType->name,
                'color' => $this->badgeType->color,
                'text_color' => $this->badgeType->text_color,
            ]),

            // Relationships
            'items' => PromotionItemResource::collection($this->whenLoaded('items')),
            'bundle_items' => BundlePromotionItemResource::collection($this->whenLoaded('bundleItems')),
        ];
    }
}
