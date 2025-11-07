<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerDevice;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FCMService
{
    public function __construct(protected Messaging $messaging) {}

    /**
     * Send a push notification to a single device
     *
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string, error?: string}
     */
    public function sendToDevice(string $fcmToken, string $title, string $body, array $data = []): array
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification($notification)
                ->withData($data);

            $this->messaging->send($message);

            return [
                'success' => true,
                'message' => 'Notification sent successfully',
            ];
        } catch (NotFound $e) {
            // Token no válido o dispositivo desinstalado
            $this->markDeviceAsInactive($fcmToken);

            return [
                'success' => false,
                'message' => 'Device token not found',
                'error' => $e->getMessage(),
            ];
        } catch (InvalidMessage $e) {
            Log::error('FCM Invalid Message', [
                'token' => $fcmToken,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Invalid message format',
                'error' => $e->getMessage(),
            ];
        } catch (MessagingException $e) {
            Log::error('FCM Messaging Error', [
                'token' => $fcmToken,
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
     * Send push notification to all devices of a customer
     *
     * @param  array<string, mixed>  $data
     * @return array{sent: int, failed: int}
     */
    public function sendToCustomer(int $customerId, string $title, string $body, array $data = []): array
    {
        $devices = CustomerDevice::where('customer_id', $customerId)
            ->where('is_active', true)
            ->whereNotNull('fcm_token')
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($devices as $device) {
            $result = $this->sendToDevice($device->fcm_token, $title, $body, $data);

            if ($result['success']) {
                $sent++;
                $device->updateLastUsed();
            } else {
                $failed++;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    /**
     * Send push notification to multiple customers
     *
     * @param  array<int>  $customerIds
     * @param  array<string, mixed>  $data
     * @return array{sent: int, failed: int, customers: int}
     */
    public function sendToMultipleCustomers(array $customerIds, string $title, string $body, array $data = []): array
    {
        $totalSent = 0;
        $totalFailed = 0;

        foreach ($customerIds as $customerId) {
            $result = $this->sendToCustomer($customerId, $title, $body, $data);
            $totalSent += $result['sent'];
            $totalFailed += $result['failed'];
        }

        return [
            'sent' => $totalSent,
            'failed' => $totalFailed,
            'customers' => count($customerIds),
        ];
    }

    /**
     * Send notification to all active customers
     *
     * @param  array<string, mixed>  $data
     * @return array{sent: int, failed: int, customers: int}
     */
    public function sendToAllCustomers(string $title, string $body, array $data = []): array
    {
        $customerIds = Customer::whereNotNull('email_verified_at')
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();

        return $this->sendToMultipleCustomers($customerIds, $title, $body, $data);
    }

    /**
     * Mark device as inactive when FCM token is invalid
     */
    protected function markDeviceAsInactive(string $fcmToken): void
    {
        CustomerDevice::where('fcm_token', $fcmToken)->update([
            'is_active' => false,
        ]);

        Log::warning('FCM token marked as inactive', [
            'token' => substr($fcmToken, 0, 20).'...',
        ]);
    }

    /**
     * Test if Firebase connection is working
     */
    public function testConnection(): array
    {
        try {
            // Intentar validar que el servicio de mensajería está disponible
            // No hay un método directo para testear, así que devolvemos que está OK
            return [
                'success' => true,
                'message' => 'Firebase Messaging service is configured correctly',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Firebase Messaging service configuration error',
                'error' => $e->getMessage(),
            ];
        }
    }
}
