<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Test 1: Puede registrarse con datos válidos
test('puede registrarse con datos validos', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'María',
        'last_name' => 'García',
        'email' => 'maria@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'phone' => '12345678',
        'birth_date' => '1990-05-15',
        'gender' => 'female',
        'device_identifier' => 'test-device-001',
        'terms_accepted' => true,
    ]);

    // Verificar respuesta exitosa (201 Created)
    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'data' => [
                'token',
                'token_type',
                'expires_in',
                'customer' => [
                    'id',
                    'first_name',
                    'last_name',
                    'full_name',
                    'email',
                    'phone',
                ],
            ],
        ]);

    // Verificar que el customer fue creado en la BD
    $this->assertDatabaseHas('customers', [
        'email' => 'maria@example.com',
        'first_name' => 'María',
        'last_name' => 'García',
        'phone' => '12345678',
        'gender' => 'female',
    ]);

    // Verificar que el token existe
    expect($response->json('data.token'))->not->toBeNull();
    expect($response->json('data.token_type'))->toBe('Bearer');
});

// Test 2: Rechaza email duplicado
test('rechaza email duplicado', function () {
    // Crear customer existente
    Customer::create([
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'email' => 'juan@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => '1234567890',
    ]);

    // Intentar registrar con el mismo email
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Otro',
        'last_name' => 'Usuario',
        'email' => 'juan@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'phone' => '12345678',
        'birth_date' => '1990-05-15',
        'gender' => 'male',
        'device_identifier' => 'test-device-002',
        'terms_accepted' => true,
    ]);

    // Verificar error de validación
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

// Test 3: Requiere password confirmation
test('requiere password confirmation', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Carlos',
        'last_name' => 'López',
        'email' => 'carlos@example.com',
        'password' => 'SecurePass123!',
        'phone' => '12345678',
        'birth_date' => '1990-05-15',
        'gender' => 'male',
        'device_identifier' => 'test-device-003',
        'terms_accepted' => true,
        // Sin password_confirmation
    ]);

    // Verificar error de validación
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

// Test 4: Hashea password automáticamente
test('hashea password automaticamente', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Ana',
        'last_name' => 'Martínez',
        'email' => 'ana@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'phone' => '12345678',
        'birth_date' => '1990-05-15',
        'gender' => 'female',
        'device_identifier' => 'test-device-004',
        'terms_accepted' => true,
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
        'first_name' => 'Roberto',
        'last_name' => 'Ruiz',
        'email' => 'roberto@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'phone' => '12345678',
        'birth_date' => '1990-05-15',
        'gender' => 'male',
        'device_identifier' => 'test-device-005',
        'terms_accepted' => true,
    ]);

    // Verificar respuesta exitosa
    $response->assertCreated();

    // Obtener el customer de la BD
    $customer = Customer::where('email', 'roberto@example.com')->first();

    // Verificar que tiene tokens
    expect($customer->tokens()->count())->toBe(1);

    // Verificar que el token tiene el nombre del device_identifier (truncado a 8 chars)
    expect($customer->tokens()->first()->name)->toBe('test-dev');
});

// Test 6: Genera subway_card automaticamente
test('genera subway_card automaticamente', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Pedro',
        'last_name' => 'Gómez',
        'email' => 'pedro@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'phone' => '12345678',
        'birth_date' => '1990-05-15',
        'gender' => 'male',
        'device_identifier' => 'test-device-006',
        'terms_accepted' => true,
    ]);

    $response->assertCreated();

    $customer = Customer::where('email', 'pedro@example.com')->first();

    // Verificar que se generó subway_card
    expect($customer->subway_card)->not->toBeNull();

    // Verificar formato: debe tener 11 dígitos y comenzar con 8
    expect($customer->subway_card)->toHaveLength(11);
    expect($customer->subway_card)->toStartWith('8');

    // Verificar que es numérico
    expect($customer->subway_card)->toMatch('/^\d{11}$/');
});

// Test 7: Validates gender field using dataset
test('validates gender field', function (string $gender, bool $shouldPass) {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Test',
        'last_name' => 'Gender',
        'email' => 'test-gender-'.uniqid().'@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'phone' => '12345678',
        'birth_date' => '1990-05-15',
        'gender' => $gender,
        'device_identifier' => 'test-device',
        'terms_accepted' => true,
    ]);

    if ($shouldPass) {
        $response->assertCreated();
    } else {
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['gender']);
    }
})->with([
    'valid male' => ['male', true],
    'valid female' => ['female', true],
    'valid other' => ['other', true],
    'invalid masculino' => ['masculino', false],
    'invalid femenino' => ['femenino', false],
    'invalid empty' => ['', false],
]);

// Test 8: Validates phone format using dataset
test('validates phone format', function (string $phone, bool $shouldPass) {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Test',
        'last_name' => 'Phone',
        'email' => 'test-phone-'.uniqid().'@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'phone' => $phone,
        'birth_date' => '1990-05-15',
        'gender' => 'male',
        'device_identifier' => 'test-device',
        'terms_accepted' => true,
    ]);

    if ($shouldPass) {
        $response->assertCreated();
    } else {
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }
})->with([
    'valid 8 digits' => ['12345678', true],
    'too short' => ['1234567', false],
    'too long' => ['123456789', false],
    'with prefix' => ['+5021234', false],
    'with letters' => ['1234abcd', false],
]);

// Test 14: Requiere todos los campos obligatorios
test('requiere todos los campos obligatorios', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Test',
        'last_name' => 'Required',
        'email' => 'test-required@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        // Falta phone, birth_date, gender, device_identifier
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['phone', 'birth_date', 'gender', 'device_identifier']);
});
