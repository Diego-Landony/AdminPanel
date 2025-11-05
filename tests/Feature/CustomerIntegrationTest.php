<?php

use App\Models\Customer;

describe('Complete Workflow', function () {
    test('complete customer management workflow', function () {
        $testUser = createTestUserForIntegration();
        $this->actingAs($testUser);

        $response = $this->get('/customers');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('customers/index')
            ->where('total_customers', 0)
        );

        $customerData = [
            'name' => 'Cliente de Integración',
            'email' => 'integracion@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subway_card' => '9876543210',
            'birth_date' => '1985-12-25',
            'gender' => 'femenino',
            'phone' => '+502 5555-0000',
            'address' => 'Calle de Prueba 123',
            'nit' => '87654321-0',
        ];

        $response = $this->post('/customers', $customerData);
        $response->assertRedirect('/customers');
        $response->assertSessionHas('success');

        $response = $this->get('/customers');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('customers/index')
            ->where('total_customers', 1)
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Cliente de Integración')
        );

        $response = $this->get('/customers?search=Integración');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('customers.data', 1)
            ->where('customers.data.0.email', 'integracion@test.com')
        );

        $customer = Customer::where('email', 'integracion@test.com')->first();
        $response = $this->get("/customers/{$customer->id}/edit");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('customers/edit')
            ->where('customer.name', 'Cliente de Integración')
        );

        $updateData = [
            'name' => 'Cliente Actualizado',
            'email' => 'actualizado@test.com',
            'subway_card' => $customer->subway_card,
            'birth_date' => $customer->birth_date->format('Y-m-d'),
            'phone' => '+502 9999-8888',
        ];

        $response = $this->put("/customers/{$customer->id}", $updateData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $response = $this->get('/customers');
        $response->assertInertia(fn ($page) => $page->where('customers.data.0.name', 'Cliente Actualizado')
        );

        $response = $this->delete("/customers/{$customer->id}");
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $response = $this->get('/customers');
        $response->assertInertia(fn ($page) => $page->where('total_customers', 0)
        );
    });
});

describe('Search Functionality', function () {
    test('customer search functionality works correctly', function () {
        $testUser = createTestUserForIntegration();
        $this->actingAs($testUser);

        Customer::factory()->create([
            'name' => 'Juan Carlos Pérez',
            'email' => 'juan@test.com',
            'subway_card' => '1111111111',
            'phone' => '+502 1111-1111',
        ]);

        Customer::factory()->create([
            'name' => 'María Elena González',
            'email' => 'maria@test.com',
            'subway_card' => '2222222222',
            'phone' => '+502 2222-2222',
        ]);

        Customer::factory()->create([
            'name' => 'Carlos Antonio López',
            'email' => 'carlos@test.com',
            'subway_card' => '3333333333',
            'phone' => '+502 3333-3333',
        ]);

        $response = $this->get('/customers?search=Juan');
        $response->assertInertia(fn ($page) => $page->has('customers.data', 1)
            ->where('customers.data.0.name', 'Juan Carlos Pérez')
        );

        $response = $this->get('/customers?search=maria@test.com');
        $response->assertInertia(fn ($page) => $page->has('customers.data', 1)
            ->where('customers.data.0.email', 'maria@test.com')
        );

        $response = $this->get('/customers?search=3333333333');
        $response->assertInertia(fn ($page) => $page->has('customers.data', 1)
            ->where('customers.data.0.subway_card', '3333333333')
        );

        $response = $this->get('/customers?search=2222-2222');
        $response->assertInertia(fn ($page) => $page->has('customers.data', 1)
            ->where('customers.data.0.phone', '+502 2222-2222')
        );

        $response = $this->get('/customers?search=NoExiste');
        $response->assertInertia(fn ($page) => $page->has('customers.data', 0)
        );
    });
});

