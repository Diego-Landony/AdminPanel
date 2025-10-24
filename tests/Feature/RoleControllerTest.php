<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ========== TESTS DE PROTECCIÓN DE RUTAS (FASE 2) ==========

test('usuario sin permiso roles.view no puede acceder al índice de roles', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'viewer', 'description' => 'Viewer']); // Rol sin permisos
    $user->roles()->attach($role);

    $response = $this->actingAs($user)->get(route('roles.index'));

    $response->assertRedirect(route('no-access')); // Usuario con rol pero sin permisos
    $response->assertSessionHas('error');
});

test('usuario sin permiso roles.edit no puede acceder a edit', function () {
    $user = User::factory()->create();
    $viewPerm = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'Ver', 'group' => 'roles']);
    $role = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
    $role->permissions()->attach($viewPerm);
    $user->roles()->attach($role);

    $testRole = Role::create(['name' => 'test-role', 'description' => 'Test']);

    $response = $this->actingAs($user)->get(route('roles.edit', $testRole));

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('error');
});

test('usuario sin permiso roles.delete no puede eliminar roles', function () {
    $user = User::factory()->create();
    $viewPerm = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'Ver', 'group' => 'roles']);
    $role = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
    $role->permissions()->attach($viewPerm);
    $user->roles()->attach($role);

    $testRole = Role::create(['name' => 'test-role', 'description' => 'Test', 'is_system' => false]);

    $response = $this->actingAs($user)->delete(route('roles.destroy', $testRole));

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('roles', ['id' => $testRole->id]); // Rol no eliminado
});

test('usuario con permiso roles.view puede acceder al índice', function () {
    $user = User::factory()->create();
    $viewPerm = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'Ver', 'group' => 'roles']);
    $role = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
    $role->permissions()->attach($viewPerm);
    $user->roles()->attach($role);

    $response = $this->actingAs($user)->get(route('roles.index'));

    $response->assertSuccessful();
});

test('usuario con permiso roles.edit puede acceder a edit', function () {
    $user = User::factory()->create();
    $viewPerm = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'Ver', 'group' => 'roles']);
    $editPerm = Permission::firstOrCreate(['name' => 'roles.edit'], ['display_name' => 'Editar', 'group' => 'roles']);
    $role = Role::create(['name' => 'editor', 'description' => 'Editor']);
    $role->permissions()->attach([$viewPerm->id, $editPerm->id]);
    $user->roles()->attach($role);

    $testRole = Role::create(['name' => 'test-role', 'description' => 'Test']);

    $response = $this->actingAs($user)->get(route('roles.edit', $testRole));

    $response->assertSuccessful();
});

test('admin puede acceder a todas las rutas de roles', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin', 'is_system' => true]);
    $adminUser = User::factory()->create();
    $adminUser->roles()->attach($adminRole);

    $testRole = Role::create(['name' => 'test-role', 'description' => 'Test', 'is_system' => false]);

    // Puede ver índice
    $response = $this->actingAs($adminUser)->get(route('roles.index'));
    $response->assertSuccessful();

    // Puede editar
    $response = $this->actingAs($adminUser)->get(route('roles.edit', $testRole));
    $response->assertSuccessful();

    // Puede eliminar
    $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $testRole));
    $response->assertRedirect();
    $this->assertDatabaseMissing('roles', ['id' => $testRole->id]);
});

test('no se pueden eliminar roles del sistema', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin', 'is_system' => true]);
    $adminUser = User::factory()->create();
    $adminUser->roles()->attach($adminRole);

    $systemRole = Role::create(['name' => 'system-role', 'description' => 'System', 'is_system' => true]);

    $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $systemRole));

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('roles', ['id' => $systemRole->id]); // Rol no eliminado
});

test('usuario sin roles no puede acceder a rutas de roles', function () {
    $user = User::factory()->create(); // Sin roles

    $response = $this->actingAs($user)->get(route('roles.index'));

    $response->assertRedirect(route('no-access'));
});
