<?php

use App\Models\Customer;

/**
 * Tests de integración para el sistema de gestión de clientes
 */
test('complete customer management workflow', function () {
    $testUser = createTestUserForIntegration();
    $this->actingAs($testUser);

    // 1. Acceder a la página de clientes (debe estar vacía)
    $response = $this->get('/customers');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('customers/index')
        ->where('total_customers', 0)
    );

    // 2. Crear un nuevo cliente
    $customerData = [
        'full_name' => 'Cliente de Integración',
        'email' => 'integracion@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'subway_card' => '9876543210',
        'birth_date' => '1985-12-25',
        'gender' => 'femenino',
        // customer_type_id will be assigned based on points or manually
        'phone' => '+502 5555-0000',
        'address' => 'Calle de Prueba 123',
        'location' => 'Ciudad de Guatemala',
        'nit' => '87654321-0',
    ];

    $response = $this->post('/customers', $customerData);
    $response->assertRedirect('/customers');
    $response->assertSessionHas('success');

    // 3. Verificar que el cliente aparece en la lista
    $response = $this->get('/customers');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('customers/index')
        ->where('total_customers', 1)
        ->has('customers.data', 1)
        ->where('customers.data.0.full_name', 'Cliente de Integración')
    );

    // 4. Buscar el cliente por nombre
    $response = $this->get('/customers?search=Integración');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->has('customers.data', 1)
        ->where('customers.data.0.email', 'integracion@test.com')
    );

    // 5. Editar el cliente
    $customer = Customer::where('email', 'integracion@test.com')->first();
    $response = $this->get("/customers/{$customer->id}/edit");
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('customers/edit')
        ->where('customer.full_name', 'Cliente de Integración')
    );

    // 6. Actualizar información del cliente
    $updateData = [
        'full_name' => 'Cliente Actualizado',
        'email' => 'actualizado@test.com',
        'subway_card' => $customer->subway_card,
        'birth_date' => $customer->birth_date->format('Y-m-d'),
        // customer_type_id will be assigned based on points or manually
        'phone' => '+502 9999-8888',
    ];

    $response = $this->put("/customers/{$customer->id}", $updateData);
    $response->assertRedirect();
    $response->assertSessionHas('success');

    // 7. Verificar cambios en la lista
    $response = $this->get('/customers');
    $response->assertInertia(fn ($page) => $page->where('customers.data.0.full_name', 'Cliente Actualizado')
    );

    // 8. Eliminar cliente
    $response = $this->delete("/customers/{$customer->id}");
    $response->assertRedirect();
    $response->assertSessionHas('success');

    // 9. Verificar que el cliente fue eliminado
    $response = $this->get('/customers');
    $response->assertInertia(fn ($page) => $page->where('total_customers', 0)
    );
});

test('customer search functionality works correctly', function () {
    $testUser = createTestUserForIntegration();
    $this->actingAs($testUser);

    // Crear clientes con diferentes atributos
    Customer::factory()->create([
        'full_name' => 'Juan Carlos Pérez',
        'email' => 'juan@test.com',
        'subway_card' => '1111111111',
        'phone' => '+502 1111-1111',
        // customer_type_id will be assigned based on points or manually
    ]);

    Customer::factory()->create([
        'full_name' => 'María Elena González',
        'email' => 'maria@test.com',
        'subway_card' => '2222222222',
        'phone' => '+502 2222-2222',
        // customer_type_id will be assigned based on points or manually
    ]);

    Customer::factory()->create([
        'full_name' => 'Carlos Antonio López',
        'email' => 'carlos@test.com',
        'subway_card' => '3333333333',
        'phone' => '+502 3333-3333',
        // customer_type_id will be assigned based on points or manually
    ]);

    // Buscar por nombre
    $response = $this->get('/customers?search=Juan');
    $response->assertInertia(fn ($page) => $page->has('customers.data', 1)
        ->where('customers.data.0.full_name', 'Juan Carlos Pérez')
    );

    // Buscar por email
    $response = $this->get('/customers?search=maria@test.com');
    $response->assertInertia(fn ($page) => $page->has('customers.data', 1)
        ->where('customers.data.0.email', 'maria@test.com')
    );

    // Buscar por subway card
    $response = $this->get('/customers?search=3333333333');
    $response->assertInertia(fn ($page) => $page->has('customers.data', 1)
        ->where('customers.data.0.subway_card', '3333333333')
    );

    // Buscar por teléfono
    $response = $this->get('/customers?search=2222-2222');
    $response->assertInertia(fn ($page) => $page->has('customers.data', 1)
        ->where('customers.data.0.phone', '+502 2222-2222')
    );

    // Búsqueda que no encuentra resultados
    $response = $this->get('/customers?search=NoExiste');
    $response->assertInertia(fn ($page) => $page->has('customers.data', 0)
    );
});

