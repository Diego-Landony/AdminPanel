<?php

namespace App\Services\Wallet;

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

        return [
            'formatVersion' => 1,
            'passTypeIdentifier' => config('services.apple_wallet.pass_type_identifier'),
            'serialNumber' => 'subway-'.$customer->id,
            'teamIdentifier' => config('services.apple_wallet.team_identifier'),
            'organizationName' => config('services.apple_wallet.organization_name'),
            'description' => 'Tarjeta de Lealtad Subway Guatemala',
            'logoText' => 'SUBWAY',
            'foregroundColor' => 'rgb(255, 255, 255)',
            'backgroundColor' => 'rgb(0, 130, 68)',
            'labelColor' => 'rgb(255, 212, 0)',
            'storeCard' => [
                'headerFields' => [
                    [
                        'key' => 'tier',
                        'label' => 'NIVEL',
                        'value' => $tierName,
                        'textAlignment' => 'PKTextAlignmentRight',
                    ],
                ],
                'primaryFields' => [
                    [
                        'key' => 'points',
                        'label' => '',
                        'value' => ($customer->points ?? 0).' PUNTOS',
                    ],
                ],
                'secondaryFields' => [
                    [
                        'key' => 'name',
                        'label' => '',
                        'value' => $customer->full_name,
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
    }

    /**
     * Add pass images to the PKPass instance.
     * Note: Only icon and logo are included for a clean design without strip.
     */
    private function addImages(PKPass $pkpass): void
    {
        $imagesPath = config('services.apple_wallet.images_path');

        $imageFiles = [
            'icon.png',
            'icon@2x.png',
            'icon@3x.png',
            'logo.png',
            'logo@2x.png',
            'logo@3x.png',
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
