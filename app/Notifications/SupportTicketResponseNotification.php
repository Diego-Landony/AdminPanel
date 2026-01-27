<?php

namespace App\Notifications;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SupportTicketResponseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
        public SupportMessage $message
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['fcm', 'database'];
    }

    /**
     * Send FCM notification
     */
    public function toFcm(object $notifiable): void
    {
        $fcmService = app(FCMService::class);

        $fcmService->sendToCustomer(
            $notifiable->id,
            'Respuesta a tu ticket',
            "Tienes una respuesta en tu ticket #{$this->ticket->ticket_number}.",
            [
                'type' => 'support_ticket_response',
                'ticket_id' => (string) $this->ticket->id,
                'ticket_number' => $this->ticket->ticket_number,
                'message_id' => (string) $this->message->id,
            ]
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'message_id' => $this->message->id,
            'message' => 'Respuesta a tu ticket',
        ];
    }
}
