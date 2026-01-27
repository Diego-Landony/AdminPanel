<?php

namespace App\Notifications;

use App\Models\CustomerType;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CustomerTypeDowngradeWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CustomerType $currentType,
        public CustomerType $projectedType,
        public int $currentPoints,
        public int $pointsNeeded,
        public int $daysUntilDowngrade = 7
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

        $title = "Mantén tu nivel {$this->currentType->name}";
        $body = "Te faltan {$this->pointsNeeded} puntos para conservar tus beneficios. Tienes {$this->daysUntilDowngrade} días más.";

        $fcmService->sendToCustomer(
            $notifiable->id,
            $title,
            $body,
            [
                'type' => 'tier_downgrade_warning',
                'current_tier' => $this->currentType->name,
                'projected_tier' => $this->projectedType->name,
                'current_points' => (string) $this->currentPoints,
                'points_needed' => (string) $this->pointsNeeded,
                'days_until_downgrade' => (string) $this->daysUntilDowngrade,
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
            'current_tier' => $this->currentType->name,
            'projected_tier' => $this->projectedType->name,
            'current_points' => $this->currentPoints,
            'points_needed' => $this->pointsNeeded,
            'days_until_downgrade' => $this->daysUntilDowngrade,
        ];
    }
}
