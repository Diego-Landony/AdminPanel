<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

test('sistema puede crear usuario administrador', function () {
    // Crear rol admin
    $adminRole = Role::create([
        'name' => 'admin',
        'display_name' => 'Administrador',
        'description' => 'Administrador del sistema',
        'is_system' => true,
    ]);

    // Crear permisos básicos
    $permissions = [
        'dashboard.view' => 'Ver Dashboard',
        'users.view' => 'Ver Usuarios',
        'roles.view' => 'Ver Roles',
    ];

    foreach ($permissions as $name => $displayName) {
        Permission::create([
            'name' => $name,
            'display_name' => $displayName,
            'description' => "Permiso para {$displayName}",
            'group' => 'general',
        ]);
    }

    // Asignar permisos al rol admin
    $adminRole->permissions()->attach(Permission::pluck('id'));

    // Crear usuario admin
    $adminUser = User::create([
        'name' => 'Administrador',
        'email' => 'admin@admin.com',
        'password' => bcrypt('admin'),
        'email_verified_at' => now(),
        'timezone' => 'America/Guatemala',
    ]);

    // Asignar rol admin al usuario
    $adminUser->roles()->attach($adminRole->id);

    // Verificar que el usuario existe
    expect($adminUser)->toBeInstanceOf(User::class);
    expect($adminUser->email)->toBe('admin@admin.com');
    expect($adminUser->hasRole('admin'))->toBeTrue();
    expect($adminUser->hasPermission('dashboard.view'))->toBeTrue();
});

test('sistema puede autenticar usuario admin', function () {
    // Crear rol admin
    $adminRole = Role::create([
        'name' => 'admin',
        'display_name' => 'Administrador',
        'description' => 'Administrador del sistema',
        'is_system' => true,
    ]);

    // Crear permiso para el dashboard
    $dashboardPermission = Permission::create([
        'name' => 'dashboard.view',
        'display_name' => 'Ver Dashboard',
        'description' => 'Permite acceder al dashboard del sistema',
        'group' => 'dashboard',
    ]);

    // Asignar permiso al rol
    $adminRole->permissions()->attach($dashboardPermission->id);

    // Crear usuario admin
    $adminUser = User::create([
        'name' => 'Administrador',
        'email' => 'admin@admin.com',
        'password' => bcrypt('admin'),
        'email_verified_at' => now(),
        'timezone' => 'America/Guatemala',
    ]);

    // Asignar rol admin al usuario
    $adminUser->roles()->attach($adminRole->id);

    $response = $this->post('/login', [
        'email' => 'admin@admin.com',
        'password' => 'admin',
    ]);

    $response->assertRedirect();
    $this->assertAuthenticated();
});

test('base de datos tiene estructura correcta', function () {
    // Crear rol admin
    $adminRole = Role::create([
        'name' => 'admin',
        'display_name' => 'Administrador',
        'description' => 'Administrador del sistema',
        'is_system' => true,
    ]);

    // Crear permisos básicos
    $permissions = [
        'dashboard.view' => 'Ver Dashboard',
        'users.view' => 'Ver Usuarios',
        'roles.view' => 'Ver Roles',
    ];

    foreach ($permissions as $name => $displayName) {
        Permission::create([
            'name' => $name,
            'display_name' => $displayName,
            'description' => "Permiso para {$displayName}",
            'group' => 'general',
        ]);
    }

    // Asignar permisos al rol admin
    $adminRole->permissions()->attach(Permission::pluck('id'));

    // Crear usuario admin
    $adminUser = User::create([
        'name' => 'Administrador',
        'email' => 'admin@admin.com',
        'password' => bcrypt('admin'),
        'email_verified_at' => now(),
        'timezone' => 'America/Guatemala',
    ]);

    // Asignar rol admin al usuario
    $adminUser->roles()->attach($adminRole->id);

    // Verificar que las tablas principales existen
    $this->assertDatabaseHas('users', [
        'email' => 'admin@admin.com',
    ]);

    $this->assertDatabaseHas('roles', [
        'name' => 'admin',
    ]);

    $this->assertDatabaseHas('permissions', [
        'name' => 'dashboard.view',
    ]);

    // Verificar relaciones
    $adminUser = User::where('email', 'admin@admin.com')->first();
    $adminRole = Role::where('name', 'admin')->first();

    expect($adminUser->roles)->toHaveCount(1);
    expect($adminRole->permissions)->toHaveCount(3);
});
