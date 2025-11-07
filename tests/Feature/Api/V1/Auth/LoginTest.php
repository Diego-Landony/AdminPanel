<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Test 1: Puede hacer login con credenciales válidas
test('puede hacer login con credenciales validas', function () {
    // Crear customer de prueba
    $customer = Customer::create([
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => '1234567890',
    ]);

    // Hacer login
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'juan@example.com',
        'password' => 'password123',
        'os' => 'android',
    ]);

    // Verificar respuesta exitosa
    $response->assertOk();

    $response->assertJsonStructure([
        'data' => [
            'access_token',
            'token_type',
            'expires_in',
            'customer' => [
                'id',
                'name',
                'email',
            ],
        ],
    ]);

    // Verificar que el token existe
    expect($response->json('data.access_token'))->not->toBeNull();
    expect($response->json('data.token_type'))->toBe('Bearer');

    // Verificar que el customer en la respuesta es el correcto
    expect($response->json('data.customer.email'))->toBe('juan@example.com');
});

// Test 2: Rechaza credenciales inválidas
test('rechaza credenciales invalidas', function () {
    // Crear customer de prueba
    Customer::create([
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => '1234567890',
    ]);

    // Intentar login con password incorrecta
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'juan@example.com',
        'password' => 'wrongpassword',
    ]);

    // Verificar respuesta 422 (ValidationException)
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// Test 3: Rechaza usuario inexistente
test('rechaza usuario inexistente', function () {
    // Intentar login con email no registrado
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'noexiste@example.com',
        'password' => 'password123',
    ]);

    // Verificar respuesta 422 (ValidationException, NO 404 por seguridad)
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// Test 4: Puede especificar sistema operativo
test('puede especificar sistema operativo', function () {
    // Crear customer de prueba
    $customer = Customer::create([
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => '1234567890',
    ]);

    // Hacer login con os personalizado
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'juan@example.com',
        'password' => 'password123',
        'os' => 'ios',
    ]);

    // Verificar respuesta exitosa
    $response->assertOk();

    // Verificar que el token fue creado con el nombre correcto
    $customer->refresh();
    expect($customer->tokens()->first()->name)->toBe('ios');
});

// Test 5: Actualiza last_login_at
test('actualiza last login at', function () {
    // Crear customer de prueba con last_login_at en null
    $customer = Customer::create([
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => '1234567890',
        'last_login_at' => null,
    ]);

    // Verificar que last_login_at está en null
    expect($customer->last_login_at)->toBeNull();

    // Hacer login
    $this->postJson('/api/v1/auth/login', [
        'email' => 'juan@example.com',
        'password' => 'password123',
    ]);

    // Refrescar el modelo desde la BD
    $customer->refresh();

    // Verificar que last_login_at fue actualizado
    expect($customer->last_login_at)->not->toBeNull();
    expect($customer->last_login_at)->toBeInstanceOf(\Carbon\Carbon::class);
});
