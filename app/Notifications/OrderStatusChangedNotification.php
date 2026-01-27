<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private const STATUS_MESSAGES = [
        'preparing' => [
            'title' => 'Pedido confirmado',
            'body' => 'Estamos preparando tu pedido.',
        ],
        'ready' => [
            'title' => 'Pedido listo',
            'body' => 'Tu pedido está listo para recoger.',
        ],
        'out_for_delivery' => [
            'title' => 'Pedido en camino',
            'body' => 'Tu pedido va en camino.',
        ],
        'delivered' => [
            'title' => 'Pedido entregado',
            'body' => '¡Buen provecho!',
        ],
        'completed' => [
            'title' => 'Puntos acumulados',
            'body' => 'Sumaste {points} puntos por esta compra.',
        ],
        'cancelled' => [
            'title' => 'Pedido cancelado',
            'body' => 'Tu pedido ha sido cancelado.',
        ],
    ];

    public function __construct(
        public Order $order,
        public string $previousStatus
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
        $message = $this->getStatusMessage();

        if (! $message) {
            return;
        }

        $fcmService = app(FCMService::class);

        $body = str_replace('{points}', (string) ($this->order->points_earned ?? 0), $message['body']);

        $fcmService->sendToCustomer(
            $notifiable->id,
            $message['title'],
            $body,
            [
                'type' => 'order_status',
                'order_id' => (string) $this->order->id,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status,
                'previous_status' => $this->previousStatus,
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
            'previous_status' => $this->previousStatus,
        ];
    }

    /**
     * Get the message for the current status
     *
     * @return array{title: string, body: string}|null
     */
    private function getStatusMessage(): ?array
    {
        return self::STATUS_MESSAGES[$this->order->status] ?? null;
    }
}
