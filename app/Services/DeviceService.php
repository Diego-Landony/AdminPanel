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
        string $deviceIdentifier
    ): CustomerDevice {
        // Find existing device by device_identifier ONLY (it's globally unique)
        // This handles the case where a device was previously used by another customer
        $device = CustomerDevice::where('device_identifier', $deviceIdentifier)->first();

        if ($device) {
            // Update existing device - reassign to current customer
            $device->update([
                'customer_id' => $customer->id,
                'sanctum_token_id' => $token->id,
                'is_active' => true,
                'last_used_at' => now(),
                'login_count' => $device->login_count + 1,
            ]);
        } else {
            // Create new device with minimal data
            $device = CustomerDevice::create([
                'customer_id' => $customer->id,
                'sanctum_token_id' => $token->id,
                'device_identifier' => $deviceIdentifier,
                'device_name' => $this->generateDefaultDeviceName(),
                'is_active' => true,
                'last_used_at' => now(),
                'login_count' => 1,
            ]);
        }

        return $device;
    }

    /**
     * Generate a default device name
     */
    protected function generateDefaultDeviceName(): string
    {
        return 'Device';
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
