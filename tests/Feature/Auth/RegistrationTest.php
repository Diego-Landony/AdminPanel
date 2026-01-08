<?php

describe('Registration Screen', function () {
    test('registration screen can be rendered', function () {
        $response = $this->get('/register');

        $response->assertStatus(200);
    });
});

describe('User Registration', function () {
    test('new users can register', function () {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));
    });
});
