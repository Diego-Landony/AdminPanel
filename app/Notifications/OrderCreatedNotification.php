<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['fcm'];
    }

    /**
     * Send FCM notification
     */
    public function toFcm(object $notifiable): void
    {
        $fcmService = app(FCMService::class);

        $fcmService->sendToCustomer(
            $notifiable->id,
            '¡Orden Recibida!',
            "Tu orden #{$this->order->order_number} ha sido recibida. Te notificaremos cuando esté siendo preparada.",
            [
                'type' => 'order_created',
                'order_id' => (string) $this->order->id,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status,
                'total' => (string) $this->order->total,
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
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status,
            'message' => "Orden #{$this->order->order_number} recibida",
        ];
    }
}
