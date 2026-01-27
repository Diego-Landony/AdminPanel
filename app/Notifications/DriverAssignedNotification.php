<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DriverAssignedNotification extends Notification implements ShouldQueue
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
            'Repartidor asignado',
            'Un repartidor ya está en el restaurante por tu pedido.',
            [
                'type' => 'driver_assigned',
                'order_id' => (string) $this->order->id,
                'order_number' => $this->order->order_number,
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
            'message' => 'Repartidor asignado',
        ];
    }
}
