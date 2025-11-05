<?php

describe('Authenticated Page Access', function () {
    test('home page can be accessed by authenticated user', function () {
        $testUser = createTestUser();
        $this->actingAs($testUser);

        $response = $this->get('/home');
        $response->assertStatus(200);
    });

    test('users page can be accessed by authenticated user', function () {
        $testUser = createTestUser();
        $this->actingAs($testUser);

        $response = $this->get('/users');
        $response->assertStatus(200);
    });

    test('create user page can be accessed by authenticated user', function () {
        $testUser = createTestUser();
        $this->actingAs($testUser);

        $response = $this->get('/users/create');
        $response->assertStatus(200);
    });

    test('roles page can be accessed by authenticated user', function () {
        $testUser = createTestUser();
        $this->actingAs($testUser);

        $response = $this->get('/roles');
        $response->assertStatus(200);
    });

    test('create role page can be accessed by authenticated user', function () {
        $testUser = createTestUser();
        $this->actingAs($testUser);

        $response = $this->get('/roles/create');
        $response->assertStatus(200);
    });

    test('customers page can be accessed by authenticated user', function () {
        $testUser = createTestUser();
        $this->actingAs($testUser);

        $response = $this->get('/customers');
        $response->assertStatus(200);
    });

    test('create customer page can be accessed by authenticated user', function () {
        $testUser = createTestUser();
        $this->actingAs($testUser);

        $response = $this->get('/customers/create');
        $response->assertStatus(200);
    });

    test('settings page can be accessed by authenticated user', function () {
        $testUser = createTestUser();
        $this->actingAs($testUser);

        $response = $this->get('/settings');
        $response->assertRedirect('/settings/profile');
    });

    test('profile page can be accessed by authenticated user', function () {
        $testUser = createTestUser();
        $this->actingAs($testUser);

        $response = $this->get('/settings/profile');
        $response->assertStatus(200);
    });
});

describe('Unauthenticated Redirects', function () {
    test('unauthenticated users are redirected to login', function () {
        $this->get('/home')->assertRedirect('/login');
        $this->get('/users')->assertRedirect('/login');
        $this->get('/customers')->assertRedirect('/login');
        $this->get('/roles')->assertRedirect('/login');
        $this->get('/settings')->assertRedirect('/login');
        $this->get('/settings/profile')->assertRedirect('/login');
    });
});

describe('Test User Verification', function () {
    test('test user has all required permissions', function () {
        $testUser = createTestUser();

        expect($testUser)->not->toBeNull();
        expect($testUser->email)->toBe('admin@test.com');

        expect($testUser->roles)->toHaveCount(1);
        expect($testUser->roles->first()->name)->toBe('admin');

        $adminRole = $testUser->roles->first();
        expect($adminRole->permissions->count())->toBeGreaterThan(0);

        $permissionNames = $adminRole->permissions->pluck('name')->toArray();
        expect($permissionNames)->toContain('home.view');
        expect($permissionNames)->toContain('users.view');
        expect($permissionNames)->toContain('customers.view');
    });
});

describe('Authentication', function () {
    test('test user can authenticate', function () {
        createTestUser();

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'admintest',
        ]);

        $response->assertRedirect('/home');

        $this->assertAuthenticated();
    });
});
