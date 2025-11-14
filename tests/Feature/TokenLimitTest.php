<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('customer can have up to 5 tokens', function () {
    $customer = Customer::create([
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    for ($i = 1; $i <= 5; $i++) {
        $customer->createToken("device-{$i}");
    }

    expect($customer->tokens()->count())->toBe(5);
});

test('creating 6th token deletes oldest token', function () {
    $customer = Customer::create([
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    $firstToken = $customer->createToken('device-1');
    $firstTokenId = $firstToken->accessToken->id;

    for ($i = 2; $i <= 5; $i++) {
        $customer->createToken("device-{$i}");
        sleep(1);
    }

    expect($customer->tokens()->count())->toBe(5);

    $customer->enforceTokenLimit(5);
    $customer->createToken('device-6');

    expect($customer->tokens()->count())->toBe(5);
    expect($customer->tokens()->where('id', $firstTokenId)->exists())->toBeFalse();
});

test('enforceTokenLimit deletes tokens with null last_used_at first', function () {
    $customer = Customer::create([
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    $neverUsedToken = $customer->createToken('never-used');
    $neverUsedTokenId = $neverUsedToken->accessToken->id;

    for ($i = 1; $i <= 4; $i++) {
        $token = $customer->createToken("device-{$i}");
        $token->accessToken->update(['last_used_at' => now()->subDays($i)]);
        sleep(1);
    }

    expect($customer->tokens()->count())->toBe(5);

    $customer->enforceTokenLimit(5);
    $customer->createToken('device-6');

    expect($customer->tokens()->count())->toBe(5);
    expect($customer->tokens()->where('id', $neverUsedTokenId)->exists())->toBeFalse();
});

test('register endpoint enforces token limit', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'New',
        'last_name' => 'Customer',
        'email' => 'new@example.com',
        'password' => 'Pass123',
        'password_confirmation' => 'Pass123',
    ]);

    $response->assertCreated();

    $customer = Customer::where('email', 'new@example.com')->first();
    expect($customer->tokens()->count())->toBe(1);

    for ($i = 1; $i <= 5; $i++) {
        $customer->createToken("device-{$i}");
    }

    expect($customer->tokens()->count())->toBe(6);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'new@example.com',
        'password' => 'Pass123',
    ]);

    $response->assertOk();

    $customer->refresh();
    expect($customer->tokens()->count())->toBe(5);
});

test('login endpoint enforces token limit', function () {
    $customer = Customer::create([
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    for ($i = 1; $i <= 5; $i++) {
        $customer->createToken("device-{$i}");
    }

    expect($customer->tokens()->count())->toBe(5);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertOk();

    $customer->refresh();
    expect($customer->tokens()->count())->toBe(5);
});

test('token name includes device identifier when provided', function () {
    $customer = Customer::create([
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
        'device_identifier' => 'ABC123XYZ789',
    ]);

    $response->assertOk();

    $customer->refresh();
    $latestToken = $customer->tokens()->latest()->first();

    expect($latestToken->name)->toBe('ABC123XY');
});

test('token name is device-uniqid when device identifier not provided', function () {
    $customer = Customer::create([
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertOk();

    $customer->refresh();
    $latestToken = $customer->tokens()->latest()->first();

    expect($latestToken->name)->toStartWith('device-');
});
