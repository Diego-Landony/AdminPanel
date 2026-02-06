<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverLoginRequest;
use App\Http\Resources\Api\V1\Driver\AuthenticatedDriverResource;
use App\Http\Resources\Api\V1\Driver\DriverResource;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate driver and generate token.
     */
    public function login(DriverLoginRequest $request): JsonResponse
    {
        $this->ensureIsNotRateLimited($request);

        $driver = Driver::where('email', $request->email)->first();

        if (! $driver) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => [__('auth.account_not_found')],
            ]);
        }

        if (! $driver->is_active) {
            RateLimiter::hit($this->throttleKey($request));

            return response()->json([
                'success' => false,
                'message' => __('auth.driver_inactive'),
                'error_code' => 'DRIVER_INACTIVE',
            ], 403);
        }

        if (! Hash::check($request->password, $driver->password)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'password' => [__('auth.incorrect_password')],
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        // Update last login timestamp
        $driver->update([
            'last_login_at' => now(),
            'last_activity_at' => now(),
        ]);

        // Limit tokens per driver (max 3 devices)
        $this->enforceTokenLimit($driver, 3);

        $tokenName = $this->generateTokenName($request->device_name);
        $token = $driver->createToken($tokenName, ['driver'])->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => AuthenticatedDriverResource::make($driver->load('restaurant'))
                ->additional(['token' => $token]),
            'message' => __('auth.login_success'),
        ]);
    }

    /**
     * Logout driver (revoke current token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user('driver')->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => __('auth.logout_success'),
        ]);
    }

    /**
     * Get authenticated driver data.
     */
    public function me(Request $request): JsonResponse
    {
        $driver = $request->user('driver');
        $driver->load('restaurant');

        return response()->json([
            'success' => true,
            'data' => DriverResource::make($driver),
            'message' => 'Driver data retrieved successfully.',
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => [
                __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ],
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return 'driver_login:'.Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip());
    }

    /**
     * Generate token name with device name
     */
    protected function generateTokenName(string $deviceName): string
    {
        return 'driver_'.substr(Str::slug($deviceName), 0, 20).'_'.Str::random(8);
    }

    /**
     * Enforce maximum token limit per driver
     */
    protected function enforceTokenLimit(Driver $driver, int $maxTokens): void
    {
        $tokens = $driver->tokens()->orderBy('created_at', 'asc')->get();

        if ($tokens->count() >= $maxTokens) {
            // Delete oldest tokens to make room for new one
            $tokensToDelete = $tokens->take($tokens->count() - $maxTokens + 1);
            foreach ($tokensToDelete as $token) {
                $token->delete();
            }
        }
    }
}
