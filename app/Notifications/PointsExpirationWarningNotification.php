<?php

namespace App\Notifications;

use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PointsExpirationWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $pointsToExpire,
        public int $totalPoints,
        public int $daysUntilExpiration
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

        $daysText = $this->daysUntilExpiration === 1 ? 'mañana' : "en {$this->daysUntilExpiration} días";
        $urgency = $this->daysUntilExpiration === 1 ? '⚠️ ' : '';

        $title = $urgency.'¡Tus puntos están por vencer!';
        $body = "Tienes {$this->pointsToExpire} puntos que vencerán {$daysText}. ¡Haz una compra para mantenerlos activos!";

        $fcmService->sendToCustomer(
            $notifiable->id,
            $title,
            $body,
            [
                'type' => 'points_expiration_warning',
                'points_to_expire' => (string) $this->pointsToExpire,
                'total_points' => (string) $this->totalPoints,
                'days_until_expiration' => (string) $this->daysUntilExpiration,
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
            'points_to_expire' => $this->pointsToExpire,
            'total_points' => $this->totalPoints,
            'days_until_expiration' => $this->daysUntilExpiration,
        ];
    }
}
