<?php

namespace App\Http\Resources\Api\V1\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->whenLoaded('reason', fn () => new SupportReasonResource($this->reason)),
            'status' => $this->status,
            'priority' => $this->priority,
            'unread_count' => $this->unread_count ?? 0,
            'assigned_to' => $this->whenLoaded('assignedUser', fn () => [
                'id' => $this->assignedUser->id,
                'name' => $this->assignedUser->name,
            ]),
            'latest_message' => $this->whenLoaded('latestMessage', fn () => [
                'message' => $this->latestMessage->message,
                'created_at' => $this->latestMessage->created_at->toIso8601String(),
                'is_from_admin' => $this->latestMessage->isFromAdmin(),
            ]),
            'messages' => SupportMessageResource::collection($this->whenLoaded('messages')),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
