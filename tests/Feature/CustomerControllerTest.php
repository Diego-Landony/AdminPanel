<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Customer Listing', function () {
    test('displays customers list', function () {
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

    test('can search customers', function () {
        Customer::factory()->create(['name' => 'Juan Pérez']);
        Customer::factory()->create(['name' => 'María González']);
        Customer::factory()->create(['email' => 'test@example.com']);

        $response = $this->get('/customers?search=Juan');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('customers/index')
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Juan Pérez')
        );
    });

    test('can sort customers', function () {
        Customer::factory()->create(['name' => 'Ana López']);
        Customer::factory()->create(['name' => 'Carlos Ruiz']);
        Customer::factory()->create(['name' => 'Beatriz Silva']);

        $response = $this->get('/customers?sort_field=name&sort_direction=asc');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('customers/index')
            ->where('customers.data.0.name', 'Ana López')
            ->where('customers.data.1.name', 'Beatriz Silva')
            ->where('customers.data.2.name', 'Carlos Ruiz')
        );
    });
});

describe('Customer Creation', function () {
    test('renders create page', function () {
        $response = $this->get('/customers/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('customers/create'));
    });

    test('can create new customer', function () {
        $customerData = [
            'name' => 'Nuevo Cliente',
            'email' => 'nuevo@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subway_card' => '1234567890',
            'birth_date' => '1990-05-15',
            'gender' => 'masculino',
            'phone' => '+502 1234-5678',
            'address' => 'Dirección de prueba',
            'nit' => '12345678-9',
        ];

        $response = $this->post('/customers', $customerData);

        $response->assertRedirect('/customers');
        $response->assertSessionHas('success', 'Cliente creado exitosamente');

        $this->assertDatabaseHas('customers', [
            'name' => 'Nuevo Cliente',
            'email' => 'nuevo@test.com',
            'subway_card' => '1234567890',
        ]);

        $customer = Customer::where('email', 'nuevo@test.com')->first();
        expect($customer->password)->not->toBe('password123');
        expect(\Hash::check('password123', $customer->password))->toBeTrue();
    });

    test('validates customer data', function (array $data, array $expectedErrors) {
        $response = $this->post('/customers', $data);
        $response->assertSessionHasErrors($expectedErrors);
    })->with([
        'required fields' => [
            [],
            ['name', 'email', 'password'],
        ],
        'unique email' => [
            fn () => [
                'name' => 'Nuevo Cliente',
                'email' => Customer::factory()->create(['email' => 'existing@test.com'])->email,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'subway_card' => '1234567890',
                'birth_date' => '1990-05-15',
            ],
            ['email'],
        ],
        'unique subway card' => [
            fn () => [
                'name' => 'Nuevo Cliente',
                'email' => 'nuevo@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'subway_card' => Customer::factory()->create(['subway_card' => '1234567890'])->subway_card,
                'birth_date' => '1990-05-15',
            ],
            ['subway_card'],
        ],
    ]);
});

describe('Customer Updates', function () {
    test('renders edit page with customer data', function () {
        $customer = Customer::factory()->create([
            'name' => 'Cliente Test',
            'email' => 'cliente@test.com',
        ]);

        $response = $this->get("/customers/{$customer->id}/edit");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('customers/edit')
            ->has('customer')
            ->where('customer.name', 'Cliente Test')
            ->where('customer.email', 'cliente@test.com')
        );
    });

    test('can update customer information', function () {
        $customer = Customer::factory()->create([
            'name' => 'Cliente Original',
            'email' => 'original@test.com',
        ]);

        $updateData = [
            'name' => 'Cliente Actualizado',
            'email' => 'actualizado@test.com',
            'subway_card' => $customer->subway_card,
            'birth_date' => $customer->birth_date->format('Y-m-d'),
            'phone' => '+502 9876-5432',
        ];

        $response = $this->put("/customers/{$customer->id}", $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Cliente actualizado exitosamente');

        $customer->refresh();
        expect($customer->name)->toBe('Cliente Actualizado');
        expect($customer->email)->toBe('actualizado@test.com');
        expect($customer->phone)->toBe('+502 9876-5432');
    });

    test('can update customer password', function () {
        $customer = Customer::factory()->create();
        $originalPassword = $customer->password;

        $updateData = [
            'name' => $customer->name,
            'email' => $customer->email,
            'subway_card' => $customer->subway_card,
            'birth_date' => $customer->birth_date->format('Y-m-d'),
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->put("/customers/{$customer->id}", $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Cliente actualizado exitosamente');

        $customer->refresh();
        expect($customer->password)->not->toBe($originalPassword);
        expect(\Hash::check('newpassword123', $customer->password))->toBeTrue();
    });

    test('resets email verification when email is changed', function () {
        $customer = Customer::factory()->create([
            'email' => 'original@test.com',
            'email_verified_at' => now(),
        ]);

        expect($customer->email_verified_at)->not->toBeNull();

        $updateData = [
            'name' => $customer->name,
            'email' => 'newemail@test.com',
            'subway_card' => $customer->subway_card,
            'birth_date' => $customer->birth_date->format('Y-m-d'),
            'gender' => $customer->gender,
            'phone' => $customer->phone,
        ];

        $response = $this->put("/customers/{$customer->id}", $updateData);

        $response->assertRedirect();

        $customer->refresh();
        expect($customer->email)->toBe('newemail@test.com');
        expect($customer->email_verified_at)->toBeNull();
    });
});

describe('Customer Deletion', function () {
    test('can delete customer', function () {
        $customer = Customer::factory()->create(['name' => 'Cliente a Eliminar']);

        $response = $this->delete("/customers/{$customer->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', "Cliente 'Cliente a Eliminar' eliminado exitosamente");

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    });
});

describe('Authorization', function () {
    test('unauthenticated users cannot access customer routes', function () {
        auth()->logout();

        $customer = Customer::factory()->create();

        $this->get('/customers')->assertRedirect('/login');
        $this->get('/customers/create')->assertRedirect('/login');
        $this->get("/customers/{$customer->id}/edit")->assertRedirect('/login');
        $this->post('/customers', [])->assertRedirect('/login');
        $this->put("/customers/{$customer->id}", [])->assertRedirect('/login');
        $this->delete("/customers/{$customer->id}")->assertRedirect('/login');
    });
});
