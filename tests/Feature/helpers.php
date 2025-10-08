<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

/**
 * Crea un usuario de prueba completo con rol admin y todos los permisos para los tests.
 */
function createTestUser(): User
{
    // Siempre crear usuario fresco para evitar cache de permisos
    $existingUser = User::where('email', 'admin@test.com')->first();
    if ($existingUser) {
        $existingUser->roles()->detach();
        $existingUser->delete();
    }

    // Crear el usuario de test
    $testUser = User::create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
        'email_verified_at' => now(),
        'password' => bcrypt('admintest'),
        'timezone' => 'America/Guatemala',
    ]);

    // Crear o limpiar rol admin
    $adminRole = Role::where('name', 'admin')->first();
    if ($adminRole) {
        $adminRole->permissions()->detach();
    } else {
        $adminRole = Role::create([
            'name' => 'admin',
            'description' => 'Administrador del sistema con acceso completo',
            'is_system' => true,
        ]);
    }

    // Asegurar que existan todos los permisos necesarios - FORZAR CREACIÓN
    $requiredPermissions = [
        'dashboard.view' => ['display_name' => 'Dashboard', 'description' => 'Ver dashboard', 'group' => 'dashboard'],
        'home.view' => ['display_name' => 'Inicio', 'description' => 'Ver página de inicio', 'group' => 'home'],
        'users.view' => ['display_name' => 'Ver Usuarios', 'description' => 'Ver usuarios', 'group' => 'users'],
        'users.create' => ['display_name' => 'Crear Usuarios', 'description' => 'Crear nuevos usuarios', 'group' => 'users'],
        'users.edit' => ['display_name' => 'Editar Usuarios', 'description' => 'Editar usuarios existentes', 'group' => 'users'],
        'users.delete' => ['display_name' => 'Eliminar Usuarios', 'description' => 'Eliminar usuarios', 'group' => 'users'],
        'customers.view' => ['display_name' => 'Ver Clientes', 'description' => 'Ver clientes', 'group' => 'customers'],
        'customers.create' => ['display_name' => 'Crear Clientes', 'description' => 'Crear nuevos clientes', 'group' => 'customers'],
        'customers.edit' => ['display_name' => 'Editar Clientes', 'description' => 'Editar clientes existentes', 'group' => 'customers'],
        'customers.delete' => ['display_name' => 'Eliminar Clientes', 'description' => 'Eliminar clientes', 'group' => 'customers'],
        'customer-types.view' => ['display_name' => 'Ver Tipos Cliente', 'description' => 'Ver tipos de cliente', 'group' => 'customer-types'],
        'customer-types.create' => ['display_name' => 'Crear Tipos Cliente', 'description' => 'Crear tipos de cliente', 'group' => 'customer-types'],
        'customer-types.edit' => ['display_name' => 'Editar Tipos Cliente', 'description' => 'Editar tipos de cliente', 'group' => 'customer-types'],
        'customer-types.delete' => ['display_name' => 'Eliminar Tipos Cliente', 'description' => 'Eliminar tipos de cliente', 'group' => 'customer-types'],
        'roles.view' => ['display_name' => 'Ver Roles', 'description' => 'Ver roles', 'group' => 'roles'],
        'roles.create' => ['display_name' => 'Crear Roles', 'description' => 'Crear nuevos roles', 'group' => 'roles'],
        'roles.edit' => ['display_name' => 'Editar Roles', 'description' => 'Editar roles existentes', 'group' => 'roles'],
        'roles.delete' => ['display_name' => 'Eliminar Roles', 'description' => 'Eliminar roles', 'group' => 'roles'],
        'activity.view' => ['display_name' => 'Ver Actividad', 'description' => 'Ver actividad del sistema', 'group' => 'activity'],
        'settings.view' => ['display_name' => 'Ver Configuración', 'description' => 'Ver configuración', 'group' => 'settings'],
        'settings.edit' => ['display_name' => 'Editar Configuración', 'description' => 'Editar configuración', 'group' => 'settings'],
        'profile.view' => ['display_name' => 'Ver Perfil', 'description' => 'Ver perfil propio', 'group' => 'profile'],
        'profile.edit' => ['display_name' => 'Editar Perfil', 'description' => 'Editar perfil propio', 'group' => 'profile'],
        // Permisos del Menú
        'menu.categories.view' => ['display_name' => 'Ver Categorías', 'description' => 'Ver categorías del menú', 'group' => 'menu'],
        'menu.categories.create' => ['display_name' => 'Crear Categorías', 'description' => 'Crear categorías', 'group' => 'menu'],
        'menu.categories.edit' => ['display_name' => 'Editar Categorías', 'description' => 'Editar categorías', 'group' => 'menu'],
        'menu.categories.delete' => ['display_name' => 'Eliminar Categorías', 'description' => 'Eliminar categorías', 'group' => 'menu'],
        'menu.products.view' => ['display_name' => 'Ver Productos', 'description' => 'Ver productos del menú', 'group' => 'menu'],
        'menu.products.create' => ['display_name' => 'Crear Productos', 'description' => 'Crear productos', 'group' => 'menu'],
        'menu.products.edit' => ['display_name' => 'Editar Productos', 'description' => 'Editar productos', 'group' => 'menu'],
        'menu.products.delete' => ['display_name' => 'Eliminar Productos', 'description' => 'Eliminar productos', 'group' => 'menu'],
        'menu.variants.view' => ['display_name' => 'Ver Variantes', 'description' => 'Ver variantes de productos', 'group' => 'menu'],
        'menu.variants.create' => ['display_name' => 'Crear Variantes', 'description' => 'Crear variantes', 'group' => 'menu'],
        'menu.variants.edit' => ['display_name' => 'Editar Variantes', 'description' => 'Editar variantes', 'group' => 'menu'],
        'menu.variants.delete' => ['display_name' => 'Eliminar Variantes', 'description' => 'Eliminar variantes', 'group' => 'menu'],
        'menu.sections.view' => ['display_name' => 'Ver Secciones', 'description' => 'Ver secciones del menú', 'group' => 'menu'],
        'menu.sections.create' => ['display_name' => 'Crear Secciones', 'description' => 'Crear secciones', 'group' => 'menu'],
        'menu.sections.edit' => ['display_name' => 'Editar Secciones', 'description' => 'Editar secciones', 'group' => 'menu'],
        'menu.sections.delete' => ['display_name' => 'Eliminar Secciones', 'description' => 'Eliminar secciones', 'group' => 'menu'],
        'menu.promotions.view' => ['display_name' => 'Ver Promociones', 'description' => 'Ver promociones del menú', 'group' => 'menu'],
        'menu.promotions.create' => ['display_name' => 'Crear Promociones', 'description' => 'Crear promociones', 'group' => 'menu'],
        'menu.promotions.edit' => ['display_name' => 'Editar Promociones', 'description' => 'Editar promociones', 'group' => 'menu'],
        'menu.promotions.delete' => ['display_name' => 'Eliminar Promociones', 'description' => 'Eliminar promociones', 'group' => 'menu'],
    ];

    // Crear permisos de forma más directa
    $permissionIds = [];
    foreach ($requiredPermissions as $name => $details) {
        $permission = Permission::updateOrCreate(
            ['name' => $name],
            $details
        );
        $permissionIds[] = $permission->id;
    }

    // Asignar TODOS los permisos al rol admin
    $adminRole->permissions()->sync($permissionIds);

    // Asignar el rol admin al usuario de test
    $testUser->roles()->sync([$adminRole->id]);

    // Verificar que todo esté correcto
    $testUser->load('roles.permissions');

    return $testUser;
}

/**
 * Alias para createTestUser() para compatibilidad con tests de integración
 */
function createTestUserForIntegration(): User
{
    return createTestUser();
}
