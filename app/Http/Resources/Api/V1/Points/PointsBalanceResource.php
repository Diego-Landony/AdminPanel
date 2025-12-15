<?php

namespace App\Http\Resources\Api\V1\Points;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointsBalanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'points_balance' => $this->points ?? 0,
            'points_updated_at' => $this->points_updated_at,
            'points_value_in_currency' => ($this->points ?? 0) * 0.10,
            'conversion_rate' => [
                'points_per_quetzal_spent' => 10,
                'points_value' => 0.10,
            ],
        ];
    }
}
