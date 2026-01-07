<?php

namespace App\Http\Resources\Api\V1\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportMessageAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
        ];
    }
}
