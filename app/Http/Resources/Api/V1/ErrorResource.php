<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'message' => $this->resource['message'] ?? 'Ha ocurrido un error',
            'errors' => $this->when(
                isset($this->resource['errors']),
                $this->resource['errors'] ?? null
            ),
            'code' => $this->when(
                isset($this->resource['code']),
                $this->resource['code'] ?? null
            ),
        ];
    }
}
