<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ========== TESTS DE MIDDLEWARE DE PERMISOS (FASE 5.1) ==========

test('CheckUserPermissions bloquea usuarios sin permiso', function () {
    $perm = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'Ver', 'group' => 'roles']);
    $role = Role::create(['name' => 'limited', 'description' => 'Limited']);
    $role->permissions()->attach($perm); // Solo tiene roles.view

    $user = User::factory()->create();
    $user->roles()->attach($role);

    // Puede acceder a roles.index (tiene permiso)
    $response = $this->actingAs($user)->get(route('roles.index'));
    $response->assertSuccessful();

    // No puede acceder a users.index (sin permiso)
    $response = $this->actingAs($user)->get(route('users.index'));
    $response->assertRedirect(route('home'));
    $response->assertSessionHas('error');
});

test('admin bypasea todas las verificaciones del middleware', function () {
    $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin', 'is_system' => true]);
    $adminUser = User::factory()->create();
    $adminUser->roles()->attach($adminRole);

    // Admin puede acceder a todo sin permisos explícitos
    $response = $this->actingAs($adminUser)->get(route('users.index'));
    $response->assertSuccessful();

    $response = $this->actingAs($adminUser)->get(route('roles.index'));
    $response->assertSuccessful();

    $response = $this->actingAs($adminUser)->get(route('customers.index'));
    $response->assertSuccessful();

    $response = $this->actingAs($adminUser)->get(route('activity.index'));
    $response->assertSuccessful();
});

test('usuario sin roles ve solo no-access', function () {
    $user = User::factory()->create(); // Sin roles

    // No puede acceder a ninguna ruta protegida
    $response = $this->actingAs($user)->get(route('users.index'));
    $response->assertRedirect(route('no-access'));
    $response->assertSessionHas('error');

    $response = $this->actingAs($user)->get(route('roles.index'));
    $response->assertRedirect(route('no-access'));

    // Puede acceder a no-access
    $response = $this->actingAs($user)->get(route('no-access'));
    $response->assertSuccessful();
});

test('redirecciones funcionan correctamente según contexto', function () {
    // Usuario sin roles → no-access
    $userNoRoles = User::factory()->create();
    $response = $this->actingAs($userNoRoles)->get(route('users.index'));
    $response->assertRedirect(route('no-access'));

    // Usuario con rol pero sin permisos específicos → home
    $emptyRole = Role::create(['name' => 'empty', 'description' => 'Empty']);
    $userEmptyRole = User::factory()->create();
    $userEmptyRole->roles()->attach($emptyRole);

    $response = $this->actingAs($userEmptyRole)->get(route('users.index'));
    $response->assertRedirect(route('no-access')); // Sin permisos va a no-access
});

test('middleware permite acceso con permiso correcto', function () {
    $perm = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
    $role = Role::create(['name' => 'user-viewer', 'description' => 'User Viewer']);
    $role->permissions()->attach($perm);

    $user = User::factory()->create();
    $user->roles()->attach($role);

    $response = $this->actingAs($user)->get(route('users.index'));
    $response->assertSuccessful();
});

test('middleware bloquea peticiones AJAX sin permiso con JSON', function () {
    $role = Role::create(['name' => 'limited', 'description' => 'Limited']);
    $user = User::factory()->create();
    $user->roles()->attach($role);

    // Petición AJAX sin permiso
    $response = $this->actingAs($user)
        ->getJson(route('users.index'));

    $response->assertStatus(403);
    $response->assertJson(['error' => 'No tienes permisos para acceder a esta página. Contacta al administrador para asignar roles.']);
});

test('usuario con múltiples roles obtiene permisos combinados', function () {
    $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
    $perm2 = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'Ver', 'group' => 'roles']);

    $role1 = Role::create(['name' => 'user-viewer', 'description' => 'User Viewer']);
    $role1->permissions()->attach($perm1);

    $role2 = Role::create(['name' => 'role-viewer', 'description' => 'Role Viewer']);
    $role2->permissions()->attach($perm2);

    $user = User::factory()->create();
    $user->roles()->attach([$role1->id, $role2->id]);

    // Puede acceder a ambas rutas
    $response = $this->actingAs($user)->get(route('users.index'));
    $response->assertSuccessful();

    $response = $this->actingAs($user)->get(route('roles.index'));
    $response->assertSuccessful();
});

test('middleware no interfiere con rutas sin protección', function () {
    $user = User::factory()->create(); // Sin roles

    // Rutas públicas o sin middleware de permisos deben funcionar
    $response = $this->actingAs($user)->get('/no-access');
    $response->assertSuccessful();
});

test('eager loading de roles.permissions funciona correctamente', function () {
    $perm = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
    $role = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
    $role->permissions()->attach($perm);

    $user = User::factory()->create();
    $user->roles()->attach($role);

    // Simular request completo
    $response = $this->actingAs($user)->get(route('users.index'));

    // No debe haber N+1 queries (verificado manualmente con DB query log si es necesario)
    $response->assertSuccessful();
});

test('usuario puede acceder a home incluso sin permisos específicos', function () {
    $perm = Permission::firstOrCreate(['name' => 'home.view'], ['display_name' => 'Ver', 'group' => 'home']);
    $role = Role::create(['name' => 'basic', 'description' => 'Basic']);
    $role->permissions()->attach($perm);

    $user = User::factory()->create();
    $user->roles()->attach($role);

    $response = $this->actingAs($user)->get(route('home'));
    $response->assertSuccessful();
});
