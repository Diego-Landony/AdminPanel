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
