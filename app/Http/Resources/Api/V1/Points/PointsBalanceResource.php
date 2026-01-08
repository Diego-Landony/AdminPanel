<?php

namespace App\Http\Resources\Api\V1\Points;

use App\Models\PointsSetting;
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
        $settings = PointsSetting::get();
        $pointsBalance = $this->points ?? 0;

        // Valor de cada punto en Quetzales (inverso de quetzales_per_point)
        // Si quetzales_per_point = 10, entonces 1 punto = Q0.10
        $pointValue = $settings->quetzales_per_point > 0
            ? 1 / $settings->quetzales_per_point
            : 0.10;

        return [
            'points_balance' => $pointsBalance,
            'points_updated_at' => $this->points_updated_at?->toIso8601String(),
            'points_value_in_currency' => round($pointsBalance * $pointValue, 2),
            'conversion_rate' => [
                'quetzales_per_point' => $settings->quetzales_per_point,
                'point_value' => round($pointValue, 2),
            ],
        ];
    }
}
