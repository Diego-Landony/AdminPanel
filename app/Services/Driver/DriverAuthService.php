<?php

namespace App\Services\Driver;

use App\Exceptions\DriverInactiveException;
use App\Models\Driver;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class DriverAuthService
{
    /**
     * Authenticate a driver and generate a Sanctum token.
     *
     * @param  string  $email  The driver's email address
     * @param  string  $password  The driver's password
     * @param  string  $deviceName  The name of the device requesting authentication
     * @return array{token: string, driver: Driver}
     *
     * @throws AuthenticationException If credentials are invalid
     * @throws DriverInactiveException If the driver account is inactive
     */
    public function login(string $email, string $password, string $deviceName): array
    {
        $driver = Driver::query()->where('email', $email)->first();

        if (! $driver || ! Hash::check($password, $driver->password)) {
            throw new AuthenticationException(__('auth.failed'));
        }

        if (! $driver->is_active) {
            throw new DriverInactiveException($driver);
        }

        $this->recordLogin($driver);

        $token = $driver->createToken($deviceName)->plainTextToken;

        return [
            'token' => $token,
            'driver' => $driver,
        ];
    }

    /**
     * Revoke the driver's current token.
     */
    public function logout(Driver $driver): void
    {
        $driver->currentAccessToken()?->delete();
    }

    /**
     * Revoke all tokens for the driver.
     */
    public function logoutAllDevices(Driver $driver): void
    {
        $driver->tokens()->delete();
    }

    /**
     * Record the driver's login timestamp.
     */
    private function recordLogin(Driver $driver): void
    {
        $driver->update([
            'last_login_at' => now(),
            'last_activity_at' => now(),
        ]);
    }
}
