<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Test 1: Puede registrarse con datos válidos
test('puede registrarse con datos validos', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'María García',
        'email' => 'maria@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'phone' => '+50212345678',
        'device_name' => 'Test Device',
    ]);

    // Verificar respuesta exitosa (201 Created)
    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
                'customer' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                ],
            ],
        ]);

    // Verificar que el customer fue creado en la BD
    $this->assertDatabaseHas('customers', [
        'email' => 'maria@example.com',
        'name' => 'María García',
        'phone' => '+50212345678',
    ]);

    // Verificar que el token existe
    expect($response->json('data.access_token'))->not->toBeNull();
    expect($response->json('data.token_type'))->toBe('Bearer');
});

// Test 2: Rechaza email duplicado
test('rechaza email duplicado', function () {
    // Crear customer existente
    Customer::create([
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => '1234567890',
    ]);

    // Intentar registrar con el mismo email
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Otro Usuario',
        'email' => 'juan@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ]);

    // Verificar error de validación
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

// Test 3: Requiere password confirmation
test('requiere password confirmation', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Carlos López',
        'email' => 'carlos@example.com',
        'password' => 'SecurePass123!',
        // Sin password_confirmation
    ]);

    // Verificar error de validación
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

// Test 4: Hashea password automáticamente
test('hashea password automaticamente', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Ana Martínez',
        'email' => 'ana@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ]);

    // Verificar respuesta exitosa
    $response->assertCreated();

    // Obtener el customer de la BD
    $customer = Customer::where('email', 'ana@example.com')->first();

    // Verificar que la password está hasheada (no es la original)
    expect($customer->password)->not->toBe('SecurePass123!');

    // Verificar que el hash funciona correctamente
    expect(Hash::check('SecurePass123!', $customer->password))->toBeTrue();
});

// Test 5: Crea token Sanctum al registrarse
test('crea token sanctum al registrarse', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Roberto Ruiz',
        'email' => 'roberto@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'device_name' => 'iPhone 15',
    ]);

    // Verificar respuesta exitosa
    $response->assertCreated();

    // Obtener el customer de la BD
    $customer = Customer::where('email', 'roberto@example.com')->first();

    // Verificar que tiene tokens
    expect($customer->tokens()->count())->toBe(1);

    // Verificar que el token tiene el nombre del dispositivo
    expect($customer->tokens()->first()->name)->toBe('iPhone 15');
});