test('customer sorting functionality works correctly', function () {
    $testUser = createTestUserForIntegration();
    $this->actingAs($testUser);

    // Crear clientes con fechas específicas
    Customer::factory()->create([
        'full_name' => 'Ana Beatriz',
        'created_at' => now()->subDays(3),
        'last_activity_at' => now()->subMinutes(2), // Online
    ]);

    Customer::factory()->create([
        'full_name' => 'Carlos Daniel',
        'created_at' => now()->subDays(1),
        'last_activity_at' => now()->subMinutes(30), // Offline
    ]);

    Customer::factory()->create([
        'full_name' => 'Beatriz Elena',
        'created_at' => now()->subDays(2),
        'last_activity_at' => null, // Never
    ]);

    // Ordenar por nombre ascendente
    $response = $this->get('/customers?sort_field=full_name&sort_direction=asc');
    $response->assertInertia(fn ($page) => $page->where('customers.data.0.full_name', 'Ana Beatriz')
        ->where('customers.data.1.full_name', 'Beatriz Elena')
        ->where('customers.data.2.full_name', 'Carlos Daniel')
    );

    // Ordenar por nombre descendente
    $response = $this->get('/customers?sort_field=full_name&sort_direction=desc');
    $response->assertInertia(fn ($page) => $page->where('customers.data.0.full_name', 'Carlos Daniel')
        ->where('customers.data.1.full_name', 'Beatriz Elena')
        ->where('customers.data.2.full_name', 'Ana Beatriz')
    );

    // Ordenar por fecha de creación descendente (más reciente primero)
    $response = $this->get('/customers?sort_field=created_at&sort_direction=desc');
    $response->assertInertia(fn ($page) => $page->where('customers.data.0.full_name', 'Carlos Daniel') // Más reciente
        ->where('customers.data.2.full_name', 'Ana Beatriz') // Más antiguo
    );
});

test('customer pagination works correctly', function () {
    $testUser = createTestUserForIntegration();
    $this->actingAs($testUser);

    // Crear 25 clientes para probar paginación
    Customer::factory(25)->create();

    // Primera página con 10 por página
    $response = $this->get('/customers?per_page=10');
    $response->assertInertia(fn ($page) => $page->has('customers.data', 10)
        ->where('customers.current_page', 1)
        ->where('customers.per_page', 10)
        ->where('customers.total', 25)
        ->where('customers.last_page', 3)
    );

    // Segunda página
    $response = $this->get('/customers?per_page=10&page=2');
    $response->assertInertia(fn ($page) => $page->has('customers.data', 10)
        ->where('customers.current_page', 2)
    );

    // Última página
    $response = $this->get('/customers?per_page=10&page=3');
    $response->assertInertia(fn ($page) => $page->has('customers.data', 5) // Solo 5 en la última página
        ->where('customers.current_page', 3)
    );
});

test('customer statistics are calculated correctly', function () {
    $testUser = createTestUserForIntegration();
    $this->actingAs($testUser);

    // Crear clientes con diferentes características
    Customer::factory()->create([
        // customer_type_id will be assigned based on points or manually
        'email_verified_at' => now(),
        'last_activity_at' => now()->subMinutes(2), // Online
    ]);

    Customer::factory()->create([
        // customer_type_id will be assigned based on points or manually
        'email_verified_at' => now(),
        'last_activity_at' => now()->subMinutes(30), // Offline
    ]);

    Customer::factory()->create([
        // customer_type_id will be assigned based on points or manually
        'email_verified_at' => null, // No verificado
        'last_activity_at' => now()->subMinutes(3), // Online
    ]);

    Customer::factory()->create([
        // customer_type_id will be assigned based on points or manually
        'email_verified_at' => now(),
        'last_activity_at' => null, // Never active
    ]);

    Customer::factory()->create([
        // customer_type_id will be assigned based on points or manually
        'email_verified_at' => now(),
        'last_activity_at' => now()->subMinutes(1), // Online
    ]);

    $response = $this->get('/customers');
    $response->assertInertia(fn ($page) => $page->where('total_customers', 5)
        ->where('verified_customers', 4) // 4 verificados
        ->where('online_customers', 3) // 3 en línea (< 5 minutos)
        // Customer type stats depend on actual CustomerType records, removed assertions
    );
});

test('customer validation errors are handled correctly', function () {
    $testUser = createTestUserForIntegration();
    $this->actingAs($testUser);

    // Test validación de campos requeridos
    $response = $this->post('/customers', []);
    $response->assertSessionHasErrors([
        'full_name', 'email', 'password', 'subway_card', 'birth_date',
    ]);

    // Test validación de email duplicado
    $existingCustomer = Customer::factory()->create(['email' => 'existing@test.com']);

    $response = $this->post('/customers', [
        'full_name' => 'Test Customer',
        'email' => 'existing@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'subway_card' => '1234567890',
        'birth_date' => '1990-01-01',
    ]);
    $response->assertSessionHasErrors(['email']);

    // Test validación de subway card duplicado
    $response = $this->post('/customers', [
        'full_name' => 'Test Customer',
        'email' => 'new@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'subway_card' => $existingCustomer->subway_card,
        'birth_date' => '1990-01-01',
    ]);
    $response->assertSessionHasErrors(['subway_card']);

    // Test validación de confirmación de contraseña
    $response = $this->post('/customers', [
        'full_name' => 'Test Customer',
        'email' => 'test@new.com',
        'password' => 'password123',
        'password_confirmation' => 'different_password',
        'subway_card' => '9876543210',
        'birth_date' => '1990-01-01',
    ]);
    $response->assertSessionHasErrors(['password']);
});

// createTestUser is now defined in tests/Feature/helpers.php
