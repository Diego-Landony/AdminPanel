<?php

namespace App\Notifications;

use App\Models\CustomerType;
use App\Services\FCMService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerTypeDowngradedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
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
        $channels = ['fcm', 'database'];

        // Solo enviar email si el usuario tiene ofertas habilitadas
        if ($notifiable->email_offers_enabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Send FCM notification
     */
    public function toFcm(object $notifiable): void
    {
        $fcmService = app(FCMService::class);

        $fcmService->sendToCustomer(
            $notifiable->id,
            'Actualización de nivel',
            "Tu nivel ha cambiado a {$this->newType->name}. Acumula puntos en tu próxima compra para subir de nuevo.",
            [
                'type' => 'tier_downgraded',
                'previous_tier' => $this->previousType->name,
                'new_tier' => $this->newType->name,
                'points_needed_to_recover' => (string) $this->previousType->points_required,
            ]
        );
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu nivel de membresía ha cambiado')
            ->greeting("Hola {$notifiable->first_name},")
            ->line("Tu nivel de membresía ha cambiado de **{$this->previousType->name}** a **{$this->newType->name}**.")
            ->line('Esto se debe a que tus puntos acumulados en los últimos 6 meses han disminuido.')
            ->line("Para volver a **{$this->previousType->name}**, necesitas acumular {$this->previousType->points_required} puntos en los próximos 6 meses.")
            ->action('Ver mis puntos', url('/app/points'))
            ->line('¡Visítanos pronto y sigue acumulando puntos!')
            ->salutation('Saludos, Subway Guatemala');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'customer_type_downgraded',
            'previous_type_id' => $this->previousType->id,
            'previous_type_name' => $this->previousType->name,
            'new_type_id' => $this->newType->id,
            'new_type_name' => $this->newType->name,
            'points_needed_to_recover' => $this->previousType->points_required,
            'message' => "Tu nivel ha cambiado de {$this->previousType->name} a {$this->newType->name}",
        ];
    }
}
