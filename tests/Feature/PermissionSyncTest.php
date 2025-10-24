<?php

use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ========== TESTS DE SINCRONIZACIÓN (FASE 3) ==========

test('generatePermissions retorna permisos desde config', function () {
    $service = new PermissionService;
    $permissions = $service->generatePermissions();

    expect($permissions)->toBeArray()
        ->and(count($permissions))->toBeGreaterThan(0);

    // Verificar que tenga permisos esperados del config
    $permissionNames = collect($permissions)->pluck('name');

    expect($permissionNames)->toContain('home.view')
        ->and($permissionNames)->toContain('users.view')
        ->and($permissionNames)->toContain('users.create')
        ->and($permissionNames)->toContain('users.edit')
        ->and($permissionNames)->toContain('users.delete')
        ->and($permissionNames)->toContain('menu.categories.view')
        ->and($permissionNames)->toContain('menu.products.view')
        ->and($permissionNames)->toContain('menu.promotions.create');
});

test('syncPermissions crea permisos en base de datos', function () {
    $service = new PermissionService;

    $result = $service->syncPermissions();

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['total_pages', 'total_permissions', 'created', 'updated', 'deleted'])
        ->and($result['created'])->toBeGreaterThan(0);

    // Verificar que los permisos existen en DB
    expect(Permission::where('name', 'users.view')->exists())->toBeTrue()
        ->and(Permission::where('name', 'menu.categories.create')->exists())->toBeTrue();
});

test('syncPermissions no duplica permisos existentes', function () {
    $service = new PermissionService;

    // Primera sincronización
    $result1 = $service->syncPermissions();
    $totalAfterFirstSync = Permission::count();

    // Segunda sincronización
    $result2 = $service->syncPermissions();
    $totalAfterSecondSync = Permission::count();

    expect($result2['created'])->toBe(0) // No crea nuevos
        ->and($totalAfterFirstSync)->toBe($totalAfterSecondSync) // Mismo total
        ->and($result2['updated'])->toBeGreaterThan(0); // Actualiza existentes
});

test('rol admin recibe todos los permisos después de sync', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin', 'is_system' => true]);

    $service = new PermissionService;
    $service->syncPermissions();

    // Asignar todos los permisos al admin
    $allPermissionIds = Permission::pluck('id');
    $adminRole->permissions()->sync($allPermissionIds);

    $adminRole->refresh();

    expect($adminRole->permissions()->count())->toBeGreaterThan(20); // Tiene muchos permisos
});

test('getPagesConfiguration retorna estructura correcta', function () {
    $service = new PermissionService;
    $config = $service->getPagesConfiguration();

    expect($config)->toBeArray()
        ->and($config)->toHaveKey('users')
        ->and($config['users'])->toHaveKeys(['name', 'display_name', 'description', 'group', 'actions', 'permissions'])
        ->and($config['users']['permissions'])->toContain('users.view')
        ->and($config['users']['permissions'])->toContain('users.create')
        ->and($config['users']['permissions'])->toContain('users.edit')
        ->and($config['users']['permissions'])->toContain('users.delete');
});

test('getGroupDisplayName retorna nombre legible', function () {
    $service = new PermissionService;

    expect($service->getGroupDisplayName('users'))->toBe('Usuarios')
        ->and($service->getGroupDisplayName('menu.categories'))->toBe('Categorías')
        ->and($service->getGroupDisplayName('activity'))->toBe('Actividad');
});

test('permisos incluyen estructura de menú anidada', function () {
    $service = new PermissionService;
    $permissions = $service->generatePermissions();
    $permissionNames = collect($permissions)->pluck('name');

    // Verificar permisos de menú
    expect($permissionNames)->toContain('menu.categories.view')
        ->and($permissionNames)->toContain('menu.products.view')
        ->and($permissionNames)->toContain('menu.sections.view')
        ->and($permissionNames)->toContain('menu.combos.view')
        ->and($permissionNames)->toContain('menu.promotions.view');
});
