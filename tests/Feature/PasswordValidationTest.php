<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('Minimum Length Validation', function () {
    test('password must be at least 8 characters', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => 'Pass1!',
                'password_confirmation' => 'Pass1!',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/settings/password');

        expect(Hash::check('Pass1!', $user->refresh()->password))->toBeFalse();
    });

    test('password with 7 characters fails', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => 'Pass12!',
                'password_confirmation' => 'Pass12!',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/settings/password');

        expect(Hash::check('Pass12!', $user->refresh()->password))->toBeFalse();
    });
});

describe('Valid Passwords', function () {
    test('password with 8 characters meeting all requirements is valid', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => 'Pass123!',
                'password_confirmation' => 'Pass123!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/password');

        expect(Hash::check('Pass123!', $user->refresh()->password))->toBeTrue();
    });

    test('password with more than 8 characters meeting all requirements is valid', function () {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/password');

        expect(Hash::check('Password1!', $user->refresh()->password))->toBeTrue();
    });

});
