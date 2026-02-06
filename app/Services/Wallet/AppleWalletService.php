<?php

namespace App\Services\Wallet;

use App\Models\AppleWalletRegistration;
use App\Models\Customer;
use PKPass\PKPass;
use RuntimeException;

class AppleWalletService
{
    /**
     * Generate a .pkpass binary string for the given customer.
     *
     * @return string Raw .pkpass binary data
     *
     * @throws RuntimeException If certificates are missing or pass generation fails
     */
    public function generatePass(Customer $customer): string
    {
        $this->validateConfiguration();

        $customer->loadMissing('customerType');

        $pkpass = new PKPass;
        $pkpass->setCertificatePath(config('services.apple_wallet.certificate_path'));
        $pkpass->setCertificatePassword(config('services.apple_wallet.certificate_password'));
        $pkpass->setWwdrCertificatePath(config('services.apple_wallet.wwdr_certificate_path'));
        $pkpass->setData($this->buildPassData($customer));
        $this->addImages($pkpass);

        $pass = $pkpass->create(false);

        if ($pass === false) {
            throw new RuntimeException('Failed to generate Apple Wallet pass: '.$pkpass->getError());
        }

        return $pass;
    }

    /**
     * Build the pass.json data structure for a loyalty store card.
     *
     * @return array<string, mixed>
     */
    private function buildPassData(Customer $customer): array
    {
        $tierName = $customer->customerType?->name ?? 'Regular';
        $points = $customer->points ?? 0;
        $serialNumber = 'subway-'.$customer->id;

        $passData = [
            'formatVersion' => 1,
            'passTypeIdentifier' => config('services.apple_wallet.pass_type_identifier'),
            'serialNumber' => $serialNumber,
            'teamIdentifier' => config('services.apple_wallet.team_identifier'),
            'organizationName' => config('services.apple_wallet.organization_name'),
            'description' => 'Tarjeta de Lealtad Subway Guatemala',
            'logoText' => 'Subway Guatemala',
            'foregroundColor' => 'rgb(255, 255, 255)',
            'backgroundColor' => 'rgb(0, 137, 56)',
            'labelColor' => 'rgb(242, 183, 0)',
            'storeCard' => [
                'headerFields' => [
                    [
                        'key' => 'tier',
                        'label' => '',
                        'value' => $tierName,
                        'textAlignment' => 'PKTextAlignmentRight',
                    ],
                ],
                'secondaryFields' => [
                    [
                        'key' => 'name',
                        'label' => '',
                        'value' => $customer->full_name,
                    ],
                    [
                        'key' => 'points',
                        'label' => 'PUNTOS',
                        'value' => $points,
                        'textAlignment' => 'PKTextAlignmentRight',
                    ],
                ],
                'backFields' => [
                    [
                        'key' => 'member',
                        'label' => 'Miembro SubwayCard',
                        'value' => $customer->full_name,
                    ],
                    [
                        'key' => 'email',
                        'label' => 'Email',
                        'value' => $customer->email,
                    ],
                    [
                        'key' => 'card_full',
                        'label' => 'Número de Tarjeta',
                        'value' => $customer->subway_card,
                    ],
                    [
                        'key' => 'website',
                        'label' => 'Sitio Web',
                        'value' => 'https://subwaycardgt.com',
                    ],
                    [
                        'key' => 'terms',
                        'label' => 'Términos y Condiciones',
                        'value' => 'Programa de lealtad de Subway Guatemala. Los puntos se acumulan con cada compra y pueden canjearse por productos. Sujeto a términos y condiciones del programa.',
                    ],
                ],
            ],
            'barcode' => [
                'message' => $customer->subway_card,
                'format' => 'PKBarcodeFormatCode128',
                'messageEncoding' => 'iso-8859-1',
                'altText' => $this->formatCardNumber($customer->subway_card),
            ],
            'barcodes' => [
                [
                    'message' => $customer->subway_card,
                    'format' => 'PKBarcodeFormatCode128',
                    'messageEncoding' => 'iso-8859-1',
                    'altText' => $this->formatCardNumber($customer->subway_card),
                ],
            ],
        ];

        // Agregar soporte para Push Notifications si está configurado
        $webServiceUrl = config('services.apple_wallet.web_service_url');
        if ($webServiceUrl) {
            $passData['webServiceURL'] = $webServiceUrl;
            $passData['authenticationToken'] = $this->generateAuthToken($customer);
        }

        return $passData;
    }

