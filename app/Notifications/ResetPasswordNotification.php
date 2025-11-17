<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        parent::__construct($token);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $appName = config('app.mobile_name');
        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        // Get customer name
        $customerName = $notifiable->first_name ?? 'Usuario';

        return (new MailMessage)
            ->subject(__('emails.reset_subject', ['appName' => $appName]))
            ->view('emails.mobile.reset-password', [
                'customerName' => $customerName,
                'actionUrl' => $url,
                'expireMinutes' => $expireMinutes,
            ]);
    }
}
