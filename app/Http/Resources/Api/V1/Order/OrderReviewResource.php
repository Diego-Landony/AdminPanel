<?php

namespace App\Http\Resources\Api\V1\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderReviewResource extends JsonResource
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
            'overall_rating' => $this->overall_rating,
            'quality_rating' => $this->quality_rating,
            'speed_rating' => $this->speed_rating,
            'service_rating' => $this->service_rating,
            'comment' => $this->comment,
            'customer' => $this->when($this->relationLoaded('customer'), fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
            ]),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
