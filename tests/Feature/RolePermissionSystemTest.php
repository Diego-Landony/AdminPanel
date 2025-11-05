<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Admin User Permissions', function () {
    test('admin always has all permissions including nonexistent ones', function () {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin', 'is_system' => true]);
        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        expect($adminUser->isAdmin())->toBeTrue()
            ->and($adminUser->hasPermission('existing.permission'))->toBeTrue()
            ->and($adminUser->hasPermission('nonexistent.permission'))->toBeTrue()
            ->and($adminUser->hasPermission('future.page.view'))->toBeTrue()
            ->and($adminUser->getAllPermissions())->toBe(['*']);
    });
});

describe('Regular User Permissions', function () {
    test('regular user only has permissions assigned to their role', function () {
        $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'View', 'group' => 'users']);
        $perm2 = Permission::firstOrCreate(['name' => 'users.create'], ['display_name' => 'Create', 'group' => 'users']);

        $role = Role::create(['name' => 'editor', 'description' => 'Editor', 'is_system' => false]);
        $role->permissions()->attach([$perm1->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        expect($user->isAdmin())->toBeFalse()
            ->and($user->hasPermission('users.view'))->toBeTrue()
            ->and($user->hasPermission('users.create'))->toBeFalse()
            ->and($user->hasPermission('nonexistent'))->toBeFalse();
    });

    test('user without roles can only access dashboard', function () {
        $user = User::factory()->create();

        expect($user->roles()->count())->toBe(0)
            ->and($user->hasPermission('dashboard.view'))->toBeFalse()
            ->and($user->getAllPermissions())->toBe([]);
    });
});

describe('Permission Helper Methods', function () {
    test('helper methods work correctly', function () {
        $perm = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'View', 'group' => 'users']);
        $role = Role::create(['name' => 'viewer', 'description' => 'Viewer', 'is_system' => false]);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        expect($user->hasAccessToPage('users'))->toBeTrue()
            ->and($user->canPerformAction('users', 'view'))->toBeTrue()
            ->and($user->canPerformAction('users', 'create'))->toBeFalse();
    });

    test('getAllPermissions returns flat array of permissions', function () {
        $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'View', 'group' => 'users']);
        $perm2 = Permission::firstOrCreate(['name' => 'users.edit'], ['display_name' => 'Edit', 'group' => 'users']);
        $perm3 = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'View', 'group' => 'roles']);

        $role = Role::create(['name' => 'editor', 'description' => 'Editor']);
        $role->permissions()->attach([$perm1->id, $perm2->id, $perm3->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $permissions = $user->getAllPermissions();

        expect($permissions)->toBeArray()
            ->and($permissions)->toContain('users.view')
            ->and($permissions)->toContain('users.edit')
            ->and($permissions)->toContain('roles.view')
            ->and(count($permissions))->toBe(3);
    });
});

describe('Multiple Roles', function () {
    test('user with multiple roles gets unique permissions', function () {
        $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'View', 'group' => 'users']);
        $perm2 = Permission::firstOrCreate(['name' => 'users.edit'], ['display_name' => 'Edit', 'group' => 'users']);

        $role1 = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
        $role1->permissions()->attach([$perm1->id]);

        $role2 = Role::create(['name' => 'editor', 'description' => 'Editor']);
        $role2->permissions()->attach([$perm1->id, $perm2->id]);

        $user = User::factory()->create();
        $user->roles()->attach([$role1->id, $role2->id]);

        $permissions = $user->getAllPermissions();

        expect($permissions)->toBeArray()
            ->and($permissions)->toContain('users.view')
            ->and($permissions)->toContain('users.edit')
            ->and(count($permissions))->toBe(2);
    });
});
