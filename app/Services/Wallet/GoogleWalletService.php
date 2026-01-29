<?php

namespace App\Services\Wallet;

use App\Models\Customer;
use Firebase\JWT\JWT;
use Google\Client as GoogleClient;
use Google\Service\Walletobjects;
use Google\Service\Walletobjects\LoyaltyClass;
use Google\Service\Walletobjects\LoyaltyObject;
use RuntimeException;

class GoogleWalletService
{
    private ?Walletobjects $walletService = null;

    /**
     * Generate a Google Wallet save URL for the given customer.
     *
     * Uses the "skinny JWT" approach:
     * 1. Pre-create/update LoyaltyObject via REST API
     * 2. Generate compact JWT referencing only the object ID
     * 3. Return save URL
     */
    public function generateSaveUrl(Customer $customer): string
    {
        $this->validateConfiguration();

        $customer->loadMissing('customerType');

        $this->ensureClassExists();
        $object = $this->createOrUpdateObject($customer);

        $jwt = $this->generateSkinnyJwt($object->getId());

        return 'https://pay.google.com/gp/v/save/'.$jwt;
    }

    /**
     * Create or update the LoyaltyClass (one-time operation).
     */
    public function createOrUpdateClass(): LoyaltyClass
    {
        $this->validateConfiguration();

        $issuerId = config('services.google_wallet.issuer_id');
        $classId = config('services.google_wallet.class_id');
        $fullClassId = $issuerId.'.'.$classId;

        $loyaltyClass = new LoyaltyClass;
        $loyaltyClass->setId($fullClassId);
        $loyaltyClass->setIssuerName('Subway Guatemala');
        $loyaltyClass->setProgramName(config('services.google_wallet.program_name'));
        $loyaltyClass->setReviewStatus('UNDER_REVIEW');
        $loyaltyClass->setHexBackgroundColor('#008244');

        $programLogo = new Walletobjects\Image;
        $logoSourceUri = new Walletobjects\ImageUri;
        $logoSourceUri->setUri('https://appmobile.subwaycardgt.com/iconsubway.png');
        $logoDescription = new Walletobjects\LocalizedString;
        $logoDescriptionValue = new Walletobjects\TranslatedString;
        $logoDescriptionValue->setLanguage('es');
        $logoDescriptionValue->setValue('Subway Guatemala Logo');
        $logoDescription->setDefaultValue($logoDescriptionValue);
        $programLogo->setSourceUri($logoSourceUri);
        $programLogo->setContentDescription($logoDescription);
        $loyaltyClass->setProgramLogo($programLogo);

        $service = $this->getWalletService();

        try {
            return $service->loyaltyclass->update($fullClassId, $loyaltyClass);
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() === 404) {
                return $service->loyaltyclass->insert($loyaltyClass);
            }

            throw new RuntimeException('Failed to create/update Google Wallet class: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Ensure the LoyaltyClass exists, creating it if necessary.
     */
    private function ensureClassExists(): void
    {
        $issuerId = config('services.google_wallet.issuer_id');
        $classId = config('services.google_wallet.class_id');
        $fullClassId = $issuerId.'.'.$classId;

        $service = $this->getWalletService();

        try {
            $service->loyaltyclass->get($fullClassId);
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() === 404) {
                $this->createOrUpdateClass();

                return;
            }

            throw new RuntimeException('Failed to verify Google Wallet class: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Create or update a LoyaltyObject for a specific customer.
     */
    private function createOrUpdateObject(Customer $customer): LoyaltyObject
    {
        $issuerId = config('services.google_wallet.issuer_id');
        $classId = config('services.google_wallet.class_id');
        $fullClassId = $issuerId.'.'.$classId;
        $objectId = $issuerId.'.subway_loyalty_'.$customer->id;

        $loyaltyObject = new LoyaltyObject;
        $loyaltyObject->setId($objectId);
        $loyaltyObject->setClassId($fullClassId);
        $loyaltyObject->setState('ACTIVE');
        $loyaltyObject->setAccountId($customer->subway_card);
        $loyaltyObject->setAccountName($customer->full_name);

        // Loyalty points
        $loyaltyPoints = new Walletobjects\LoyaltyPoints;
        $loyaltyPoints->setLabel('Puntos');
        $pointsBalance = new Walletobjects\LoyaltyPointsBalance;
        $pointsBalance->setInt($customer->points ?? 0);
        $loyaltyPoints->setBalance($pointsBalance);
        $loyaltyObject->setLoyaltyPoints($loyaltyPoints);

        // Barcode
        $barcode = new Walletobjects\Barcode;
        $barcode->setType('CODE_128');
        $barcode->setValue($customer->subway_card);
        $barcode->setAlternateText($customer->subway_card);
        $loyaltyObject->setBarcode($barcode);

        // Tier info via text modules
        $tierName = $customer->customerType?->name ?? 'Regular';
        $textModule = new Walletobjects\TextModuleData;
        $textModule->setHeader('Nivel');
        $textModule->setBody($tierName);
        $textModule->setId('tier_info');
        $loyaltyObject->setTextModulesData([$textModule]);

        $service = $this->getWalletService();

        try {
            return $service->loyaltyobject->update($objectId, $loyaltyObject);
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() === 404) {
                return $service->loyaltyobject->insert($loyaltyObject);
            }

            throw new RuntimeException('Failed to create/update Google Wallet object: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate a compact "skinny JWT" referencing an existing object by ID only.
     */
    private function generateSkinnyJwt(string $objectId): string
    {
        $serviceAccountPath = config('services.google_wallet.service_account_path');
        $credentials = json_decode(file_get_contents($serviceAccountPath), true);

        $now = time();

        $payload = [
            'iss' => $credentials['client_email'],
            'aud' => 'google',
            'typ' => 'savetowallet',
            'iat' => $now,
            'payload' => [
                'loyaltyObjects' => [
                    ['id' => $objectId],
                ],
            ],
        ];

        return JWT::encode($payload, $credentials['private_key'], 'RS256');
    }

    /**
     * Get authenticated Google Walletobjects service instance.
     */
    private function getWalletService(): Walletobjects
    {
        if ($this->walletService !== null) {
            return $this->walletService;
        }

        $serviceAccountPath = config('services.google_wallet.service_account_path');

        $client = new GoogleClient;
        $client->setAuthConfig($serviceAccountPath);
        $client->addScope('https://www.googleapis.com/auth/wallet_object.issuer');

        $this->walletService = new Walletobjects($client);

        return $this->walletService;
    }

    /**
     * Validate that all required configuration exists.
     *
     * @throws RuntimeException
     */
    private function validateConfiguration(): void
    {
        $serviceAccountPath = config('services.google_wallet.service_account_path');
        $issuerId = config('services.google_wallet.issuer_id');

        if (! $serviceAccountPath || ! file_exists($serviceAccountPath)) {
            throw new RuntimeException('Google Wallet service account file not found at: '.$serviceAccountPath);
        }

        if (! $issuerId) {
            throw new RuntimeException('Google Wallet Issuer ID not configured. Set GOOGLE_WALLET_ISSUER_ID in .env');
        }
    }
}
