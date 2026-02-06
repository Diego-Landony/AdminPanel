<?php

namespace App\Http\Resources\Api\V1\Driver;

use Illuminate\Http\Request;

class AuthenticatedDriverResource extends DriverResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        // Add token from additional data
        if (isset($this->additional['token'])) {
            $data['token'] = $this->additional['token'];
            $data['token_type'] = 'Bearer';
        }

        return $data;
    }
}
