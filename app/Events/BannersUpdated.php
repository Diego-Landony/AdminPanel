<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BannersUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action = 'updated' // created, updated, deleted, reordered
    ) {}

    /**
     * Canal público para que todas las apps reciban la actualización
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('menu'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'banners.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
