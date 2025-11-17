<?php

use App\Models\Customer;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;

it('generates deep link for customer password reset', function () {
    $customer = Customer::factory()->create();

    $notification = new ResetPassword('test-token-123');
    $mail = $notification->toMail($customer);

    expect($mail->actionUrl)
        ->toStartWith('subwayapp://reset-password?')
        ->toContain('token=test-token-123')
        ->toContain('email='.urlencode($customer->email));
});

it('generates deep link for customer email verification', function () {
    $customer = Customer::factory()->create(['email_verified_at' => null]);

    $notification = new VerifyEmail;
    $mail = $notification->toMail($customer);

    expect($mail->actionUrl)
        ->toStartWith('subwayapp://verify-email?url=')
        ->toContain(urlencode('api/v1/auth/email/verify'));
});

it('generates web url for admin user password reset', function () {
    $user = \App\Models\User::factory()->create();

    $notification = new ResetPassword('test-token-123');
    $mail = $notification->toMail($user);

    expect($mail->actionUrl)
        ->toContain('reset-password/test-token-123')
        ->not->toStartWith('subwayapp://');
});

it('generates web url for admin user email verification', function () {
    $user = \App\Models\User::factory()->create(['email_verified_at' => null]);

    $notification = new VerifyEmail;
    $mail = $notification->toMail($user);

    expect($mail->actionUrl)
        ->toContain('verify-email')
        ->not->toStartWith('subwayapp://');
});

it('uses mobile template for customer password reset', function () {
    $customer = Customer::factory()->create(['first_name' => 'María']);

    $notification = new \App\Notifications\ResetPasswordNotification('test-token');
    $mail = $notification->toMail($customer);

    expect($mail->view)->toBe('emails.mobile.reset-password')
        ->and($mail->viewData['customerName'])->toBe('María')
        ->and($mail->viewData['expireMinutes'])->toBeInt()
        ->and($mail->subject)->toContain(config('app.mobile_name'));
});

it('uses mobile template for customer email verification', function () {
    $customer = Customer::factory()->create([
        'first_name' => 'Carlos',
        'email_verified_at' => null,
    ]);

    $notification = new \App\Notifications\VerifyEmailNotification;
    $mail = $notification->toMail($customer);

    expect($mail->view)->toBe('emails.mobile.verify-email')
        ->and($mail->viewData['customerName'])->toBe('Carlos')
        ->and($mail->viewData['expireMinutes'])->toBe(60)
        ->and($mail->subject)->toContain(config('app.mobile_name'));
});
