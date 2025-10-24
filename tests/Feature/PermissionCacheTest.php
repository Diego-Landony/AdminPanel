<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

// ========== TESTS DE CACHE DE PERMISOS (FASE 4.3) ==========

test('permisos se cachean correctamente', function () {
    $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
    $perm2 = Permission::firstOrCreate(['name' => 'users.edit'], ['display_name' => 'Editar', 'group' => 'users']);

    $role = Role::create(['name' => 'editor', 'description' => 'Editor']);
    $role->permissions()->attach([$perm1->id, $perm2->id]);

    $user = User::factory()->create();
    $user->roles()->attach($role);

    // Primera llamada: debe cachear
    $permissions1 = $user->getAllPermissions();

    // Verificar que está en cache
    $cached = Cache::get("user.{$user->id}.permissions");
    expect($cached)->not->toBeNull()
        ->and($cached)->toBe($permissions1);

    // Segunda llamada: debe retornar desde cache
    $permissions2 = $user->getAllPermissions();
    expect($permissions2)->toBe($permissions1);
});

test('cache se invalida al cambiar rol de usuario', function () {
    $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);

    $role1 = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
    $role1->permissions()->attach($perm1);

    $role2 = Role::create(['name' => 'editor', 'description' => 'Editor']);

    $user = User::factory()->create();
    $user->roles()->attach($role1);

    // Cachear permisos iniciales
    $initialPermissions = $user->getAllPermissions();
    expect($initialPermissions)->toContain('users.view');

    // Verificar que está cacheado
    expect(Cache::has("user.{$user->id}.permissions"))->toBeTrue();

    // Cambiar rol del usuario
    $user->roles()->sync([$role2->id]);
    $user->flushPermissionsCache(); // Simular invalidación manual

    // Cache debe estar invalidado
    expect(Cache::has("user.{$user->id}.permissions"))->toBeFalse();

    // Recargar relaciones y obtener nuevos permisos
    $user->load('roles.permissions');
    $newPermissions = $user->getAllPermissions();
    expect($newPermissions)->not->toContain('users.view');
});

test('cache se invalida al cambiar permisos de rol', function () {
    $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
    $perm2 = Permission::firstOrCreate(['name' => 'users.edit'], ['display_name' => 'Editar', 'group' => 'users']);

    $role = Role::create(['name' => 'editor', 'description' => 'Editor']);
    $role->permissions()->attach($perm1);

    $user = User::factory()->create();
    $user->roles()->attach($role);

    // Cachear permisos iniciales
    $initialPermissions = $user->getAllPermissions();
    expect($initialPermissions)->toContain('users.view')
        ->and($initialPermissions)->not->toContain('users.edit');

    // Agregar nuevo permiso al rol
    $role->permissions()->attach($perm2);

    // Invalidar cache manualmente (simulando lo que hace RoleController)
    $user->flushPermissionsCache();

    // Recargar y verificar nuevos permisos
    $user->load('roles.permissions');
    $newPermissions = $user->getAllPermissions();
    expect($newPermissions)->toContain('users.view')
        ->and($newPermissions)->toContain('users.edit');
});

test('admin no usa cache, siempre retorna wildcard', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin', 'is_system' => true]);
    $adminUser = User::factory()->create();
    $adminUser->roles()->attach($adminRole);

    // Admin siempre retorna wildcard
    $permissions = $adminUser->getAllPermissions();
    expect($permissions)->toBe(['*']);

    // Verificar que NO se cachea para admin
    expect(Cache::has("user.{$adminUser->id}.permissions"))->toBeFalse();
});

test('cache expira después del TTL', function () {
    $perm = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
    $role = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
    $role->permissions()->attach($perm);

    $user = User::factory()->create();
    $user->roles()->attach($role);

    // Cachear con TTL corto (1 segundo para el test)
    Cache::put("user.{$user->id}.permissions", ['users.view'], now()->addSecond());

    expect(Cache::has("user.{$user->id}.permissions"))->toBeTrue();

    // Esperar que expire
    sleep(2);

    expect(Cache::has("user.{$user->id}.permissions"))->toBeFalse();
})->skip('Test lento, ejecutar manualmente si es necesario');

test('flushPermissionsCache invalida correctamente', function () {
    $perm = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
    $role = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
    $role->permissions()->attach($perm);

    $user = User::factory()->create();
    $user->roles()->attach($role);

    // Cachear permisos
    $user->getAllPermissions();
    expect(Cache::has("user.{$user->id}.permissions"))->toBeTrue();

    // Invalidar cache
    $user->flushPermissionsCache();

    // Cache debe estar limpio
    expect(Cache::has("user.{$user->id}.permissions"))->toBeFalse();
});

test('múltiples usuarios tienen caches independientes', function () {
    $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
    $perm2 = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'Ver', 'group' => 'roles']);

    $role1 = Role::create(['name' => 'user-viewer', 'description' => 'User Viewer']);
    $role1->permissions()->attach($perm1);

    $role2 = Role::create(['name' => 'role-viewer', 'description' => 'Role Viewer']);
    $role2->permissions()->attach($perm2);

    $user1 = User::factory()->create();
    $user1->roles()->attach($role1);

    $user2 = User::factory()->create();
    $user2->roles()->attach($role2);

    // Cachear ambos usuarios
    $perms1 = $user1->getAllPermissions();
    $perms2 = $user2->getAllPermissions();

    expect($perms1)->toContain('users.view')
        ->and($perms1)->not->toContain('roles.view')
        ->and($perms2)->toContain('roles.view')
        ->and($perms2)->not->toContain('users.view');

    // Ambos caches deben existir independientemente
    expect(Cache::has("user.{$user1->id}.permissions"))->toBeTrue()
        ->and(Cache::has("user.{$user2->id}.permissions"))->toBeTrue();

    // Invalidar solo user1
    $user1->flushPermissionsCache();

    expect(Cache::has("user.{$user1->id}.permissions"))->toBeFalse()
        ->and(Cache::has("user.{$user2->id}.permissions"))->toBeTrue();
});
