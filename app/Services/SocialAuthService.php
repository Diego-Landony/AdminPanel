<?php

namespace App\Services;

use App\Exceptions\AccountDeletedException;
use App\Models\Customer;
use Illuminate\Auth\Events\Registered;
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
     * Permite vincular Google/Apple a cuentas existentes sin eliminar el acceso por contraseña.
     * - Si la cuenta es 'local', agrega el provider_id pero MANTIENE oauth_provider='local'
     * - Esto permite al usuario seguir usando su contraseña O el login social
     *
     * @throws ValidationException if email doesn't exist
     */
    public function findAndLinkCustomer(string $provider, object $providerData): array
    {
        $providerIdField = $provider.'_id';

        // Caso 0: Verificar si existe una cuenta eliminada (soft deleted)
        $deletedCustomer = Customer::where('email', $providerData->email)
            ->onlyTrashed()
            ->first();

        if ($deletedCustomer) {
            $deletedAt = $deletedCustomer->deleted_at;
            $daysSinceDeletion = $deletedAt->diffInDays(now());
            $daysLeft = max(0, 30 - $daysSinceDeletion);

            if ($daysLeft > 0) {
                // OAuth login ya verificó la identidad → reactivar automáticamente
                $deletedCustomer->restore();

                $deletedCustomer->update([
                    $providerIdField => $providerData->provider_id,
                    'avatar' => $providerData->avatar ?? $deletedCustomer->avatar,
                    'last_login_at' => now(),
                    'last_activity_at' => now(),
                ]);

                return [
                    'customer' => $deletedCustomer,
                    'message_key' => 'auth.account_reactivated',
                    'is_new' => false,
                    'was_reactivated' => true,
                    'points_recovered' => $deletedCustomer->points ?? 0,
                ];
            }

            // Si pasaron más de 30 días, eliminar permanentemente
            $deletedCustomer->forceDelete();
        }

        // Caso 1: Ya tiene este provider vinculado (login normal con OAuth)
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

        // Caso 2: Buscar por email
        $existingCustomer = Customer::where('email', $providerData->email)->first();

        if ($existingCustomer) {
            // Verificar si ya tiene OTRO provider OAuth vinculado (no local)
            // Por ejemplo: tiene Apple y quiere vincular Google
            $hasOtherOAuthProvider = $existingCustomer->oauth_provider !== 'local'
                && $existingCustomer->oauth_provider !== $provider;

            // Si ya tiene otro OAuth Y ese campo ya tiene un ID, no permitir
            // (pero sí permitir si es 'local' o si es el mismo provider)
            if ($hasOtherOAuthProvider) {
                // Permitir vincular múltiples providers
                // Solo rechazar si intenta usar un provider diferente al que ya tiene como principal
                // y no tiene el campo del provider actual vacío
                $currentProviderField = $existingCustomer->oauth_provider.'_id';
                if ($existingCustomer->$currentProviderField && ! $existingCustomer->$providerIdField) {
                    // Ya tiene otro OAuth como principal, pero podemos agregar este también
                    // No cambiamos oauth_provider, solo agregamos el nuevo ID
                }
            }

            // Vincular el provider SIN cambiar oauth_provider si es 'local'
            // Esto permite que siga usando su contraseña
            $updateData = [
                $providerIdField => $providerData->provider_id,
                'avatar' => $providerData->avatar ?? $existingCustomer->avatar,
                'last_login_at' => now(),
                'last_activity_at' => now(),
            ];

            // Solo cambiar oauth_provider si NO es 'local'
            // Si es 'local', mantenerlo para que pueda seguir usando contraseña
            if ($existingCustomer->oauth_provider === 'local') {
                // Mantener 'local' - el usuario puede usar contraseña Y OAuth
                // No agregamos 'oauth_provider' al update
            } else {
                // Si ya era OAuth, mantener el provider original
                // (no cambiar de 'google' a 'apple' por ejemplo)
            }

            $existingCustomer->update($updateData);

            // Mensaje diferente si es primera vez que vincula vs ya estaba vinculado
            $isFirstLink = $existingCustomer->wasChanged($providerIdField);
            $messageKey = $isFirstLink ? 'auth.oauth_account_linked' : 'auth.oauth_login_success';

            return [
                'customer' => $existingCustomer,
                'message_key' => $messageKey,
                'is_new' => false,
            ];
        }

        // Email no existe en la base de datos - RECHAZAR
        throw ValidationException::withMessages([
            'email' => [__('auth.oauth_email_not_registered')],
        ]);
    }

    /**
     * Create new customer from OAuth provider data (REGISTER - creates accounts)
     *
     * @throws ValidationException if email already exists (user_already_exists error)
     * @throws AccountDeletedException if account is soft-deleted and recoverable
     */
    public function createCustomerFromOAuth(string $provider, object $providerData): array
    {
        $providerIdField = $provider.'_id';

        // Verificar si ya existe una cuenta con este email
        $existingCustomer = Customer::where('email', $providerData->email)->first();

        if ($existingCustomer) {
            // La cuenta ya existe - SIEMPRE rechazar en flujo de registro
            throw ValidationException::withMessages([
                'email' => [__('auth.oauth_user_already_exists')],
            ])->errorBag('user_already_exists');
        }

        // Verificar si existe una cuenta eliminada (soft deleted)
        $deletedCustomer = Customer::where('email', $providerData->email)
            ->onlyTrashed()
            ->first();

        if ($deletedCustomer) {
            $deletedAt = $deletedCustomer->deleted_at;
            $daysSinceDeletion = $deletedAt->diffInDays(now());
            $daysLeft = max(0, 30 - $daysSinceDeletion);

            if ($daysLeft > 0) {
                // Cuenta recuperable - lanzar excepción para que el cliente maneje la reactivación
                throw new AccountDeletedException($deletedCustomer, $daysLeft);
            }

            // Más de 30 días - eliminar permanentemente
            $deletedCustomer->forceDelete();
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
            'password' => null, // OAuth users don't have password until they create one
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