describe('Sorting Functionality', function () {
    test('customer sorting functionality works correctly', function () {
        $testUser = createTestUserForIntegration();
        $this->actingAs($testUser);

        Customer::factory()->create([
            'name' => 'Ana Beatriz',
            'created_at' => now()->subDays(3),
            'last_activity_at' => now()->subMinutes(2),
        ]);

        Customer::factory()->create([
            'name' => 'Carlos Daniel',
            'created_at' => now()->subDays(1),
            'last_activity_at' => now()->subMinutes(30),
        ]);

        Customer::factory()->create([
            'name' => 'Beatriz Elena',
            'created_at' => now()->subDays(2),
            'last_activity_at' => null,
        ]);

        $response = $this->get('/customers?sort_field=name&sort_direction=asc');
        $response->assertInertia(fn ($page) => $page->where('customers.data.0.name', 'Ana Beatriz')
            ->where('customers.data.1.name', 'Beatriz Elena')
            ->where('customers.data.2.name', 'Carlos Daniel')
        );

        $response = $this->get('/customers?sort_field=name&sort_direction=desc');
        $response->assertInertia(fn ($page) => $page->where('customers.data.0.name', 'Carlos Daniel')
            ->where('customers.data.1.name', 'Beatriz Elena')
            ->where('customers.data.2.name', 'Ana Beatriz')
        );

        $response = $this->get('/customers?sort_field=created_at&sort_direction=desc');
        $response->assertInertia(fn ($page) => $page->where('customers.data.0.name', 'Carlos Daniel')
            ->where('customers.data.2.name', 'Ana Beatriz')
        );
    });
});

describe('Pagination', function () {
    test('customer pagination works correctly', function () {
        $testUser = createTestUserForIntegration();
        $this->actingAs($testUser);

        Customer::factory(25)->create();

        $response = $this->get('/customers?per_page=10');
        $response->assertInertia(fn ($page) => $page->has('customers.data', 10)
            ->where('customers.current_page', 1)
            ->where('customers.per_page', 10)
            ->where('customers.total', 25)
            ->where('customers.last_page', 3)
        );

        $response = $this->get('/customers?per_page=10&page=2');
        $response->assertInertia(fn ($page) => $page->has('customers.data', 10)
            ->where('customers.current_page', 2)
        );

        $response = $this->get('/customers?per_page=10&page=3');
        $response->assertInertia(fn ($page) => $page->has('customers.data', 5)
            ->where('customers.current_page', 3)
        );
    });
});

describe('Statistics', function () {
    test('customer statistics are calculated correctly', function () {
        $testUser = createTestUserForIntegration();
        $this->actingAs($testUser);

        Customer::factory()->create([
            'email_verified_at' => now(),
            'last_activity_at' => now()->subMinutes(2),
        ]);

        Customer::factory()->create([
            'email_verified_at' => now(),
            'last_activity_at' => now()->subMinutes(30),
        ]);

        Customer::factory()->create([
            'email_verified_at' => null,
            'last_activity_at' => now()->subMinutes(3),
        ]);

        Customer::factory()->create([
            'email_verified_at' => now(),
            'last_activity_at' => null,
        ]);

        Customer::factory()->create([
            'email_verified_at' => now(),
            'last_activity_at' => now()->subMinutes(1),
        ]);

        $response = $this->get('/customers');
        $response->assertInertia(fn ($page) => $page->where('total_customers', 5)
            ->where('verified_customers', 4)
            ->where('online_customers', 3)
        );
    });
});

describe('Validation', function () {
    test('customer validation errors are handled correctly', function () {
        $testUser = createTestUserForIntegration();
        $this->actingAs($testUser);

        $response = $this->post('/customers', []);
        $response->assertSessionHasErrors([
            'name', 'email', 'password', 'subway_card', 'birth_date',
        ]);

        $existingCustomer = Customer::factory()->create(['email' => 'existing@test.com']);

        $response = $this->post('/customers', [
            'name' => 'Test Customer',
            'email' => 'existing@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subway_card' => '1234567890',
            'birth_date' => '1990-01-01',
        ]);
        $response->assertSessionHasErrors(['email']);

        $response = $this->post('/customers', [
            'name' => 'Test Customer',
            'email' => 'new@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subway_card' => $existingCustomer->subway_card,
            'birth_date' => '1990-01-01',
        ]);
        $response->assertSessionHasErrors(['subway_card']);

        $response = $this->post('/customers', [
            'name' => 'Test Customer',
            'email' => 'test@new.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
            'subway_card' => '9876543210',
            'birth_date' => '1990-01-01',
        ]);
        $response->assertSessionHasErrors(['password']);
    });
});
