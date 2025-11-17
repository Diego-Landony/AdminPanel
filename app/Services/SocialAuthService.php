<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthService
{
    /**
     * Verify Google OAuth token and return user data
     *
     * @throws ValidationException
     */
    public function verifyGoogleToken(string $idToken): object
    {
        try {
            $socialiteUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($idToken);

            return (object) [
                'provider_id' => $socialiteUser->getId(),
                'email' => $socialiteUser->getEmail(),
                'name' => $socialiteUser->getName(),
                'avatar' => $socialiteUser->getAvatar(),
            ];
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'token' => [__('auth.oauth_invalid_token')],
            ]);
        }
    }

    /**
     * Find existing customer and link OAuth provider (LOGIN - does NOT create accounts)
     *
     * @throws ValidationException if email doesn't exist
     */
    public function findAndLinkCustomer(string $provider, object $providerData): array
    {
        $providerIdField = $provider.'_id';

        $customer = Customer::where($providerIdField, $providerData->provider_id)->first();

        if ($customer) {
            $customer->update([
                'last_login_at' => now(),
                'last_activity_at' => now(),
                'avatar' => $providerData->avatar ?? $customer->avatar,
            ]);

            return [
                'customer' => $customer,
                'message_key' => 'auth.oauth_login_success',
                'is_new' => false,
            ];
        }

        $existingCustomer = Customer::where('email', $providerData->email)->first();

        if ($existingCustomer) {
            if ($existingCustomer->oauth_provider !== $provider && $existingCustomer->oauth_provider !== 'local') {
                throw ValidationException::withMessages([
                    'email' => [__('auth.oauth_provider_mismatch', ['provider' => $existingCustomer->oauth_provider])],
                ]);
            }

            $existingCustomer->update([
                $providerIdField => $providerData->provider_id,
                'avatar' => $providerData->avatar ?? $existingCustomer->avatar,
                'oauth_provider' => $provider,
                'last_login_at' => now(),
                'last_activity_at' => now(),
            ]);

            return [
                'customer' => $existingCustomer,
                'message_key' => 'auth.oauth_account_linked',
                'is_new' => false,
            ];
        }

        // Email no existe en la base de datos - RECHAZAR
        throw ValidationException::withMessages([
            'email' => [__('auth.oauth_email_not_registered')],
        ]);
    }

    /**
     * Create new customer from OAuth provider data (REGISTER - creates account)
     *
     * @throws ValidationException if email already exists
     */
    public function createCustomerFromOAuth(string $provider, object $providerData): array
    {
        $providerIdField = $provider.'_id';

        // Verificar si ya existe una cuenta con este email
        $existingCustomer = Customer::where('email', $providerData->email)->first();

        if ($existingCustomer) {
            // Si ya existe, verificar si tiene el provider vinculado
            if ($providerData->provider_id === $existingCustomer->$providerIdField) {
                // Cuenta ya existe y ya está vinculada - solo hacer login
                $existingCustomer->update([
                    'last_login_at' => now(),
                    'last_activity_at' => now(),
                ]);

                return [
                    'customer' => $existingCustomer,
                    'message_key' => 'auth.oauth_login_success',
                    'is_new' => false,
                ];
            }

            // Email existe pero no está vinculado a este provider
            throw ValidationException::withMessages([
                'email' => [__('auth.oauth_email_exists')],
            ]);
        }

        // Crear nueva cuenta
        $fullName = $providerData->name ?? 'Usuario '.$provider;
        $nameParts = $this->splitFullName($fullName);

        $customer = Customer::create([
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'email' => $providerData->email,
            $providerIdField => $providerData->provider_id,
            'avatar' => $providerData->avatar,
            'oauth_provider' => $provider,
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(32)),
        ]);

        event(new Registered($customer));

        return [
            'customer' => $customer,
            'message_key' => 'auth.oauth_register_success',
            'is_new' => true,
        ];
    }

    /**
     * Split full name into first and last name
     */
    protected function splitFullName(string $fullName): array
    {
        $nameParts = explode(' ', trim($fullName), 2);

        return [
            'first_name' => $nameParts[0] ?? 'Usuario',
            'last_name' => $nameParts[1] ?? '',
        ];
    }
}
