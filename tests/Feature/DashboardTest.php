<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    
    // Buscar el permiso dashboard.view existente
    $permission = Permission::where('name', 'dashboard.view')->first();
    
    if (!$permission) {
        // Si no existe, crear uno con todos los campos requeridos
        $permission = Permission::create([
            'name' => 'dashboard.view',
            'display_name' => 'Dashboard',
            'description' => 'Ver dashboard'
        ]);
    }
    
    // Crear rol y asignar permiso
    $role = Role::create(['name' => 'test-role', 'description' => 'Test role']);
    $role->permissions()->attach($permission->id);
    
    // Asignar rol al usuario
    $user->roles()->attach($role->id);

    $this->actingAs($user);

    $this->get('/dashboard')->assertOk();
});
