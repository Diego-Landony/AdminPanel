<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

/**
 * Crea un usuario de prueba completo con rol admin y todos los permisos para los tests.
 *
 * @return \App\Models\User
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

    // Asegurar que existan todos los permisos necesarios incluyendo customers
    $requiredPermissions = [
        ['name' => 'dashboard.view', 'display_name' => 'Dashboard', 'description' => 'Ver dashboard', 'group' => 'dashboard'],
        ['name' => 'home.view', 'display_name' => 'Inicio', 'description' => 'Ver página de inicio', 'group' => 'home'],
        ['name' => 'users.view', 'display_name' => 'Usuarios', 'description' => 'Ver usuarios', 'group' => 'users'],
        ['name' => 'users.create', 'display_name' => 'Crear Usuarios', 'description' => 'Crear nuevos usuarios', 'group' => 'users'],
        ['name' => 'users.edit', 'display_name' => 'Editar Usuarios', 'description' => 'Editar usuarios existentes', 'group' => 'users'],
        ['name' => 'users.delete', 'display_name' => 'Eliminar Usuarios', 'description' => 'Eliminar usuarios', 'group' => 'users'],
        ['name' => 'customers.view', 'display_name' => 'Ver Clientes', 'description' => 'Ver clientes', 'group' => 'customers'],
        ['name' => 'customers.create', 'display_name' => 'Crear Clientes', 'description' => 'Crear nuevos clientes', 'group' => 'customers'],
        ['name' => 'customers.edit', 'display_name' => 'Editar Clientes', 'description' => 'Editar clientes existentes', 'group' => 'customers'],
        ['name' => 'customers.delete', 'display_name' => 'Eliminar Clientes', 'description' => 'Eliminar clientes', 'group' => 'customers'],
        ['name' => 'roles.view', 'display_name' => 'Roles', 'description' => 'Ver roles', 'group' => 'roles'],
        ['name' => 'roles.create', 'display_name' => 'Crear Roles', 'description' => 'Crear nuevos roles', 'group' => 'roles'],
        ['name' => 'roles.edit', 'display_name' => 'Editar Roles', 'description' => 'Editar roles existentes', 'group' => 'roles'],
        ['name' => 'roles.delete', 'display_name' => 'Eliminar Roles', 'description' => 'Eliminar roles', 'group' => 'roles'],
        ['name' => 'permissions.view', 'display_name' => 'Permisos', 'description' => 'Ver permisos', 'group' => 'permissions'],
        ['name' => 'activity.view', 'display_name' => 'Actividad', 'description' => 'Ver actividad del sistema', 'group' => 'activity'],
        ['name' => 'settings.view', 'display_name' => 'Configuración', 'description' => 'Ver configuración', 'group' => 'settings'],
        ['name' => 'profile.view', 'display_name' => 'Perfil', 'description' => 'Ver perfil propio', 'group' => 'profile'],
        ['name' => 'profile.edit', 'display_name' => 'Editar Perfil', 'description' => 'Editar perfil propio', 'group' => 'profile'],
    ];

    foreach ($requiredPermissions as $permission) {
        Permission::firstOrCreate(['name' => $permission['name']], [
            'display_name' => $permission['display_name'],
            'description' => $permission['description'],
            'group' => $permission['group'],
        ]);
    }
    
    $allPermissions = Permission::all();

    // Asignar TODOS los permisos existentes al rol admin
    $permissionIds = $allPermissions->pluck('id')->toArray();
    $adminRole->permissions()->syncWithoutDetaching($permissionIds);

    return $testUser;
}

/**
 * Alias para createTestUser() para compatibilidad con tests de integración
 *
 * @return \App\Models\User
 */
function createTestUserForIntegration(): User
{
    return createTestUser();
}