<?php

namespace App\Events;

use App\Http\Resources\Api\V1\Support\SupportMessageResource;
use App\Models\SupportMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SupportMessage $message
    ) {
        // Cargar relaciones necesarias una sola vez
        $this->message->load(['attachments', 'ticket']);
    }

    public function broadcastOn(): array
    {
        $ticket = $this->message->ticket;

        $channels = [
            // SIEMPRE: Canal especifico del ticket (para la vista del chat)
            new PrivateChannel('support.ticket.'.$this->message->support_ticket_id),
        ];

        // Si el mensaje es del CLIENTE, notificar a admins
        if ($this->message->isFromCustomer()) {
            if ($ticket->assigned_to) {
                // Ticket asignado: solo notificar al admin asignado
                $channels[] = new PrivateChannel('admin.'.$ticket->assigned_to);
            } else {
                // Ticket sin asignar: notificar a todos los admins
                $channels[] = new PrivateChannel('support.admin');
            }
        }

        // Si el mensaje es del ADMIN, notificar al cliente
        if ($this->message->isFromAdmin()) {
            $channels[] = new PrivateChannel('customer.'.$ticket->customer_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $ticket = $this->message->ticket;

        return [
            'message' => new SupportMessageResource($this->message),
            'ticket_id' => $this->message->support_ticket_id,
            'customer_id' => $ticket->customer_id,
            'is_from_admin' => $this->message->isFromAdmin(),
            'is_assigned' => $ticket->assigned_to !== null,
            'assigned_to' => $ticket->assigned_to,
        ];
    }
}
