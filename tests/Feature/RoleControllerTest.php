<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Authorization Denied', function () {
    test('user without roles.view permission cannot access roles index', function () {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('roles.index'));

        $response->assertRedirect(route('no-access'));
        $response->assertSessionHas('error');
    });

    test('user without roles.edit permission cannot access edit', function () {
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

    test('user without roles.delete permission cannot delete roles', function () {
        $user = User::factory()->create();
        $viewPerm = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'Ver', 'group' => 'roles']);
        $role = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
        $role->permissions()->attach($viewPerm);
        $user->roles()->attach($role);

        $testRole = Role::create(['name' => 'test-role', 'description' => 'Test', 'is_system' => false]);

        $response = $this->actingAs($user)->delete(route('roles.destroy', $testRole));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $testRole->id]);
    });

    test('user without roles cannot access role routes', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('roles.index'));

        $response->assertRedirect(route('no-access'));
    });
});

describe('Authorization Allowed', function () {
    test('user with roles.view permission can access index', function () {
        $user = User::factory()->create();
        $viewPerm = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'Ver', 'group' => 'roles']);
        $role = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
        $role->permissions()->attach($viewPerm);
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get(route('roles.index'));

        $response->assertSuccessful();
    });

    test('user with roles.edit permission can access edit', function () {
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

    test('admin can access all role routes', function () {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin', 'is_system' => true]);
        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        $testRole = Role::create(['name' => 'test-role', 'description' => 'Test', 'is_system' => false]);

        $response = $this->actingAs($adminUser)->get(route('roles.index'));
        $response->assertSuccessful();

        $response = $this->actingAs($adminUser)->get(route('roles.edit', $testRole));
        $response->assertSuccessful();

        $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $testRole));
        $response->assertRedirect();
        $this->assertDatabaseMissing('roles', ['id' => $testRole->id]);
    });
});

describe('System Role Protection', function () {
    test('cannot delete system roles', function () {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin', 'is_system' => true]);
        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        $systemRole = Role::create(['name' => 'system-role', 'description' => 'System', 'is_system' => true]);

        $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $systemRole));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
    });
});
