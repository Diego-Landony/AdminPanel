<?php

/**
 * Test para la página Home (Inicio)
 */
test('home page can be accessed by authenticated user', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $response = $this->get('/home');
    $response->assertStatus(200);
});

/**
 * Test para la página de Usuarios
 */
test('users page can be accessed by authenticated user', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $response = $this->get('/users');
    $response->assertStatus(200);
});

/**
 * Test para la página de Crear Usuario
 */
test('create user page can be accessed by authenticated user', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $response = $this->get('/users/create');
    $response->assertStatus(200);
});

/**
 * Test para la página de Roles
 */
test('roles page can be accessed by authenticated user', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $response = $this->get('/roles');
    $response->assertStatus(200);
});

/**
 * Test para la página de Crear Rol
 */
test('create role page can be accessed by authenticated user', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $response = $this->get('/roles/create');
    $response->assertStatus(200);
});

/**
 * Test para la página de Clientes
 */
test('customers page can be accessed by authenticated user', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $response = $this->get('/customers');
    $response->assertStatus(200);
});

/**
 * Test para la página de Crear Cliente
 */
test('create customer page can be accessed by authenticated user', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $response = $this->get('/customers/create');
    $response->assertStatus(200);
});

/**
 * Test para la página de Configuración
 */
test('settings page can be accessed by authenticated user', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $response = $this->get('/settings');
    $response->assertRedirect('/settings/profile'); // La ruta /settings redirige a /settings/profile
});

/**
 * Test para la página de Perfil
 */
test('profile page can be accessed by authenticated user', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $response = $this->get('/settings/profile'); // Ruta correcta para el perfil
    $response->assertStatus(200);
});

/**
 * Test para verificar que usuarios no autenticados son redirigidos
 */
test('unauthenticated users are redirected to login', function () {
    // Test Home
    $this->get('/home')->assertRedirect('/login');

    // Test Users
    $this->get('/users')->assertRedirect('/login');

    // Test Customers
    $this->get('/customers')->assertRedirect('/login');

    // Test Roles
    $this->get('/roles')->assertRedirect('/login');

    // Test Settings
    $this->get('/settings')->assertRedirect('/login');

    // Test Profile
    $this->get('/settings/profile')->assertRedirect('/login'); // Ruta correcta
});

/**
 * Test para verificar que el usuario de test tiene todos los permisos necesarios
 */
test('test user has all required permissions', function () {
    $testUser = createTestUser();

    // Verificar que el usuario existe
    expect($testUser)->not->toBeNull();
    expect($testUser->email)->toBe('admin@test.com');

    // Verificar que tiene el rol admin
    expect($testUser->roles)->toHaveCount(1);
    expect($testUser->roles->first()->name)->toBe('admin');

    // Verificar que tiene al menos los permisos básicos del sistema
    $adminRole = $testUser->roles->first();
    expect($adminRole->permissions->count())->toBeGreaterThan(0);

    // Verificar permisos clave
    $permissionNames = $adminRole->permissions->pluck('name')->toArray();
    expect($permissionNames)->toContain('home.view');
    expect($permissionNames)->toContain('users.view');
    expect($permissionNames)->toContain('customers.view');
});

/**
 * Test para verificar que el usuario de test puede autenticarse
 */
test('test user can authenticate', function () {
    createTestUser();

    $response = $this->post('/login', [
        'email' => 'admin@test.com',
        'password' => 'admintest',
    ]);

    $response->assertRedirect('/home'); // Corregido: ahora redirige a home después del login

    // Verificar que el usuario está autenticado
    $this->assertAuthenticated();
});

// createTestUser is now defined in tests/Feature/helpers.php
