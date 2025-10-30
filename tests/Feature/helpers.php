<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

/**
 * Obtiene el usuario de prueba creado por las migraciones (admin@test.com)
 * La migración inicial ya crea este usuario con rol admin y permisos básicos
 */
function createTestUser(): User
{
    // Obtener el usuario de test que fue creado por la migración
    $testUser = User::where('email', 'admin@test.com')->firstOrFail();

    // Asegurar que tenga el rol admin
    $adminRole = Role::where('name', 'admin')->firstOrFail();

    // Asegurar permisos adicionales que algunos tests necesitan
    $additionalPermissions = [
        'customers.view' => ['display_name' => 'Ver Clientes', 'description' => 'Ver clientes', 'group' => 'customers'],
        'customers.create' => ['display_name' => 'Crear Clientes', 'description' => 'Crear nuevos clientes', 'group' => 'customers'],
        'customers.edit' => ['display_name' => 'Editar Clientes', 'description' => 'Editar clientes existentes', 'group' => 'customers'],
        'customers.delete' => ['display_name' => 'Eliminar Clientes', 'description' => 'Eliminar clientes', 'group' => 'customers'],
        'customer-types.view' => ['display_name' => 'Ver Tipos Cliente', 'description' => 'Ver tipos de cliente', 'group' => 'customer-types'],
        'customer-types.create' => ['display_name' => 'Crear Tipos Cliente', 'description' => 'Crear tipos de cliente', 'group' => 'customer-types'],
        'customer-types.edit' => ['display_name' => 'Editar Tipos Cliente', 'description' => 'Editar tipos de cliente', 'group' => 'customer-types'],
        'customer-types.delete' => ['display_name' => 'Eliminar Tipos Cliente', 'description' => 'Eliminar tipos de cliente', 'group' => 'customer-types'],
        'activity.view' => ['display_name' => 'Ver Actividad', 'description' => 'Ver actividad del sistema', 'group' => 'activity'],
        'settings.edit' => ['display_name' => 'Editar Configuración', 'description' => 'Editar configuración', 'group' => 'settings'],
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
        'menu.combos.view' => ['display_name' => 'Ver Combos', 'description' => 'Ver combos del menú', 'group' => 'menu'],
        'menu.combos.create' => ['display_name' => 'Crear Combos', 'description' => 'Crear combos', 'group' => 'menu'],
        'menu.combos.edit' => ['display_name' => 'Editar Combos', 'description' => 'Editar combos', 'group' => 'menu'],
        'menu.combos.delete' => ['display_name' => 'Eliminar Combos', 'description' => 'Eliminar combos', 'group' => 'menu'],
    ];

    // Agregar permisos adicionales si no existen
    $permissionIds = [];
    foreach ($additionalPermissions as $name => $details) {
        $permission = Permission::firstOrCreate(
            ['name' => $name],
            $details
        );
        $permissionIds[] = $permission->id;
    }

    // Asignar permisos adicionales al rol admin
    $adminRole->permissions()->syncWithoutDetaching($permissionIds);

    // Cargar relaciones
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
