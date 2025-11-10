<?php

use App\Models\Customer;
use App\Services\SocialAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Test 1: Rechaza login con Google si el email no existe
test('rechaza login con google si el email no existe', function () {
    $mockService = $this->mock(SocialAuthService::class);

    $mockService->shouldReceive('verifyGoogleToken')
        ->once()
        ->with('fake-id-token')
        ->andReturn((object) [
            'provider_id' => 'google-123',
            'email' => 'noexiste@example.com',
            'name' => 'Usuario Nuevo',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

    $mockService->shouldReceive('findOrCreateCustomer')
        ->once()
        ->andThrow(new \Illuminate\Validation\ValidationException(
            validator([], []),
            response()->json([
                'message' => 'No existe una cuenta con este correo electrónico. Por favor regístrate primero.',
                'errors' => [
                    'email' => ['No existe una cuenta con este correo electrónico. Por favor regístrate primero.'],
                ],
            ], 422)
        ));

    $response = $this->postJson('/api/v1/auth/oauth/google', [
        'id_token' => 'fake-id-token',
        'os' => 'android',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

// Test 2: Permite login con Google si el email ya existe
test('permite login con google si el email ya existe', function () {
    // Crear customer existente
    $customer = Customer::create([
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    $mockService = $this->mock(SocialAuthService::class);

    $mockService->shouldReceive('verifyGoogleToken')
        ->once()
        ->andReturn((object) [
            'provider_id' => 'google-123',
            'email' => 'juan@example.com',
            'name' => 'Juan Pérez',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

    $mockService->shouldReceive('findOrCreateCustomer')
        ->once()
        ->andReturn([
            'customer' => $customer->fresh(),
            'message' => 'Cuenta vinculada exitosamente.',
            'is_new' => false,
        ]);

    $response = $this->postJson('/api/v1/auth/oauth/google', [
        'id_token' => 'fake-id-token',
        'os' => 'android',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
                'customer',
                'is_new_customer',
            ],
        ]);

    expect($response->json('data.is_new_customer'))->toBeFalse();
});

// Test 3: Vincula Google a cuenta local existente
test('vincula google a cuenta local existente', function () {
    // Crear customer con oauth_provider = local
    $customer = Customer::create([
        'name' => 'María García',
        'email' => 'maria@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
        'google_id' => null,
    ]);

    expect($customer->google_id)->toBeNull();
    expect($customer->oauth_provider)->toBe('local');

    $mockService = $this->mock(SocialAuthService::class);

    $mockService->shouldReceive('verifyGoogleToken')
        ->once()
        ->andReturn((object) [
            'provider_id' => 'google-456',
            'email' => 'maria@example.com',
            'name' => 'María García',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

    $mockService->shouldReceive('findOrCreateCustomer')
        ->once()
        ->andReturnUsing(function ($provider, $providerData) use ($customer) {
            // Simular el comportamiento real: vincular Google ID
            $customer->update([
                'google_id' => $providerData->provider_id,
                'oauth_provider' => 'google',
            ]);

            return [
                'customer' => $customer->fresh(),
                'message' => 'Cuenta vinculada exitosamente.',
                'is_new' => false,
            ];
        });

    $response = $this->postJson('/api/v1/auth/oauth/google', [
        'id_token' => 'fake-id-token',
        'os' => 'ios',
    ]);

    $response->assertOk();
    expect($response->json('message'))->toContain('vinculada');

    // Verificar que se vinculó correctamente
    $customer->refresh();
    expect($customer->google_id)->toBe('google-456');
    expect($customer->oauth_provider)->toBe('google');
});

// Test 4: Rechaza login con Apple si el email no existe
test('rechaza login con apple si el email no existe', function () {
    $mockService = $this->mock(SocialAuthService::class);

    $mockService->shouldReceive('verifyAppleToken')
        ->once()
        ->with('fake-apple-token')
        ->andReturn((object) [
            'provider_id' => 'apple-789',
            'email' => 'noexiste@example.com',
            'name' => 'Usuario Nuevo',
            'avatar' => null,
        ]);

    $mockService->shouldReceive('findOrCreateCustomer')
        ->once()
        ->andThrow(new \Illuminate\Validation\ValidationException(
            validator([], []),
            response()->json([
                'message' => 'No existe una cuenta con este correo electrónico. Por favor regístrate primero.',
                'errors' => [
                    'email' => ['No existe una cuenta con este correo electrónico. Por favor regístrate primero.'],
                ],
            ], 422)
        ));

    $response = $this->postJson('/api/v1/auth/oauth/apple', [
        'id_token' => 'fake-apple-token',
        'os' => 'ios',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
