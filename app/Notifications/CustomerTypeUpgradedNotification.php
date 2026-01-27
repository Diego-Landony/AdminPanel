<?php

namespace App\Notifications;

use App\Models\CustomerType;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CustomerTypeUpgradedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CustomerType $previousType,
        public CustomerType $newType
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

        $multiplier = number_format($this->newType->multiplier, 0);

        $fcmService->sendToCustomer(
            $notifiable->id,
            'Subiste de nivel',
            "Ahora eres {$this->newType->name}. Tus puntos se multiplican x{$multiplier} en cada compra.",
            [
                'type' => 'tier_upgraded',
                'previous_tier' => $this->previousType->name,
                'new_tier' => $this->newType->name,
                'multiplier' => $multiplier,
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
            'previous_tier' => $this->previousType->name,
            'new_tier' => $this->newType->name,
            'multiplier' => $this->newType->multiplier,
        ];
    }
}
