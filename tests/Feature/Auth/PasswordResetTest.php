<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

describe('Reset Password Link Screen', function () {
    test('reset password link screen can be rendered', function () {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    });
});

describe('Password Reset Request', function () {
    test('reset password link can be requested', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    });

    test('password reset link cannot be requested with invalid email', function () {
        $response = $this->post('/forgot-password', ['email' => 'nonexistent@example.com']);

        $response->assertSessionHasNoErrors();
    });
});

describe('Password Reset Screen', function () {
    test('reset password screen can be rendered', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    });
});

describe('Password Reset Execution', function () {
    test('password can be reset with valid token', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    });
});

describe('Password Reset Validation', function () {
    test('password reset fails with invalid token', function () {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token-12345',
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    test('password reset fails with mismatched passwords', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'newpassword',
                'password_confirmation' => 'differentpassword',
            ]);

            $response->assertSessionHasErrors(['password']);

            return true;
        });
    });

    test('password reset fails with wrong email for token', function () {
        Notification::fake();

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($otherUser) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $otherUser->email,
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ]);

            $response->assertSessionHasErrors(['email']);

            return true;
        });
    });
});
