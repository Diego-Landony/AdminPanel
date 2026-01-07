<?php

namespace App\Events;

use App\Models\SupportTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SupportTicket $ticket
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('support.ticket.'.$this->ticket->id),
            new PrivateChannel('support.admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'status' => $this->ticket->status,
            'assigned_to' => $this->ticket->assigned_to,
            'resolved_at' => $this->ticket->resolved_at?->toIso8601String(),
        ];
    }
}
