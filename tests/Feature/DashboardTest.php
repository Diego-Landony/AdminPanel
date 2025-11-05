<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Guest Access', function () {
    test('guests are redirected to the login page', function () {
        $this->get('/home')->assertRedirect('/login');
    });
});

describe('Authenticated Access', function () {
    test('authenticated users can visit the home page', function () {
        $user = User::factory()->create();

        $permission = Permission::where('name', 'home.view')->first();

        if (! $permission) {
            $permission = Permission::create([
                'name' => 'home.view',
                'display_name' => 'Home',
                'description' => 'Ver home',
            ]);
        }

        $role = Role::create(['name' => 'test-role', 'description' => 'Test role']);
        $role->permissions()->attach($permission->id);

        $user->roles()->attach($role->id);

        $this->actingAs($user);

        $this->get('/home')->assertOk();
    });
});
