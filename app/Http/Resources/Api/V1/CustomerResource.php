<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CustomerType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'avatar' => $this->avatar,
            'oauth_provider' => $this->oauth_provider,
            'subway_card' => $this->subway_card,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'gender' => $this->gender,
            'phone' => $this->phone,
            'email_offers_enabled' => (bool) $this->email_offers_enabled,
            'last_login_at' => $this->last_login_at,
            'last_activity_at' => $this->last_activity_at,
            'last_purchase_at' => $this->last_purchase_at,
            'points' => $this->points ?? 0,
            'points_updated_at' => $this->points_updated_at,
            'status' => $this->status,
            'is_online' => $this->is_online,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Authentication state (for Flutter to know login options)
            'has_password' => $this->password !== null,
            'has_google_linked' => $this->google_id !== null,
            'has_apple_linked' => $this->apple_id !== null,

            // Relationships
            'customer_type' => CustomerTypeResource::make($this->whenLoaded('customerType')),
            'next_tier_info' => CustomerType::getNextTierInfo($this->resource),
            'addresses' => CustomerAddressResource::collection($this->whenLoaded('addresses')),
            'nits' => CustomerNitResource::collection($this->whenLoaded('nits')),
            'devices' => CustomerDeviceResource::collection($this->whenLoaded('activeDevices')),

            // Counts
            'addresses_count' => $this->whenCounted('addresses'),
            'nits_count' => $this->whenCounted('nits'),
            'devices_count' => $this->whenCounted('devices'),
        ];
    }
}
