<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerDevice;
use Laravel\Sanctum\PersonalAccessToken;

class DeviceService
{
    /**
     * Synchronize device with token - create or update device record
     */
    public function syncDeviceWithToken(
        Customer $customer,
        PersonalAccessToken $token,
        string $deviceIdentifier,
        string $deviceType,
        ?string $deviceFingerprint = null
    ): CustomerDevice {
        // Find existing device by device_identifier
        $device = CustomerDevice::where('customer_id', $customer->id)
            ->where('device_identifier', $deviceIdentifier)
            ->first();

        if ($device) {
            // Update existing device
            $device->update([
                'sanctum_token_id' => $token->id,
                'device_type' => $deviceType,
                'device_fingerprint' => $deviceFingerprint ?? $device->device_fingerprint,
                'is_active' => true,
                'last_used_at' => now(),
                'login_count' => $device->login_count + 1,
            ]);

            // Recalculate trust score after login
            $device->trust_score = $this->calculateTrustScore($device);
            $device->save();
        } else {
            // Create new device with minimal data
            $device = CustomerDevice::create([
                'customer_id' => $customer->id,
                'sanctum_token_id' => $token->id,
                'device_identifier' => $deviceIdentifier,
                'device_fingerprint' => $deviceFingerprint,
                'device_type' => $deviceType,
                'device_name' => $this->generateDefaultDeviceName($deviceType),
                'is_active' => true,
                'last_used_at' => now(),
                'login_count' => 1,
                'trust_score' => 50, // Default trust score for new devices
            ]);
        }

        return $device;
    }

    /**
     * Generate a default device name based on device type
     */
    protected function generateDefaultDeviceName(string $deviceType): string
    {
        return match ($deviceType) {
            'ios' => 'iOS Device',
            'android' => 'Android Device',
            'web' => 'Web Browser',
            default => 'Unknown Device',
        };
    }

    /**
     * Calculate trust score for a device
     */
    public function calculateTrustScore(CustomerDevice $device): int
    {
        $score = 50; // Base score

        // More logins = more trust (max +30 points)
        $score += min(30, $device->login_count * 2);

        // Older device = more trust (max +20 points)
        $daysSinceCreation = $device->created_at->diffInDays(now());
        $score += min(20, floor($daysSinceCreation / 7));

        // Active device = more trust (+10 points)
        if ($device->is_active) {
            $score += 10;
        }

        // Recently used = more trust (+10 points)
        if ($device->last_used_at && $device->last_used_at->diffInDays(now()) < 7) {
            $score += 10;
        }

        // Cap at 100
        return min(100, $score);
    }

    /**
     * Mark devices as inactive if not used in specified days
     */
    public function deactivateInactiveDevices(int $inactiveDays = 90): int
    {
        $threshold = now()->subDays($inactiveDays);

        return CustomerDevice::where('is_active', true)
            ->where('last_used_at', '<', $threshold)
            ->update(['is_active' => false]);
    }

    /**
     * Soft delete devices inactive for extended period
     */
    public function cleanupOldDevices(int $inactiveDays = 180): int
    {
        $threshold = now()->subDays($inactiveDays);

        return CustomerDevice::where('is_active', false)
            ->where('last_used_at', '<', $threshold)
            ->delete();
    }
}
