<?php

use App\Exceptions\AccountDeletedException;
use App\Models\Customer;
use App\Services\SocialAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Bind FirebaseAuth mock globally so the SocialAuthService can be resolved
    $this->app->bind(FirebaseAuth::class, fn () => Mockery::mock(FirebaseAuth::class));
});

// Test 1: Login exitoso con Firebase Google Sign-In
test('puede hacer login con firebase google sign-in', function () {
    $customer = Customer::create([
        'first_name' => 'María',
        'last_name' => 'García',
        'email' => 'maria@example.com',
        'google_id' => 'firebase-uid-123',
        'oauth_provider' => 'google',
        'subway_card' => '9876543210',
        'email_verified_at' => now(),
    ]);

    $mock = Mockery::mock(SocialAuthService::class)->makePartial();
    $mock->shouldReceive('verifyFirebaseToken')
        ->once()
        ->with('fake-firebase-id-token')
        ->andReturn((object) [
            'provider_id' => 'firebase-uid-123',
            'email' => 'maria@example.com',
            'name' => 'María García',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

    $mock->shouldReceive('findAndLinkCustomer')
        ->once()
        ->with('google', Mockery::type('object'))
        ->andReturn([
            'customer' => $customer,
            'is_new' => false,
            'message_key' => 'auth.oauth_login_success',
        ]);

    $this->app->instance(SocialAuthService::class, $mock);

    $response = $this->postJson('/api/v1/auth/oauth/google/firebase', [
        'firebase_token' => 'fake-firebase-id-token',
        'action' => 'login',
        'device_identifier' => 'test-device-firebase-001',
    ]);

    $response->assertOk();

    $response->assertJsonStructure([
        'token',
        'customer_id',
        'is_new_customer',
        'message',
    ]);

    expect($response->json('customer_id'))->toBe($customer->id);
    expect($response->json('is_new_customer'))->toBeFalse();
});

// Test 2: Registro exitoso con Firebase Google Sign-In
test('puede registrarse con firebase google sign-in', function () {
    $customer = Customer::create([
        'first_name' => 'Carlos',
        'last_name' => 'López',
        'email' => 'carlos@example.com',
        'google_id' => 'firebase-uid-456',
        'oauth_provider' => 'google',
        'subway_card' => '1122334455',
        'email_verified_at' => now(),
    ]);

    $mock = Mockery::mock(SocialAuthService::class)->makePartial();
    $mock->shouldReceive('verifyFirebaseToken')
        ->once()
        ->with('fake-firebase-register-token')
        ->andReturn((object) [
            'provider_id' => 'firebase-uid-456',
            'email' => 'carlos@example.com',
            'name' => 'Carlos López',
            'avatar' => '',
        ]);

    $mock->shouldReceive('createCustomerFromOAuth')
        ->once()
        ->with('google', Mockery::type('object'))
        ->andReturn([
            'customer' => $customer,
            'is_new' => true,
            'message_key' => 'auth.oauth_register_success',
        ]);

    $this->app->instance(SocialAuthService::class, $mock);

    $response = $this->postJson('/api/v1/auth/oauth/google/firebase', [
        'firebase_token' => 'fake-firebase-register-token',
        'action' => 'register',
        'device_identifier' => 'test-device-firebase-002',
    ]);

    $response->assertOk();
    expect($response->json('is_new_customer'))->toBeTrue();
    expect($response->json('token'))->not->toBeNull();
});

// Test 3: Rechaza request sin firebase_token
test('rechaza request sin firebase token', function () {
    $response = $this->postJson('/api/v1/auth/oauth/google/firebase', [
        'action' => 'login',
        'device_identifier' => 'test-device',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['firebase_token']);
});

// Test 4: Rechaza action inválida
test('rechaza action invalida', function () {
    $response = $this->postJson('/api/v1/auth/oauth/google/firebase', [
        'firebase_token' => 'some-token',
        'action' => 'invalid_action',
        'device_identifier' => 'test-device',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['action']);
});

// Test 5: Rechaza request sin device_identifier
test('rechaza request sin device identifier', function () {
    $response = $this->postJson('/api/v1/auth/oauth/google/firebase', [
        'firebase_token' => 'some-token',
        'action' => 'login',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['device_identifier']);
});

// Test 6: Retorna error cuando firebase token es inválido
test('retorna error cuando firebase token es invalido', function () {
    $mock = Mockery::mock(SocialAuthService::class)->makePartial();
    $mock->shouldReceive('verifyFirebaseToken')
        ->once()
        ->andThrow(
            \Illuminate\Validation\ValidationException::withMessages([
                'firebase_token' => ['The token is invalid or expired.'],
            ])
        );

    $this->app->instance(SocialAuthService::class, $mock);

    $response = $this->postJson('/api/v1/auth/oauth/google/firebase', [
        'firebase_token' => 'invalid-token',
        'action' => 'login',
        'device_identifier' => 'test-device',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['firebase_token']);
});

// Test 7: Retorna 409 cuando la cuenta está eliminada pero recuperable
test('retorna 409 cuando cuenta eliminada es recuperable', function () {
    $customer = Customer::factory()->create([
        'email' => 'deleted@example.com',
        'google_id' => 'firebase-uid-789',
        'oauth_provider' => 'google',
        'points' => 500,
        'deleted_at' => now()->subDays(15),
    ]);

    $mock = Mockery::mock(SocialAuthService::class)->makePartial();
    $mock->shouldReceive('verifyFirebaseToken')
        ->once()
        ->andReturn((object) [
            'provider_id' => 'firebase-uid-789',
            'email' => 'deleted@example.com',
            'name' => 'Deleted User',
            'avatar' => '',
        ]);

    $mock->shouldReceive('findAndLinkCustomer')
        ->once()
        ->andThrow(new AccountDeletedException($customer, 15));

    $this->app->instance(SocialAuthService::class, $mock);

    $response = $this->postJson('/api/v1/auth/oauth/google/firebase', [
        'firebase_token' => 'valid-token-deleted-account',
        'action' => 'login',
        'device_identifier' => 'test-device',
    ]);

    $response->assertStatus(409);
    $response->assertJson([
        'error' => 'account_deleted_recoverable',
        'email' => 'deleted@example.com',
        'days_left' => 15,
        'points' => 500,
    ]);
});

// ============================================
// Apple Sign-In Tests
// ============================================

// Test 8: Login exitoso con Firebase Apple Sign-In
test('puede hacer login con firebase apple sign-in', function () {
    $customer = Customer::create([
        'first_name' => 'Ana',
        'last_name' => 'Martínez',
        'email' => 'ana@example.com',
        'apple_id' => 'apple-uid-123',
        'oauth_provider' => 'apple',
        'subway_card' => '5555666677',
        'email_verified_at' => now(),
    ]);

    $mock = Mockery::mock(SocialAuthService::class)->makePartial();
    $mock->shouldReceive('verifyFirebaseToken')
        ->once()
        ->with('fake-firebase-apple-token')
        ->andReturn((object) [
            'provider_id' => 'apple-uid-123',
            'email' => 'ana@example.com',
            'name' => 'Ana Martínez',
        ]);

    $mock->shouldReceive('findAndLinkCustomer')
        ->once()
        ->with('apple', Mockery::type('object'))
        ->andReturn([
            'customer' => $customer,
            'is_new' => false,
            'message_key' => 'auth.oauth_login_success',
        ]);

    $this->app->instance(SocialAuthService::class, $mock);

    $response = $this->postJson('/api/v1/auth/oauth/apple/firebase', [
        'firebase_token' => 'fake-firebase-apple-token',
        'action' => 'login',
        'device_identifier' => 'test-device-apple-001',
    ]);

    $response->assertOk();

    $response->assertJsonStructure([
        'token',
        'customer_id',
        'is_new_customer',
        'message',
    ]);

    expect($response->json('customer_id'))->toBe($customer->id);
    expect($response->json('is_new_customer'))->toBeFalse();
});

// Test 9: Registro exitoso con Firebase Apple Sign-In
test('puede registrarse con firebase apple sign-in', function () {
    $customer = Customer::create([
        'first_name' => 'Pedro',
        'last_name' => 'Sánchez',
        'email' => 'pedro@example.com',
        'apple_id' => 'apple-uid-456',
        'oauth_provider' => 'apple',
        'subway_card' => '8888999900',
        'email_verified_at' => now(),
    ]);

    $mock = Mockery::mock(SocialAuthService::class)->makePartial();
    $mock->shouldReceive('verifyFirebaseToken')
        ->once()
        ->with('fake-firebase-apple-register-token')
        ->andReturn((object) [
            'provider_id' => 'apple-uid-456',
            'email' => 'pedro@example.com',
            'name' => 'Pedro Sánchez',
        ]);

    $mock->shouldReceive('createCustomerFromOAuth')
        ->once()
        ->with('apple', Mockery::type('object'))
        ->andReturn([
            'customer' => $customer,
            'is_new' => true,
            'message_key' => 'auth.oauth_register_success',
        ]);

    $this->app->instance(SocialAuthService::class, $mock);

    $response = $this->postJson('/api/v1/auth/oauth/apple/firebase', [
        'firebase_token' => 'fake-firebase-apple-register-token',
        'action' => 'register',
        'device_identifier' => 'test-device-apple-002',
    ]);

    $response->assertOk();
    expect($response->json('is_new_customer'))->toBeTrue();
    expect($response->json('token'))->not->toBeNull();
});

// Test 10: Apple Sign-In rechaza request sin firebase_token
test('apple sign-in rechaza request sin firebase token', function () {
    $response = $this->postJson('/api/v1/auth/oauth/apple/firebase', [
        'action' => 'login',
        'device_identifier' => 'test-device',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['firebase_token']);
});

// Test 11: Apple Sign-In valida action correctamente
test('apple sign-in rechaza action invalida', function () {
    $response = $this->postJson('/api/v1/auth/oauth/apple/firebase', [
        'firebase_token' => 'some-token',
        'action' => 'invalid_action',
        'device_identifier' => 'test-device',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['action']);
});
