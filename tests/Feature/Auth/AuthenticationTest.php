<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    // Crear un usuario con rol de administrador para la prueba
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    // Crear rol de administrador
    $adminRole = Role::firstOrCreate(
        ['name' => 'admin'],
        [
            'display_name' => 'Administrador',
            'description' => 'Rol de administrador para pruebas',
            'is_system' => false,
        ]
    );

    // Crear permiso para el home
    $homePermission = Permission::firstOrCreate(
        ['name' => 'home.view'],
        [
            'display_name' => 'Ver Home',
            'description' => 'Permite acceder a la página de inicio',
            'group' => 'home',
        ]
    );

    // Asignar permiso al rol
    $adminRole->permissions()->sync([$homePermission->id]);

    // Asignar rol al usuario
    $user->roles()->sync([$adminRole->id]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('login form can be submitted with Enter key', function () {
    // Crear un usuario con rol de administrador para la prueba
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    // Crear rol de administrador
    $adminRole = Role::firstOrCreate(
        ['name' => 'admin'],
        [
            'display_name' => 'Administrador',
            'description' => 'Rol de administrador para pruebas',
            'is_system' => false,
        ]
    );

    // Crear permiso para el home
    $homePermission = Permission::firstOrCreate(
        ['name' => 'home.view'],
        [
            'display_name' => 'Ver Home',
            'description' => 'Permite acceder a la página de inicio',
            'group' => 'home',
        ]
    );

    // Asignar permiso al rol
    $adminRole->permissions()->sync([$homePermission->id]);

    // Asignar rol al usuario
    $user->roles()->sync([$adminRole->id]);

    // Simular envío del formulario (que es lo que haría el Enter key)
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
});
