<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Permission Blocking', function () {
    test('CheckUserPermissions blocks users without permission', function () {
        $perm = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'Ver', 'group' => 'roles']);
        $role = Role::create(['name' => 'limited', 'description' => 'Limited']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('roles.index'));
        $response->assertSuccessful();

        $response = $this->actingAs($user)->get(route('users.index'));
        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    });

    test('user without roles sees only no-access', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('users.index'));
        $response->assertRedirect(route('no-access'));
        $response->assertSessionHas('error');

        $response = $this->actingAs($user)->get(route('roles.index'));
        $response->assertRedirect(route('no-access'));

        $response = $this->actingAs($user)->get(route('no-access'));
        $response->assertSuccessful();
    });

    test('middleware blocks AJAX requests without permission with JSON', function () {
        $role = Role::create(['name' => 'limited', 'description' => 'Limited']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)
            ->getJson(route('users.index'));

        $response->assertForbidden();
        $response->assertJson(['error' => 'No tienes permisos para acceder a esta página. Contacta al administrador para asignar roles.']);
    });
});

describe('Permission Allowance', function () {
    test('admin bypasses all middleware checks', function () {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin', 'is_system' => true]);
        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        $response = $this->actingAs($adminUser)->get(route('users.index'));
        $response->assertSuccessful();

        $response = $this->actingAs($adminUser)->get(route('roles.index'));
        $response->assertSuccessful();

        $response = $this->actingAs($adminUser)->get(route('customers.index'));
        $response->assertSuccessful();

        $response = $this->actingAs($adminUser)->get(route('activity.index'));
        $response->assertSuccessful();
    });

    test('middleware allows access with correct permission', function () {
        $perm = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
        $role = Role::create(['name' => 'user-viewer', 'description' => 'User Viewer']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('users.index'));
        $response->assertSuccessful();
    });

    test('middleware does not interfere with unprotected routes', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/no-access');
        $response->assertSuccessful();
    });

    test('user can access home even without specific permissions', function () {
        $perm = Permission::firstOrCreate(['name' => 'home.view'], ['display_name' => 'Ver', 'group' => 'home']);
        $role = Role::create(['name' => 'basic', 'description' => 'Basic']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertSuccessful();
    });
});

describe('Redirect Behavior', function () {
    test('redirects work correctly based on context', function () {
        $userNoRoles = User::factory()->create();
        $response = $this->actingAs($userNoRoles)->get(route('users.index'));
        $response->assertRedirect(route('no-access'));

        $emptyRole = Role::create(['name' => 'empty', 'description' => 'Empty']);
        $userEmptyRole = User::factory()->create();
        $userEmptyRole->roles()->attach($emptyRole);

        $response = $this->actingAs($userEmptyRole)->get(route('users.index'));
        $response->assertRedirect(route('no-access'));
    });
});

describe('Multiple Roles', function () {
    test('user with multiple roles gets combined permissions', function () {
        $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
        $perm2 = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'Ver', 'group' => 'roles']);

        $role1 = Role::create(['name' => 'user-viewer', 'description' => 'User Viewer']);
        $role1->permissions()->attach($perm1);

        $role2 = Role::create(['name' => 'role-viewer', 'description' => 'Role Viewer']);
        $role2->permissions()->attach($perm2);

        $user = User::factory()->create();
        $user->roles()->attach([$role1->id, $role2->id]);

        $response = $this->actingAs($user)->get(route('users.index'));
        $response->assertSuccessful();

        $response = $this->actingAs($user)->get(route('roles.index'));
        $response->assertSuccessful();
    });
});

describe('Eager Loading', function () {
    test('eager loading of roles.permissions works correctly', function () {
        $perm = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'Ver', 'group' => 'users']);
        $role = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertSuccessful();
    });
});
