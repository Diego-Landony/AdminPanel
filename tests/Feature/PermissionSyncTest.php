<?php

use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Permission Generation', function () {
    test('generatePermissions returns permissions from config', function () {
        $service = new PermissionService;
        $permissions = $service->generatePermissions();

        expect($permissions)->toBeArray()
            ->and(count($permissions))->toBeGreaterThan(0);

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

    test('permissions include nested menu structure', function () {
        $service = new PermissionService;
        $permissions = $service->generatePermissions();
        $permissionNames = collect($permissions)->pluck('name');

        expect($permissionNames)->toContain('menu.categories.view')
            ->and($permissionNames)->toContain('menu.products.view')
            ->and($permissionNames)->toContain('menu.sections.view')
            ->and($permissionNames)->toContain('menu.combos.view')
            ->and($permissionNames)->toContain('menu.promotions.view');
    });
});

describe('Permission Synchronization', function () {
    test('syncPermissions creates permissions in database', function () {
        $service = new PermissionService;

        $result = $service->syncPermissions();

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys(['total_pages', 'total_permissions', 'created', 'updated', 'deleted'])
            ->and($result['created'])->toBeGreaterThan(0);

        expect(Permission::where('name', 'users.view')->exists())->toBeTrue()
            ->and(Permission::where('name', 'menu.categories.create')->exists())->toBeTrue();
    });

    test('syncPermissions does not duplicate existing permissions', function () {
        $service = new PermissionService;

        $result1 = $service->syncPermissions();
        $totalAfterFirstSync = Permission::count();

        $result2 = $service->syncPermissions();
        $totalAfterSecondSync = Permission::count();

        expect($result2['created'])->toBe(0)
            ->and($totalAfterFirstSync)->toBe($totalAfterSecondSync)
            ->and($result2['updated'])->toBeGreaterThan(0);
    });
});

describe('Role Permissions', function () {
    test('admin role receives all permissions after sync', function () {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin', 'is_system' => true]);

        $service = new PermissionService;
        $service->syncPermissions();

        $allPermissionIds = Permission::pluck('id');
        $adminRole->permissions()->sync($allPermissionIds);

        $adminRole->refresh();

        expect($adminRole->permissions()->count())->toBeGreaterThan(20);
    });
});

describe('Configuration and Display', function () {
    test('getPagesConfiguration returns correct structure', function () {
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

    test('getGroupDisplayName returns readable name', function () {
        $service = new PermissionService;

        expect($service->getGroupDisplayName('users'))->toBe('Usuarios')
            ->and($service->getGroupDisplayName('menu.categories'))->toBe('Categorías')
            ->and($service->getGroupDisplayName('activity'))->toBe('Actividad');
    });
});
