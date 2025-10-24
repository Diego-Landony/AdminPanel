<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin siempre tiene todos los permisos - incluso los que no existen', function () {
    // Obtener o crear rol admin
    $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin', 'is_system' => true]);
    $adminUser = User::factory()->create();
    $adminUser->roles()->attach($adminRole);

    // Admin tiene acceso a CUALQUIER permiso (bypass automático)
    expect($adminUser->isAdmin())->toBeTrue()
        ->and($adminUser->hasPermission('existing.permission'))->toBeTrue()
        ->and($adminUser->hasPermission('nonexistent.permission'))->toBeTrue()
        ->and($adminUser->hasPermission('future.page.view'))->toBeTrue()
        ->and($adminUser->getAllPermissions())->toBe(['*']);
});

test('usuario normal solo tiene permisos asignados a su rol', function () {
    // Crear permisos si no existen
    $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
    $perm2 = Permission::firstOrCreate(['name' => 'users.create'], ['display_name' => 'Crear', 'group' => 'users']);

    // Crear rol y usuario
    $role = Role::create(['name' => 'editor', 'description' => 'Editor', 'is_system' => false]);
    $role->permissions()->attach([$perm1->id]);

    $user = User::factory()->create();
    $user->roles()->attach($role);

    expect($user->isAdmin())->toBeFalse()
        ->and($user->hasPermission('users.view'))->toBeTrue()
        ->and($user->hasPermission('users.create'))->toBeFalse()
        ->and($user->hasPermission('nonexistent'))->toBeFalse();
});

test('métodos helper funcionan correctamente', function () {
    $perm = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
    $role = Role::create(['name' => 'viewer', 'description' => 'Viewer', 'is_system' => false]);
    $role->permissions()->attach($perm);

    $user = User::factory()->create();
    $user->roles()->attach($role);

    expect($user->hasAccessToPage('users'))->toBeTrue()
        ->and($user->canPerformAction('users', 'view'))->toBeTrue()
        ->and($user->canPerformAction('users', 'create'))->toBeFalse();
});

// ========== TESTS DE REGRESIÓN FASE 1 ==========

test('getAllPermissions retorna array plano de permisos', function () {
    $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
    $perm2 = Permission::firstOrCreate(['name' => 'users.edit'], ['display_name' => 'Editar', 'group' => 'users']);
    $perm3 = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'Ver', 'group' => 'roles']);

    $role = Role::create(['name' => 'editor', 'description' => 'Editor']);
    $role->permissions()->attach([$perm1->id, $perm2->id, $perm3->id]);

    $user = User::factory()->create();
    $user->roles()->attach($role);

    $permissions = $user->getAllPermissions();

    // Debe retornar array plano
    expect($permissions)->toBeArray()
        ->and($permissions)->toContain('users.view')
        ->and($permissions)->toContain('users.edit')
        ->and($permissions)->toContain('roles.view')
        ->and(count($permissions))->toBe(3);
});

test('usuario con múltiples roles obtiene permisos únicos', function () {
    $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
    $perm2 = Permission::firstOrCreate(['name' => 'users.edit'], ['display_name' => 'Editar', 'group' => 'users']);

    $role1 = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
    $role1->permissions()->attach([$perm1->id]);

    $role2 = Role::create(['name' => 'editor', 'description' => 'Editor']);
    $role2->permissions()->attach([$perm1->id, $perm2->id]); // Duplica users.view

    $user = User::factory()->create();
    $user->roles()->attach([$role1->id, $role2->id]);

    $permissions = $user->getAllPermissions();

    // No debe duplicar permisos
    expect($permissions)->toBeArray()
        ->and($permissions)->toContain('users.view')
        ->and($permissions)->toContain('users.edit')
        ->and(count($permissions))->toBe(2);
});

test('usuario sin roles solo puede acceder al dashboard', function () {
    $user = User::factory()->create();

    expect($user->roles()->count())->toBe(0)
        ->and($user->hasPermission('dashboard.view'))->toBeFalse() // No tiene permisos reales
        ->and($user->getAllPermissions())->toBe([]); // Array vacío
});
