<?php

namespace App\Http\Resources\Api\V1\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BadgeResource extends JsonResource
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
            'badge_type' => [
                'name' => $this->badgeType->name,
                'color' => $this->badgeType->color,
            ],
            'validity_type' => $this->validity_type,
            'is_valid_now' => $this->isValidNow(),
        ];
    }
}
