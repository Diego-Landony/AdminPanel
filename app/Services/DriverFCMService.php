<?php

namespace App\Services;

use App\Models\Driver;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class DriverFCMService
{
    public function __construct(protected Messaging $messaging) {}

    /**
     * Send a push notification to a driver
     *
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string, error?: string}
     */
    public function sendToDriver(Driver $driver, string $title, string $body, array $data = []): array
    {
        if (! $driver->fcm_token) {
            return [
                'success' => false,
                'message' => 'Driver has no FCM token registered',
            ];
        }

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $driver->fcm_token)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);

            return [
                'success' => true,
                'message' => 'Notification sent successfully',
            ];
        } catch (NotFound $e) {
            // Token no válido o app desinstalada
            $this->invalidateToken($driver);

            return [
                'success' => false,
                'message' => 'Device token not found',
                'error' => $e->getMessage(),
            ];
        } catch (InvalidMessage $e) {
            Log::error('Driver FCM Invalid Message', [
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Invalid message format',
                'error' => $e->getMessage(),
            ];
        } catch (MessagingException $e) {
            Log::error('Driver FCM Messaging Error', [
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Invalidate FCM token when it's no longer valid
     */
    protected function invalidateToken(Driver $driver): void
    {
        $driver->update(['fcm_token' => null]);

        Log::warning('Driver FCM token invalidated', [
            'driver_id' => $driver->id,
        ]);
    }
}
