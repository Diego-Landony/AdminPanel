<?php

namespace App\Http\Resources\Api\V1\Menu;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
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
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'is_active' => $this->is_active,
            'zone' => $this->price_location,
            'delivery_active' => $this->delivery_active,
            'pickup_active' => $this->pickup_active,
            'schedule' => $this->schedule,
            'estimated_delivery_time' => $this->estimated_delivery_time,
            'estimated_pickup_time' => $this->estimated_pickup_time,
            'minimum_order_amount' => $this->minimum_order_amount ? (float) $this->minimum_order_amount : null,
            'has_geofence' => $this->hasGeofence(),
            'is_open_now' => $this->isOpenNow(),
            'today_schedule' => $this->today_schedule,
            'status_text' => $this->status_text,

            // Distance from user location (only when calculated)
            'distance_km' => $this->when(isset($this->distance_km), function () {
                return round($this->distance_km, 2);
            }),
        ];
    }
}
