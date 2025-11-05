<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('Minimum Length Validation', function () {
    test('password must be at least 6 characters', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => '12345',
                'password_confirmation' => '12345',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/settings/password');

        expect(Hash::check('12345', $user->refresh()->password))->toBeFalse();
    });

    test('simple word password with less than 6 characters fails', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => 'admin',
                'password_confirmation' => 'admin',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/settings/password');

        expect(Hash::check('admin', $user->refresh()->password))->toBeFalse();
    });
});

describe('Valid Passwords', function () {
    test('password with exactly 6 characters is valid', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => '123456',
                'password_confirmation' => '123456',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/password');

        expect(Hash::check('123456', $user->refresh()->password))->toBeTrue();
    });

    test('password with more than 6 characters is valid', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => '123456789',
                'password_confirmation' => '123456789',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/password');

        expect(Hash::check('123456789', $user->refresh()->password))->toBeTrue();
    });

    test('numeric password with 6 digits is valid', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => '123456',
                'password_confirmation' => '123456',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/password');

        expect(Hash::check('123456', $user->refresh()->password))->toBeTrue();
    });
});
