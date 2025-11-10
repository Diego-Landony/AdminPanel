<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

// Test 1: Puede solicitar reset de password
test('puede solicitar reset de password', function () {
    // Crear customer de prueba
    $customer = Customer::create([
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => '1234567890',
    ]);

    // Solicitar reset de password
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'juan@example.com',
    ]);

    // Verificar respuesta exitosa
    $response->assertOk()
        ->assertJson([
            'message' => 'Enlace de restablecimiento de contraseña enviado.',
        ]);

    // Verificar que se creó un token en la tabla password_reset_tokens
    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => 'juan@example.com',
    ]);
});

// Test 2: Puede cambiar password con token válido
test('puede cambiar password con token valido', function () {
    // Crear customer de prueba
    $customer = Customer::create([
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('OldPass123'),
        'oauth_provider' => 'local',
        'subway_card' => '1234567890',
    ]);

    // Crear un token de reset válido
    $token = Password::broker('customers')->createToken($customer);

    // Usar el token para cambiar la password
    $response = $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'juan@example.com',
        'token' => $token,
        'password' => 'NewPass123',
        'password_confirmation' => 'NewPass123',
    ]);

    // Verificar respuesta exitosa
    $response->assertOk()
        ->assertJson([
            'message' => 'Contraseña restablecida exitosamente.',
        ]);

    // Refrescar el customer desde la BD
    $customer->refresh();

    // Verificar que la nueva password funciona
    expect(Hash::check('NewPass123', $customer->password))->toBeTrue();

    // Verificar que la vieja password no funciona
    expect(Hash::check('OldPass123', $customer->password))->toBeFalse();
});

// Test 3: Rechaza token expirado
test('rechaza token expirado', function () {
    // Crear customer de prueba
    $customer = Customer::create([
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => '1234567890',
    ]);

    // Intentar reset con un token inválido/expirado
    $response = $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'juan@example.com',
        'token' => 'token-invalido-o-expirado',
        'password' => 'NewPass123',
        'password_confirmation' => 'NewPass123',
    ]);

    // Verificar error de validación
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

// Test 4: Puede cambiar password estando autenticado
test('puede cambiar password estando autenticado', function () {
    // Crear customer de prueba
    $customer = Customer::create([
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('CurrPass123'),
        'oauth_provider' => 'local',
        'subway_card' => '1234567890',
    ]);

    // Crear token para autenticación
    $token = $customer->createToken('test-device')->plainTextToken;

    // Cambiar password estando autenticado
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/v1/profile/password', [
            'current_password' => 'CurrPass123',
            'password' => 'NewPass456',
            'password_confirmation' => 'NewPass456',
        ]);

    // Verificar respuesta exitosa
    $response->assertOk()
        ->assertJson([
            'message' => 'Contraseña actualizada exitosamente. Se cerraron todas las otras sesiones.',
        ]);

    // Refrescar el customer desde la BD
    $customer->refresh();

    // Verificar que la nueva password funciona
    expect(Hash::check('NewPass456', $customer->password))->toBeTrue();

    // Verificar que la vieja password no funciona
    expect(Hash::check('CurrPass123', $customer->password))->toBeFalse();
});

// Test 5: Requiere password actual correcto
test('requiere password actual correcto', function () {
    // Crear customer de prueba
    $customer = Customer::create([
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('CurrPass123'),
        'oauth_provider' => 'local',
        'subway_card' => '1234567890',
    ]);

    // Crear token para autenticación
    $token = $customer->createToken('test-device')->plainTextToken;

    // Intentar cambiar password con password actual incorrecta
    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/v1/profile/password', [
            'current_password' => 'WrongPass123',
            'password' => 'NewPass456',
            'password_confirmation' => 'NewPass456',
        ]);

    // Verificar error de validación
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);

    // Refrescar el customer desde la BD
    $customer->refresh();

    // Verificar que la password NO cambió
    expect(Hash::check('CurrPass123', $customer->password))->toBeTrue();
});
