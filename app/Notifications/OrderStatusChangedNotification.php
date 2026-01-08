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
            'title' => '¡Orden Confirmada!',
            'body' => 'Tu orden #{order} ha sido aceptada y está siendo preparada.',
        ],
        'ready' => [
            'title' => '¡Orden Lista!',
            'body' => 'Tu orden #{order} está lista para recoger.',
        ],
        'out_for_delivery' => [
            'title' => 'Orden en Camino',
            'body' => '¡Tu orden #{order} va en camino! Pronto llegará.',
        ],
        'delivered' => [
            'title' => '¡Entregado!',
            'body' => 'Tu orden #{order} ha sido entregada. ¡Buen provecho!',
        ],
        'completed' => [
            'title' => '¡Gracias por tu compra!',
            'body' => 'Tu orden #{order} ha sido completada. ¡Esperamos verte pronto!',
        ],
        'cancelled' => [
            'title' => 'Orden Cancelada',
            'body' => 'Tu orden #{order} ha sido cancelada.',
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

        $fcmService->sendToCustomer(
            $notifiable->id,
            $message['title'],
            str_replace('{order}', $this->order->order_number, $message['body']),
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
