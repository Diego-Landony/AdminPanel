<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function __construct(
        public string $eventType,
        public string $targetModel,
        public ?int $targetId,
        public ?int $userId,
        public string $description,
        public ?array $oldValues = null,
        public ?array $newValues = null,
    ) {}

    /**
     * Convert the event to an array suitable for logging.
     *
     * @return array{user_id: ?int, event_type: string, target_model: string, target_id: ?int, description: string, old_values: ?array, new_values: ?array, user_agent: ?string}
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'event_type' => $this->eventType,
            'target_model' => $this->targetModel,
            'target_id' => $this->targetId,
            'description' => $this->description,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'user_agent' => request()->userAgent(),
        ];
    }
}
