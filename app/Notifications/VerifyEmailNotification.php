<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmailBase
{
    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl($notifiable): string
    {
        // Generate the signed API URL
        $apiUrl = URL::temporarySignedRoute(
            'api.v1.auth.verify-email',
            Carbon::now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        // Wrap in deep link for mobile app
        $scheme = config('app.mobile_scheme', 'subwayapp');
        $encodedUrl = urlencode($apiUrl);

        return "{$scheme}://verify-email?url={$encodedUrl}";
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        $appName = config('app.mobile_name');

        // Get customer name
        $customerName = $notifiable->first_name ?? 'Usuario';

        return (new MailMessage)
            ->subject(__('emails.verify_subject', ['appName' => $appName]))
            ->view('emails.mobile.verify-email', [
                'customerName' => $customerName,
                'actionUrl' => $verificationUrl,
                'expireMinutes' => 60,
            ]);
    }
}
