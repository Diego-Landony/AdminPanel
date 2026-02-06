<?php

namespace App\Http\Resources\Api\V1\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para estadísticas consolidadas del driver
 *
 * Expected input array structure:
 * [
 *     'period' => 'month',
 *     'period_label' => 'Febrero 2026',
 *     'deliveries' => ['total' => int, 'completed' => int, 'cancelled' => int, 'completion_rate' => float],
 *     'timing' => ['average_minutes' => int|null, 'fastest_minutes' => int|null, 'slowest_minutes' => int|null],
 *     'rating' => ['average' => float|null, 'total_reviews' => int, 'distribution' => array],
 *     'earnings' => ['tips_total' => string, 'tips_average' => string],
 * ]
 */
class DriverStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'period' => $this->resource['period'],
            'period_label' => $this->resource['period_label'],
            'deliveries' => [
                'total' => $this->resource['deliveries']['total'],
                'completed' => $this->resource['deliveries']['completed'],
                'cancelled' => $this->resource['deliveries']['cancelled'],
                'completion_rate' => $this->resource['deliveries']['completion_rate'],
            ],
            'timing' => [
                'average_minutes' => $this->resource['timing']['average_minutes'],
                'fastest_minutes' => $this->resource['timing']['fastest_minutes'],
                'slowest_minutes' => $this->resource['timing']['slowest_minutes'],
            ],
            'rating' => [
                'average' => $this->resource['rating']['average'],
                'total_reviews' => $this->resource['rating']['total_reviews'],
                'distribution' => $this->resource['rating']['distribution'],
            ],
            'earnings' => [
                'tips_total' => $this->resource['earnings']['tips_total'],
                'tips_average' => $this->resource['earnings']['tips_average'],
            ],
        ];
    }
}
