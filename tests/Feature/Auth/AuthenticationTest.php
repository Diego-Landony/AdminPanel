<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

describe('Login Screen', function () {
    test('login screen can be rendered', function () {
        $response = $this->get('/login');

        $response->assertStatus(200);
    });
});

describe('Authentication', function () {
    beforeEach(function () {
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrador',
                'description' => 'Rol de administrador para pruebas',
                'is_system' => false,
            ]
        );

        $homePermission = Permission::firstOrCreate(
            ['name' => 'home.view'],
            [
                'display_name' => 'Ver Home',
                'description' => 'Permite acceder a la página de inicio',
                'group' => 'home',
            ]
        );

        $adminRole->permissions()->sync([$homePermission->id]);
        $this->user->roles()->sync([$adminRole->id]);
    });

    test('users can authenticate using the login screen', function () {
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));
    });

    test('users can not authenticate with invalid password', function () {
        $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    });

    test('login form can be submitted with Enter key', function () {
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));
    });
});

describe('Logout', function () {
    test('users can logout', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    });
});