    /**
     * Genera un token de autenticación único para el cliente.
     * Este token se usa para validar las solicitudes de registro de dispositivos.
     */
    private function generateAuthToken(Customer $customer): string
    {
        // Usar un hash determinístico basado en el ID del cliente y una clave secreta
        $secret = config('services.apple_wallet.auth_secret', config('app.key'));

        return hash_hmac('sha256', 'apple-wallet-'.$customer->id, $secret);
    }

    /**
     * Valida el token de autenticación de un request.
     */
    public function validateAuthToken(string $token, string $serialNumber): bool
    {
        $customerId = AppleWalletRegistration::extractCustomerIdFromSerial($serialNumber);
        if (! $customerId) {
            return false;
        }

        $customer = Customer::find($customerId);
        if (! $customer) {
            return false;
        }

        return hash_equals($this->generateAuthToken($customer), $token);
    }

    /**
     * Envía push notifications a todos los dispositivos registrados para un cliente.
     * Esto notifica a Apple Wallet que debe descargar una versión actualizada del pase.
     */
    public function sendPushNotifications(Customer $customer): void
    {
        $serialNumber = 'subway-'.$customer->id;
        $registrations = AppleWalletRegistration::getBySerialNumber($serialNumber);

        if ($registrations->isEmpty()) {
            return;
        }

        foreach ($registrations as $registration) {
            $this->sendPushToDevice($registration->push_token);
        }
    }

    /**
     * Envía una notificación push vacía a un dispositivo específico.
     * Apple Wallet interpreta esto como señal para actualizar el pase.
     */
    private function sendPushToDevice(string $pushToken): void
    {
        $apnsUrl = config('services.apple_wallet.apns_production', true)
            ? 'https://api.push.apple.com'
            : 'https://api.sandbox.push.apple.com';

        $certPath = config('services.apple_wallet.certificate_path');
        $certPassword = config('services.apple_wallet.certificate_password');
        $passTypeId = config('services.apple_wallet.pass_type_identifier');

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "{$apnsUrl}/3/device/{$pushToken}",
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => '{}',
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
                CURLOPT_HTTPHEADER => [
                    'apns-topic: '.$passTypeId,
                    'apns-push-type: background',
                    'apns-priority: 5',
                ],
                CURLOPT_SSLCERT => $certPath,
                CURLOPT_SSLCERTPASSWD => $certPassword,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode !== 200) {
                \Log::warning("APNs push failed for token {$pushToken}: HTTP {$httpCode}, Error: {$error}, Response: {$response}");
            }
        } catch (\Exception $e) {
            \Log::warning("APNs push exception for token {$pushToken}: ".$e->getMessage());
        }
    }

    /**
     * Actualiza el pase de un cliente (para llamar cuando cambian los puntos).
     */
    public function updateCustomerPass(Customer $customer): void
    {
        // Enviar push notifications a todos los dispositivos registrados
        $this->sendPushNotifications($customer);
    }

    /**
     * Add pass images to the PKPass instance.
     */
    private function addImages(PKPass $pkpass): void
    {
        $imagesPath = config('services.apple_wallet.images_path');

        $imageFiles = [
            'icon.png',
            'icon@2x.png',
            'icon@3x.png',
            'strip.png',
            'strip@2x.png',
            'strip@3x.png',
        ];

        foreach ($imageFiles as $file) {
            $filePath = $imagesPath.'/'.$file;
            if (file_exists($filePath)) {
                $pkpass->addFile($filePath);
            }
        }
    }

    /**
     * Validate that all required configuration and files exist.
     *
     * @throws RuntimeException
     */
    private function validateConfiguration(): void
    {
        $certPath = config('services.apple_wallet.certificate_path');
        $wwdrPath = config('services.apple_wallet.wwdr_certificate_path');
        $teamId = config('services.apple_wallet.team_identifier');

        if (! $certPath || ! file_exists($certPath)) {
            throw new RuntimeException('Apple Wallet certificate not found at: '.$certPath);
        }

        if (! $wwdrPath || ! file_exists($wwdrPath)) {
            throw new RuntimeException('Apple WWDR certificate not found at: '.$wwdrPath);
        }

        if (! $teamId) {
            throw new RuntimeException('Apple Wallet Team Identifier not configured. Set APPLE_WALLET_TEAM_ID in .env');
        }
    }

    /**
     * Convert a hex color string to Apple's RGB format.
     */
    private function hexToRgbString(string $hex): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "rgb({$r}, {$g}, {$b})";
    }

    /**
     * Format the card number for display (e.g., "8123 4567 890").
     */
    private function formatCardNumber(string $cardNumber): string
    {
        return trim(chunk_split($cardNumber, 4, ' '));
    }
}
