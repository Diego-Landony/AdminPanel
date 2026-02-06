<?php

namespace App\Http\Resources\Api\V1\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'restaurant' => $this->when($this->relationLoaded('restaurant') && $this->restaurant, function () {
                return [
                    'id' => $this->restaurant->id,
                    'name' => $this->restaurant->name,
                    'address' => $this->restaurant->address,
                    'phone' => $this->restaurant->phone,
                ];
            }),
            'is_active' => $this->is_active,
            'is_available' => ! $this->hasActiveOrder(),
            'rating' => $this->rating,
            'total_deliveries' => $this->total_deliveries,
            'created_at' => $this->created_at?->toIso8601String(),
        ];

        // Include location only if coordinates exist
        if ($this->current_latitude !== null && $this->current_longitude !== null) {
            $data['location'] = [
                'latitude' => (float) $this->current_latitude,
                'longitude' => (float) $this->current_longitude,
                'updated_at' => $this->last_location_update?->toIso8601String(),
            ];
        }

        return $data;
    }
}
