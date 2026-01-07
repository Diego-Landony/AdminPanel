<?php

namespace App\Http\Resources\Api\V1\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'is_from_admin' => $this->isFromAdmin(),
            'is_read' => $this->is_read,
            'sender' => $this->getSenderInfo(),
            'attachments' => SupportMessageAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    private function getSenderInfo(): array
    {
        if ($this->isFromAdmin()) {
            return [
                'type' => 'admin',
                'name' => $this->sender?->name ?? 'Soporte',
            ];
        }

        return [
            'type' => 'customer',
            'name' => $this->sender?->full_name ?? 'Cliente',
        ];
    }
}
