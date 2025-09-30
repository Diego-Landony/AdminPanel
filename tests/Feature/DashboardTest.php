<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

test('guests are redirected to the login page', function () {
    $this->get('/home')->assertRedirect('/login');
});

test('authenticated users can visit the home page', function () {
    $user = User::factory()->create();

    // Buscar el permiso home.view existente
    $permission = Permission::where('name', 'home.view')->first();

    if (!$permission) {
        // Si no existe, crear uno con todos los campos requeridos
        $permission = Permission::create([
            'name' => 'home.view',
            'display_name' => 'Home',
            'description' => 'Ver home'
        ]);
    }

    // Crear rol y asignar permiso
    $role = Role::create(['name' => 'test-role', 'description' => 'Test role']);
    $role->permissions()->attach($permission->id);

    // Asignar rol al usuario
    $user->roles()->attach($role->id);

    $this->actingAs($user);

    $this->get('/home')->assertOk();
});
