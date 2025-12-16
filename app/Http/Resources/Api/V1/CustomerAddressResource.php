<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'address_line' => $this->address_line,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'delivery_notes' => $this->delivery_notes,
            'zone' => $this->zone ?? 'capital',
            'is_default' => $this->is_default,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
