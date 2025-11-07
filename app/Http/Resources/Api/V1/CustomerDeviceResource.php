<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerDeviceResource extends JsonResource
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
            'device_type' => $this->device_type,
            'device_name' => $this->device_name,
            'device_model' => $this->device_model,
            'app_version' => $this->app_version,
            'os_version' => $this->os_version,
            'last_used_at' => $this->last_used_at,
            'is_active' => (bool) $this->is_active,
            'is_current_device' => $this->is_current_device,
            'created_at' => $this->created_at,
        ];
    }
}
