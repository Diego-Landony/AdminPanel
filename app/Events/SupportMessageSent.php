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
        $this->message->load('attachments');
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('support.ticket.'.$this->message->support_ticket_id),
            new PrivateChannel('support.admin'),
        ];

        // Si el mensaje es del admin, notificar al cliente en su canal privado
        if ($this->message->isFromAdmin()) {
            $this->message->load('ticket');
            $channels[] = new PrivateChannel('customer.'.$this->message->ticket->customer_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => new SupportMessageResource($this->message),
            'ticket_id' => $this->message->support_ticket_id,
        ];
    }
}
