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
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
        'phone' => '+50212345678',
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
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
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
        'password' => 'SecurePass123',
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
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
    ]);

    // Verificar respuesta exitosa
    $response->assertCreated();

    // Obtener el customer de la BD
    $customer = Customer::where('email', 'ana@example.com')->first();

    // Verificar que la password está hasheada (no es la original)
    expect($customer->password)->not->toBe('SecurePass123');

    // Verificar que el hash funciona correctamente
    expect(Hash::check('SecurePass123', $customer->password))->toBeTrue();
});

// Test 5: Crea token Sanctum al registrarse
test('crea token sanctum al registrarse', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Roberto',
        'last_name' => 'Ruiz',
        'email' => 'roberto@example.com',
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
    ]);

    // Verificar respuesta exitosa
    $response->assertCreated();

    // Obtener el customer de la BD
    $customer = Customer::where('email', 'roberto@example.com')->first();

    // Verificar que tiene tokens
    expect($customer->tokens()->count())->toBe(1);

    // Verificar que el token tiene un nombre generado
    expect($customer->tokens()->first()->name)->toStartWith('device-');
});

// Test 6: Genera subway_card automaticamente
test('genera subway_card automaticamente', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Pedro',
        'last_name' => 'Gómez',
        'email' => 'pedro@example.com',
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
    ]);

    $response->assertCreated();

    $customer = Customer::where('email', 'pedro@example.com')->first();

    // Verificar que se generó subway_card
    expect($customer->subway_card)->not->toBeNull();

    // Verificar formato: debe tener 12 dígitos y comenzar con 8
    expect($customer->subway_card)->toHaveLength(12);
    expect($customer->subway_card)->toStartWith('8');

    // Verificar que es numérico
    expect($customer->subway_card)->toMatch('/^\d{12}$/');
});

// Test 7: Genera subway_card unica
test('genera subway_card unica', function () {
    // Crear primer customer
    $response1 = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Cliente',
        'last_name' => 'Uno',
        'email' => 'cliente1@example.com',
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
    ]);

    // Crear segundo customer
    $response2 = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Cliente',
        'last_name' => 'Dos',
        'email' => 'cliente2@example.com',
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
    ]);

    $response1->assertCreated();
    $response2->assertCreated();

    $customer1 = Customer::where('email', 'cliente1@example.com')->first();
    $customer2 = Customer::where('email', 'cliente2@example.com')->first();

    // Verificar que ambos tienen subway_card
    expect($customer1->subway_card)->not->toBeNull();
    expect($customer2->subway_card)->not->toBeNull();

    // Verificar que son diferentes
    expect($customer1->subway_card)->not->toBe($customer2->subway_card);
});

// Test 8: Mantiene compatibilidad con tarjetas legacy
test('mantiene compatibilidad con tarjetas legacy', function () {
    // Crear customer con tarjeta legacy de 11 dígitos
    $legacyCard = '70000000001';

    Customer::create([
        'first_name' => 'Cliente',
        'last_name' => 'Legacy',
        'email' => 'legacy@example.com',
        'password' => Hash::make('password123'),
        'oauth_provider' => 'local',
        'subway_card' => $legacyCard,
    ]);

    // Verificar que se guardó correctamente
    $customer = Customer::where('email', 'legacy@example.com')->first();
    expect($customer->subway_card)->toBe($legacyCard);
    expect($customer->subway_card)->toHaveLength(11);
    expect($customer->subway_card)->toStartWith('7');
});

// Test 9: Valida formato de gender estandarizado
test('valida formato de gender estandarizado', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Test',
        'last_name' => 'Gender',
        'email' => 'test-gender@example.com',
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
        'gender' => 'male',
    ]);

    $response->assertCreated();

    $customer = Customer::where('email', 'test-gender@example.com')->first();
    expect($customer->gender)->toBe('male');

    // Verificar que la respuesta JSON contiene el gender correcto
    expect($response->json('data.customer.gender'))->toBe('male');
});

// Test 10: Rechaza gender no válido
test('rechaza gender no valido', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Test',
        'last_name' => 'Invalid Gender',
        'email' => 'invalid-gender@example.com',
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
        'gender' => 'masculino', // formato antiguo no válido
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['gender']);
});

// Test 11: No incluye timezone en respuesta
test('no incluye timezone en respuesta', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Test',
        'last_name' => 'Timezone',
        'email' => 'test-timezone@example.com',
        'password' => 'SecurePass123',
        'password_confirmation' => 'SecurePass123',
    ]);

    $response->assertCreated();

    // Verificar que timezone no está en la respuesta
    expect($response->json('data.customer'))->not->toHaveKey('timezone');
});
