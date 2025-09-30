<?php

use App\Models\Customer;

/**
 * Test suite para CustomerController
 */
test('customers index page displays customers list', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    // Crear algunos clientes de prueba
    $customers = Customer::factory(3)->create();

    $response = $this->get('/customers');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('customers/index')
        ->has('customers.data', 3)
        ->has('total_customers')
        ->has('verified_customers')
        ->has('online_customers')
        ->has('premium_customers')
        ->has('vip_customers')
        ->has('customer_type_stats')
    );
});

test('customers index can search customers', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    // Crear clientes específicos para la búsqueda
    Customer::factory()->create(['full_name' => 'Juan Pérez']);
    Customer::factory()->create(['full_name' => 'María González']);
    Customer::factory()->create(['email' => 'test@example.com']);

    $response = $this->get('/customers?search=Juan');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('customers/index')
        ->has('customers.data', 1)
        ->where('customers.data.0.full_name', 'Juan Pérez')
    );
});

test('customers index can sort customers', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    // Crear clientes con nombres específicos para ordenar
    Customer::factory()->create(['full_name' => 'Ana López']);
    Customer::factory()->create(['full_name' => 'Carlos Ruiz']);
    Customer::factory()->create(['full_name' => 'Beatriz Silva']);

    $response = $this->get('/customers?sort_field=full_name&sort_direction=asc');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('customers/index')
        ->where('customers.data.0.full_name', 'Ana López')
        ->where('customers.data.1.full_name', 'Beatriz Silva')
        ->where('customers.data.2.full_name', 'Carlos Ruiz')
    );
});

test('customers create page renders correctly', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $response = $this->get('/customers/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('customers/create')
    );
});

test('can create a new customer', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $customerData = [
        'full_name' => 'Nuevo Cliente',
        'email' => 'nuevo@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'subway_card' => '1234567890',
        'birth_date' => '1990-05-15',
        'gender' => 'masculino',
        // Customer type assigned automatically
        'phone' => '+502 1234-5678',
        'address' => 'Dirección de prueba',
        'location' => 'Guatemala',
        'nit' => '12345678-9',
    ];

    $response = $this->post('/customers', $customerData);

    $response->assertRedirect('/customers');
    $response->assertSessionHas('success', 'Cliente creado exitosamente');

    // Verificar que el cliente fue creado en la base de datos
    $this->assertDatabaseHas('customers', [
        'full_name' => 'Nuevo Cliente',
        'email' => 'nuevo@test.com',
        'subway_card' => '1234567890',
        // Customer type assigned automatically
    ]);

    // Verificar que la contraseña fue hasheada
    $customer = Customer::where('email', 'nuevo@test.com')->first();
    expect($customer->password)->not->toBe('password123');
    expect(\Hash::check('password123', $customer->password))->toBeTrue();
});

test('customer creation validates required fields', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $response = $this->post('/customers', []);

    $response->assertSessionHasErrors([
        'full_name',
        'email',
        'password',
        'subway_card',
        'birth_date',
    ]);
});

test('customer creation validates unique email', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $existingCustomer = Customer::factory()->create(['email' => 'existing@test.com']);

    $customerData = [
        'full_name' => 'Nuevo Cliente',
        'email' => 'existing@test.com', // Email ya existe
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'subway_card' => '1234567890',
        'birth_date' => '1990-05-15',
    ];

    $response = $this->post('/customers', $customerData);

    $response->assertSessionHasErrors(['email']);
});

test('customer creation validates unique subway card', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $existingCustomer = Customer::factory()->create(['subway_card' => '1234567890']);

    $customerData = [
        'full_name' => 'Nuevo Cliente',
        'email' => 'nuevo@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'subway_card' => '1234567890', // Subway card ya existe
        'birth_date' => '1990-05-15',
    ];

    $response = $this->post('/customers', $customerData);

    $response->assertSessionHasErrors(['subway_card']);
});

test('customers edit page renders with customer data', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $customer = Customer::factory()->create([
        'full_name' => 'Cliente Test',
        'email' => 'cliente@test.com',
    ]);

    $response = $this->get("/customers/{$customer->id}/edit");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('customers/edit')
        ->has('customer')
        ->where('customer.full_name', 'Cliente Test')
        ->where('customer.email', 'cliente@test.com')
    );
});

test('can update customer information', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $customer = Customer::factory()->create([
        'full_name' => 'Cliente Original',
        'email' => 'original@test.com',
    ]);

    $updateData = [
        'full_name' => 'Cliente Actualizado',
        'email' => 'actualizado@test.com',
        'subway_card' => $customer->subway_card,
        'birth_date' => $customer->birth_date->format('Y-m-d'),
        // Customer type assigned automatically
        'phone' => '+502 9876-5432',
    ];

    $response = $this->put("/customers/{$customer->id}", $updateData);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Cliente actualizado exitosamente');

    // Verificar que los datos fueron actualizados
    $customer->refresh();
    expect($customer->full_name)->toBe('Cliente Actualizado');
    expect($customer->email)->toBe('actualizado@test.com');
    // Customer type is managed through relationships, not direct field
    expect($customer->phone)->toBe('+502 9876-5432');
});

test('can update customer password', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $customer = Customer::factory()->create();
    $originalPassword = $customer->password;

    $updateData = [
        'full_name' => $customer->full_name,
        'email' => $customer->email,
        'subway_card' => $customer->subway_card,
        'birth_date' => $customer->birth_date->format('Y-m-d'),
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ];

    $response = $this->put("/customers/{$customer->id}", $updateData);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Cliente actualizado exitosamente');

    // Verificar que la contraseña fue cambiada
    $customer->refresh();
    expect($customer->password)->not->toBe($originalPassword);
    expect(\Hash::check('newpassword123', $customer->password))->toBeTrue();
});

test('email verification is reset when email is changed', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $customer = Customer::factory()->create([
        'email' => 'original@test.com',
        'email_verified_at' => now(),
    ]);

    expect($customer->email_verified_at)->not->toBeNull();

    $updateData = [
        'full_name' => $customer->full_name,
        'email' => 'newemail@test.com',
        'subway_card' => $customer->subway_card,
        'birth_date' => $customer->birth_date->format('Y-m-d'),
        'gender' => $customer->gender,
        'phone' => $customer->phone,
    ];

    $response = $this->put("/customers/{$customer->id}", $updateData);

    $response->assertRedirect();

    // Verificar que email_verified_at fue reseteado
    $customer->refresh();
    expect($customer->email)->toBe('newemail@test.com');
    expect($customer->email_verified_at)->toBeNull();
});

test('can delete customer', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $customer = Customer::factory()->create(['full_name' => 'Cliente a Eliminar']);

    $response = $this->delete("/customers/{$customer->id}");

    $response->assertRedirect();
    $response->assertSessionHas('success', "Cliente 'Cliente a Eliminar' eliminado exitosamente");

    // Verificar que el cliente fue eliminado (soft delete)
    $this->assertSoftDeleted('customers', ['id' => $customer->id]);
});

test('unauthenticated users cannot access customer routes', function () {
    $customer = Customer::factory()->create();

    $this->get('/customers')->assertRedirect('/login');
    $this->get('/customers/create')->assertRedirect('/login');
    $this->get("/customers/{$customer->id}/edit")->assertRedirect('/login');
    $this->post('/customers', [])->assertRedirect('/login');
    $this->put("/customers/{$customer->id}", [])->assertRedirect('/login');
    $this->delete("/customers/{$customer->id}")->assertRedirect('/login');
});

// createTestUser is now defined in tests/Feature/helpers.php
