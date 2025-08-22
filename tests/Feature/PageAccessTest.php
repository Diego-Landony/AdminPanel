<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

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
 * Test para la página Dashboard
 */
test('dashboard page can be accessed by authenticated user', function () {
    $testUser = createTestUser();
    $this->actingAs($testUser);

    $response = $this->get('/dashboard');
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
    
    // Test Dashboard
    $this->get('/dashboard')->assertRedirect('/login');
    
    // Test Users
    $this->get('/users')->assertRedirect('/login');
    
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
    
    // Verificar que tiene permisos (ajustado a 14 que es lo que realmente se crea)
    expect($testUser->roles->first()->permissions)->toHaveCount(14);
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

/**
 * Función helper para crear el usuario de test
 */
function createTestUser(): User
{
    // Verificar si el usuario ya existe
    $existingUser = User::where('email', 'admin@test.com')->first();
    if ($existingUser) {
        return $existingUser;
    }

    // Crear el usuario de test
    $testUser = User::create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
        'email_verified_at' => now(),
        'password' => bcrypt('admintest'),
        'timezone' => 'America/Guatemala',
    ]);

    // Buscar rol admin existente o crear uno nuevo con nombre único
    $adminRole = Role::where('name', 'admin')->first();
    
    if (!$adminRole) {
        // Crear rol admin con nombre único para evitar conflictos
        $adminRole = Role::create([
            'name' => 'admin_test_' . uniqid(),
            'description' => 'Administrador del sistema con acceso completo',
            'is_system' => true,
        ]);
    }

    // Asignar el rol admin al usuario de test
    $testUser->roles()->attach($adminRole->id);

    // Crear permisos básicos si no existen
    $permissions = [
        ['name' => 'dashboard.view', 'display_name' => 'Dashboard', 'description' => 'Ver dashboard', 'group' => 'dashboard'],
        ['name' => 'home.view', 'display_name' => 'Inicio', 'description' => 'Ver página de inicio', 'group' => 'home'],
        ['name' => 'users.view', 'display_name' => 'Usuarios', 'description' => 'Ver usuarios', 'group' => 'users'],
        ['name' => 'users.create', 'display_name' => 'Crear Usuarios', 'description' => 'Crear nuevos usuarios', 'group' => 'users'],
        ['name' => 'users.edit', 'display_name' => 'Editar Usuarios', 'description' => 'Editar usuarios existentes', 'group' => 'users'],
        ['name' => 'users.delete', 'display_name' => 'Eliminar Usuarios', 'description' => 'Eliminar usuarios', 'group' => 'users'],
        ['name' => 'roles.view', 'display_name' => 'Roles', 'description' => 'Ver roles', 'group' => 'roles'],
        ['name' => 'roles.create', 'display_name' => 'Crear Roles', 'description' => 'Crear nuevos roles', 'group' => 'roles'],
        ['name' => 'roles.edit', 'display_name' => 'Editar Roles', 'description' => 'Editar roles existentes', 'group' => 'roles'],
        ['name' => 'roles.delete', 'display_name' => 'Eliminar Roles', 'description' => 'Eliminar roles', 'group' => 'roles'],
        ['name' => 'permissions.view', 'display_name' => 'Permisos', 'description' => 'Ver permisos', 'group' => 'permissions'],
        ['name' => 'settings.view', 'display_name' => 'Configuración', 'description' => 'Ver configuración', 'group' => 'settings'],
        ['name' => 'profile.view', 'display_name' => 'Perfil', 'description' => 'Ver perfil propio', 'group' => 'profile'],
        ['name' => 'profile.edit', 'display_name' => 'Editar Perfil', 'description' => 'Editar perfil propio', 'group' => 'profile'],
    ];

    foreach ($permissions as $permission) {
        $permissionModel = Permission::firstOrCreate(['name' => $permission['name']], [
            'display_name' => $permission['display_name'],
            'description' => $permission['description'],
            'group' => $permission['group'],
        ]);

        // Asignar todos los permisos al rol admin
        $adminRole->permissions()->syncWithoutDetaching([$permissionModel->id]);
    }

    return $testUser;
}
