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
                'token' => ['Token de Google inválido o expirado.'],
            ]);
        }
    }

    /**
     * Verify Apple OAuth token and return user data
     *
     * @throws ValidationException
     */
    public function verifyAppleToken(string $idToken): object
    {
        try {
            $socialiteUser = Socialite::driver('apple')
                ->stateless()
                ->userFromToken($idToken);

            return (object) [
                'provider_id' => $socialiteUser->getId(),
                'email' => $socialiteUser->getEmail(),
                'name' => $socialiteUser->getName() ?? null,
                'avatar' => $socialiteUser->getAvatar() ?? null,
            ];
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'token' => ['Token de Apple inválido o expirado.'],
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
                'message' => 'Inicio de sesión exitoso.',
                'is_new' => false,
            ];
        }

        $existingCustomer = Customer::where('email', $providerData->email)->first();

        if ($existingCustomer) {
            if ($existingCustomer->oauth_provider !== $provider && $existingCustomer->oauth_provider !== 'local') {
                throw ValidationException::withMessages([
                    'email' => ['Esta cuenta ya existe con autenticación '.$existingCustomer->oauth_provider.'.'],
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
                'message' => 'Cuenta vinculada exitosamente.',
                'is_new' => false,
            ];
        }

        // Email no existe en la base de datos - RECHAZAR
        throw ValidationException::withMessages([
            'email' => ['No existe una cuenta con este correo electrónico. Por favor regístrate primero.'],
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
                    'message' => 'Inicio de sesión exitoso.',
                    'is_new' => false,
                ];
            }

            // Email existe pero no está vinculado a este provider
            throw ValidationException::withMessages([
                'email' => ['Ya existe una cuenta con este correo electrónico. Por favor inicia sesión con tu método original.'],
            ]);
        }

        // Crear nueva cuenta
        $customer = Customer::create([
            'name' => $providerData->name ?? 'Usuario '.$provider,
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
            'message' => 'Cuenta creada exitosamente.',
            'is_new' => true,
        ];
    }
}
