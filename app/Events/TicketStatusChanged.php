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
            // Canal del cliente para que la app reciba el cambio de estado
            new PrivateChannel('customer.'.$this->ticket->customer_id),
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
            'ticket_number' => $this->ticket->ticket_number,
            'status' => $this->ticket->status,
            'contact_preference' => $this->ticket->contact_preference,
            'has_admin_message' => $this->ticket->hasAdminMessage(),
            'can_send_messages' => $this->ticket->customerCanSendMessages(),
            'assigned_to' => $this->ticket->assigned_to,
            'resolved_at' => $this->ticket->resolved_at?->toIso8601String(),
        ];
    }
}
