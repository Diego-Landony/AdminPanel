<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('Permission Caching', function () {
    test('permissions are cached correctly', function () {
        $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'View', 'group' => 'users']);
        $perm2 = Permission::firstOrCreate(['name' => 'users.edit'], ['display_name' => 'Edit', 'group' => 'users']);

        $role = Role::create(['name' => 'editor', 'description' => 'Editor']);
        $role->permissions()->attach([$perm1->id, $perm2->id]);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        // First call: should cache
        $permissions1 = $user->getAllPermissions();

        // Verify it's in cache
        $cached = Cache::get("user.{$user->id}.permissions");
        expect($cached)->not->toBeNull()
            ->and($cached)->toBe($permissions1);

        // Second call: should return from cache
        $permissions2 = $user->getAllPermissions();
        expect($permissions2)->toBe($permissions1);
    });

    test('admin does not use cache, always returns wildcard', function () {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin', 'is_system' => true]);
        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($adminRole);

        // Admin always returns wildcard
        $permissions = $adminUser->getAllPermissions();
        expect($permissions)->toBe(['*']);

        // Verify it's NOT cached for admin
        expect(Cache::has("user.{$adminUser->id}.permissions"))->toBeFalse();
    });
});

describe('Cache Invalidation', function () {
    test('cache is invalidated when user role changes', function () {
        $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'View', 'group' => 'users']);

        $role1 = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
        $role1->permissions()->attach($perm1);

        $role2 = Role::create(['name' => 'editor', 'description' => 'Editor']);

        $user = User::factory()->create();
        $user->roles()->attach($role1);

        // Cache initial permissions
        $initialPermissions = $user->getAllPermissions();
        expect($initialPermissions)->toContain('users.view');

        // Verify it's cached
        expect(Cache::has("user.{$user->id}.permissions"))->toBeTrue();

        // Change user role
        $user->roles()->sync([$role2->id]);
        $user->flushPermissionsCache();

        // Cache should be invalidated
        expect(Cache::has("user.{$user->id}.permissions"))->toBeFalse();

        // Reload relationships and get new permissions
        $user->load('roles.permissions');
        $newPermissions = $user->getAllPermissions();
        expect($newPermissions)->not->toContain('users.view');
    });

    test('cache is invalidated when role permissions change', function () {
        $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'View', 'group' => 'users']);
        $perm2 = Permission::firstOrCreate(['name' => 'users.edit'], ['display_name' => 'Edit', 'group' => 'users']);

        $role = Role::create(['name' => 'editor', 'description' => 'Editor']);
        $role->permissions()->attach($perm1);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        // Cache initial permissions
        $initialPermissions = $user->getAllPermissions();
        expect($initialPermissions)->toContain('users.view')
            ->and($initialPermissions)->not->toContain('users.edit');

        // Add new permission to role
        $role->permissions()->attach($perm2);

        // Invalidate cache manually (simulating what RoleController does)
        $user->flushPermissionsCache();

        // Reload and verify new permissions
        $user->load('roles.permissions');
        $newPermissions = $user->getAllPermissions();
        expect($newPermissions)->toContain('users.view')
            ->and($newPermissions)->toContain('users.edit');
    });

    test('flushPermissionsCache invalidates correctly', function () {
        $perm = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'View', 'group' => 'users']);
        $role = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        // Cache permissions
        $user->getAllPermissions();
        expect(Cache::has("user.{$user->id}.permissions"))->toBeTrue();

        // Invalidate cache
        $user->flushPermissionsCache();

        // Cache should be clean
        expect(Cache::has("user.{$user->id}.permissions"))->toBeFalse();
    });
});

describe('Cache Expiration', function () {
    test('cache expires after TTL', function () {
        $perm = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'View', 'group' => 'users']);
        $role = Role::create(['name' => 'viewer', 'description' => 'Viewer']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        // Cache with short TTL (1 second for test)
        Cache::put("user.{$user->id}.permissions", ['users.view'], now()->addSecond());

        expect(Cache::has("user.{$user->id}.permissions"))->toBeTrue();

        // Wait for expiration
        sleep(2);

        expect(Cache::has("user.{$user->id}.permissions"))->toBeFalse();
    })->skip('Slow test, run manually if needed');
});

describe('Multiple Users Cache', function () {
    test('multiple users have independent caches', function () {
        $perm1 = Permission::firstOrCreate(['name' => 'users.view'], ['display_name' => 'View', 'group' => 'users']);
        $perm2 = Permission::firstOrCreate(['name' => 'roles.view'], ['display_name' => 'View', 'group' => 'roles']);

        $role1 = Role::create(['name' => 'user-viewer', 'description' => 'User Viewer']);
        $role1->permissions()->attach($perm1);

        $role2 = Role::create(['name' => 'role-viewer', 'description' => 'Role Viewer']);
        $role2->permissions()->attach($perm2);

        $user1 = User::factory()->create();
        $user1->roles()->attach($role1);

        $user2 = User::factory()->create();
        $user2->roles()->attach($role2);

        // Cache both users
        $perms1 = $user1->getAllPermissions();
        $perms2 = $user2->getAllPermissions();

        expect($perms1)->toContain('users.view')
            ->and($perms1)->not->toContain('roles.view')
            ->and($perms2)->toContain('roles.view')
            ->and($perms2)->not->toContain('users.view');

        // Both caches should exist independently
        expect(Cache::has("user.{$user1->id}.permissions"))->toBeTrue()
            ->and(Cache::has("user.{$user2->id}.permissions"))->toBeTrue();

        // Invalidate only user1
        $user1->flushPermissionsCache();

        expect(Cache::has("user.{$user1->id}.permissions"))->toBeFalse()
            ->and(Cache::has("user.{$user2->id}.permissions"))->toBeTrue();
    });
});
