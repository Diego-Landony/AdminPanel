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

/**
 * Creates a test user with specific permissions only
 *
 * @param  array|null  $permissions  Array of permission names (e.g., ['users.view', 'users.edit'])
 */
function createTestUserWithPermissions(?array $permissions = null): User
{
    $user = User::factory()->create();

    $adminRole = Role::firstOrCreate(['name' => 'test_admin'], [
        'display_name' => 'Test Administrator',
        'is_system' => false,
    ]);

    if ($permissions !== null) {
        $permissionIds = collect($permissions)->map(function ($permName) {
            return Permission::firstOrCreate(['name' => $permName], [
                'display_name' => ucwords(str_replace(['.', '_'], ' ', $permName)),
                'group' => explode('.', $permName)[0] ?? 'general',
            ])->id;
        });

        $adminRole->permissions()->sync($permissionIds);
    }

    $user->roles()->attach($adminRole);
    $user->load('roles.permissions');

    return $user;
}

/**
 * Creates a test role with specific permissions
 *
 * @param  string  $name  Role name
 * @param  array  $permissions  Array of permission names
 */
function createTestRole(string $name, array $permissions = []): Role
{
    $role = Role::firstOrCreate(['name' => $name], [
        'display_name' => ucwords(str_replace('_', ' ', $name)),
        'is_system' => false,
    ]);

    if (! empty($permissions)) {
        $permissionIds = collect($permissions)->map(function ($permName) {
            return Permission::firstOrCreate(['name' => $permName], [
                'display_name' => ucwords(str_replace(['.', '_'], ' ', $permName)),
                'group' => explode('.', $permName)[0] ?? 'general',
            ])->id;
        });

        $role->permissions()->sync($permissionIds);
    }

    return $role;
}

/**
 * Creates a test permission
 *
 * @param  string  $name  Permission name (e.g., 'users.view')
 * @param  array  $attributes  Additional attributes
 */
function createTestPermission(string $name, array $attributes = []): Permission
{
    $defaults = [
        'display_name' => ucwords(str_replace(['.', '_'], ' ', $name)),
        'group' => explode('.', $name)[0] ?? 'general',
    ];

    return Permission::firstOrCreate(
        ['name' => $name],
        array_merge($defaults, $attributes)
    );
}

/**
 * Creates a complete menu structure for combo testing with products and variants
 *
 * @return array{comboCategory, subsCategory, products, bebida}
 */
function createMenuStructureForComboTests(): array
{
    $comboCategory = \App\Models\Menu\Category::factory()->create([
        'is_combo_category' => true,
        'is_active' => true,
        'uses_variants' => false,
    ]);

    $subsCategory = \App\Models\Menu\Category::factory()->create([
        'name' => 'Subs',
        'is_active' => true,
        'uses_variants' => true,
        'variant_definitions' => ['15cm', '30cm'],
    ]);

    $products = [
        \App\Models\Menu\Product::factory()->create([
            'category_id' => $subsCategory->id,
            'name' => 'Italian BMT',
            'has_variants' => true,
            'is_active' => true,
        ]),
        \App\Models\Menu\Product::factory()->create([
            'category_id' => $subsCategory->id,
            'name' => 'Pollo Teriyaki',
            'has_variants' => true,
            'is_active' => true,
        ]),
        \App\Models\Menu\Product::factory()->create([
            'category_id' => $subsCategory->id,
            'name' => 'Atún',
            'has_variants' => true,
            'is_active' => true,
        ]),
    ];

    foreach ($products as $product) {
        \App\Models\Menu\ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '15cm',
            'size' => '15cm',
            'precio_pickup_capital' => 30.00,
            'precio_domicilio_capital' => 35.00,
            'precio_pickup_interior' => 32.00,
            'precio_domicilio_interior' => 37.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        \App\Models\Menu\ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => '30cm',
            'size' => '30cm',
            'precio_pickup_capital' => 60.00,
            'precio_domicilio_capital' => 65.00,
            'precio_pickup_interior' => 62.00,
            'precio_domicilio_interior' => 67.00,
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    $bebida = \App\Models\Menu\Product::factory()->create([
        'name' => 'Coca Cola Personal',
        'has_variants' => false,
        'is_active' => true,
        'precio_pickup_capital' => 15.00,
        'precio_domicilio_capital' => 18.00,
        'precio_pickup_interior' => 16.00,
        'precio_domicilio_interior' => 19.00,
    ]);

    return [
        'comboCategory' => $comboCategory,
        'subsCategory' => $subsCategory,
        'products' => $products,
        'bebida' => $bebida,
    ];
}
