<?php

use App\Models\Customer;
use App\Services\SocialAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Test 1: Rechaza login con Google si el email no existe
test('rechaza login con google si el email no existe', function () {
    $mockService = $this->partialMock(SocialAuthService::class);

    $mockService->shouldReceive('verifyGoogleToken')
        ->once()
        ->andReturn((object) [
            'provider_id' => 'google-123',
            'email' => 'noexiste@example.com',
            'name' => 'Usuario Nuevo',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

    // No mockear findAndLinkCustomer - dejar que use la implementación real
    // que lanzará ValidationException correctamente

    $response = $this->postJson('/api/v1/auth/oauth/google', [
        'id_token' => 'fake-id-token',
        'os' => 'android',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email'])
        ->assertJson([
            'message' => 'No existe una cuenta con este correo electrónico. Por favor regístrate primero.',
        ]);
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

    $mockService = $this->partialMock(SocialAuthService::class);

    $mockService->shouldReceive('verifyGoogleToken')
        ->once()
        ->andReturn((object) [
            'provider_id' => 'google-123',
            'email' => 'juan@example.com',
            'name' => 'Juan Pérez',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

    // Dejar que findAndLinkCustomer use la implementación real

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
    expect($response->json('message'))->toBe('Cuenta vinculada exitosamente.');

    // Verificar que se vinculó el google_id
    $customer->refresh();
    expect($customer->google_id)->toBe('google-123');
    expect($customer->oauth_provider)->toBe('google');
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

    $mockService = $this->partialMock(SocialAuthService::class);

    $mockService->shouldReceive('verifyGoogleToken')
        ->once()
        ->andReturn((object) [
            'provider_id' => 'google-456',
            'email' => 'maria@example.com',
            'name' => 'María García',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

    // Dejar que findAndLinkCustomer use la implementación real

    $response = $this->postJson('/api/v1/auth/oauth/google', [
        'id_token' => 'fake-id-token',
        'os' => 'ios',
    ]);

    $response->assertOk();
    expect($response->json('message'))->toBe('Cuenta vinculada exitosamente.');

    // Verificar que se vinculó correctamente
    $customer->refresh();
    expect($customer->google_id)->toBe('google-456');
    expect($customer->oauth_provider)->toBe('google');
});

// Test 4: Permite registro con Google si el email no existe
test('permite registro con google si el email no existe', function () {
    $mockService = $this->partialMock(SocialAuthService::class);

    $mockService->shouldReceive('verifyGoogleToken')
        ->once()
        ->andReturn((object) [
            'provider_id' => 'google-new-123',
            'email' => 'nuevousuario@example.com',
            'name' => 'Nuevo Usuario',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

    // Dejar que createCustomerFromOAuth use la implementación real

    $response = $this->postJson('/api/v1/auth/oauth/google/register', [
        'id_token' => 'fake-id-token',
        'os' => 'android',
    ]);

    $response->assertCreated()
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

    expect($response->json('data.is_new_customer'))->toBeTrue();
    expect($response->json('message'))->toBe('Cuenta creada exitosamente.');

    // Verificar que se creó el customer correctamente
    $customer = Customer::where('email', 'nuevousuario@example.com')->first();
    expect($customer)->not->toBeNull();
    expect($customer->google_id)->toBe('google-new-123');
    expect($customer->oauth_provider)->toBe('google');
    expect($customer->email_verified_at)->not->toBeNull();
});

// Test 5: Rechaza registro con Google si el email ya existe
test('rechaza registro con google si el email ya existe', function () {
    // Crear customer existente
    Customer::create([
        'name' => 'Usuario Existente',
        'email' => 'existente@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    $mockService = $this->partialMock(SocialAuthService::class);

    $mockService->shouldReceive('verifyGoogleToken')
        ->once()
        ->andReturn((object) [
            'provider_id' => 'google-456',
            'email' => 'existente@example.com',
            'name' => 'Usuario Existente',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

    // Dejar que createCustomerFromOAuth use la implementación real

    $response = $this->postJson('/api/v1/auth/oauth/google/register', [
        'id_token' => 'fake-id-token',
        'os' => 'android',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email'])
        ->assertJson([
            'message' => 'Ya existe una cuenta con este correo electrónico. Por favor inicia sesión con tu método original.',
        ]);
});
