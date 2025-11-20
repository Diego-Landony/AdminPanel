<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Test 1: Puede hacer login con credenciales válidas
test('puede hacer login con credenciales validas', function () {
    // Crear customer de prueba
    $customer = Customer::create([
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => '1234567890',
    ]);

    // Hacer login
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'juan@example.com',
        'password' => 'password123',
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
                'first_name',
                'last_name',
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

// Test 2: Rechaza credenciales inválidas (password incorrecta)
test('rechaza password incorrecta', function () {
    // Crear customer de prueba
    Customer::create([
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
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

    // Verificar respuesta 422 con error específico en campo 'password'
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
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

// Test 4: Puede especificar device identifier
test('puede especificar sistema operativo', function () {
    // Crear customer de prueba
    $customer = Customer::create([
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => '1234567890',
    ]);

    // Hacer login con device_identifier
    $deviceId = 'abc123-def456-ghi789';
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'juan@example.com',
        'password' => 'password123',
        'device_identifier' => $deviceId,
    ]);

    // Verificar respuesta exitosa
    $response->assertOk();

    // Verificar que el token fue creado con el nombre correcto (primeros 8 chars del UUID)
    $customer->refresh();
    expect($customer->tokens()->first()->name)->toBe(substr($deviceId, 0, 8));
});

// Test 5: Actualiza last_login_at
test('actualiza last login at', function () {
    // Crear customer de prueba con last_login_at en null
    $customer = Customer::create([
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
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
