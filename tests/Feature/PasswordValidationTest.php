<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password must be at least 6 characters', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/settings/password')
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => '12345', // Solo 5 caracteres
            'password_confirmation' => '12345',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect('/settings/password');

    // Verificar que la contraseña no se actualizó
    expect(Hash::check('12345', $user->refresh()->password))->toBeFalse();
});

test('password with exactly 6 characters is valid', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/settings/password')
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => '123456', // Exactamente 6 caracteres
            'password_confirmation' => '123456',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/password');

    // Verificar que la contraseña se actualizó
    expect(Hash::check('123456', $user->refresh()->password))->toBeTrue();
});

test('password with more than 6 characters is valid', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/settings/password')
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => '123456789', // Más de 6 caracteres
            'password_confirmation' => '123456789',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/password');

    // Verificar que la contraseña se actualizó
    expect(Hash::check('123456789', $user->refresh()->password))->toBeTrue();
});

test('numeric password with 6 digits is valid', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/settings/password')
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => '123456', // Solo números, 6 dígitos
            'password_confirmation' => '123456',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/password');

    // Verificar que la contraseña se actualizó
    expect(Hash::check('123456', $user->refresh()->password))->toBeTrue();
});

test('simple word password with 6 characters is valid', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/settings/password')
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'admin', // Solo 5 caracteres - debe fallar
            'password_confirmation' => 'admin',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect('/settings/password');

    // Verificar que la contraseña no se actualizó
    expect(Hash::check('admin', $user->refresh()->password))->toBeFalse();
});
