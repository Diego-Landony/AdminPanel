<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ScheduledOrderReminderNotification extends Notification implements ShouldQueue
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

        $isDelivery = $this->order->service_type === 'delivery';
        $scheduledTime = $isDelivery
            ? $this->order->scheduled_for
            : $this->order->scheduled_pickup_time;

        $time = $scheduledTime?->format('h:i A') ?? '';
        $restaurant = $this->order->restaurant?->name ?? 'el restaurante';

        $message = $isDelivery
            ? "Tu pedido de {$restaurant} llegará aproximadamente a las {$time}."
            : "Tu pedido está programado para las {$time} en {$restaurant}.";

        $fcmService->sendToCustomer(
            $notifiable->id,
            'Recordatorio de pedido',
            $message,
            [
                'type' => 'scheduled_order_reminder',
                'order_id' => (string) $this->order->id,
                'order_number' => $this->order->order_number,
                'scheduled_time' => $scheduledTime?->toIso8601String(),
                'service_type' => $this->order->service_type,
                'restaurant_id' => (string) $this->order->restaurant_id,
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
        $scheduledTime = $this->order->service_type === 'delivery'
            ? $this->order->scheduled_for
            : $this->order->scheduled_pickup_time;

        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'service_type' => $this->order->service_type,
            'scheduled_time' => $scheduledTime,
            'message' => 'Recordatorio de pedido programado',
        ];
    }
}
