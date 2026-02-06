<?php

namespace App\Notifications;

use App\Models\Driver;
use App\Models\Order;
use App\Services\DriverFCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DriverOrderAssignedNotification extends Notification implements ShouldQueue
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
        return ['driver_fcm'];
    }

    /**
     * Send FCM notification to driver
     */
    public function toDriverFcm(Driver $driver): void
    {
        $fcmService = app(DriverFCMService::class);

        $fcmService->sendToDriver(
            $driver,
            'Nueva orden asignada',
            "Tienes una nueva orden #{$this->order->order_number} pendiente de aceptar.",
            [
                'type' => 'new_order_assigned',
                'order_id' => (string) $this->order->id,
                'order_number' => $this->order->order_number,
                'restaurant_name' => $this->order->restaurant?->name ?? 'Restaurante',
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
            'type' => 'new_order_assigned',
        ];
    }
}
