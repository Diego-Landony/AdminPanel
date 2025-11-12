<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

test('tokens:cleanup command deletes expired tokens older than specified days', function () {
    $customer = Customer::create([
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    $recentExpiredToken = $customer->createToken('recent-expired');
    $recentExpiredToken->accessToken->update([
        'expires_at' => now()->subDays(5),
    ]);

    $oldExpiredToken = $customer->createToken('old-expired');
    $oldExpiredToken->accessToken->update([
        'expires_at' => now()->subDays(10),
    ]);

    $activeToken = $customer->createToken('active');
    $activeToken->accessToken->update([
        'expires_at' => now()->addDays(30),
    ]);

    expect(PersonalAccessToken::count())->toBe(3);

    Artisan::call('tokens:cleanup', ['--days' => 7]);

    expect(PersonalAccessToken::count())->toBe(2);
    expect(PersonalAccessToken::find($oldExpiredToken->accessToken->id))->toBeNull();
    expect(PersonalAccessToken::find($recentExpiredToken->accessToken->id))->not->toBeNull();
    expect(PersonalAccessToken::find($activeToken->accessToken->id))->not->toBeNull();
});

test('tokens:cleanup command with dry-run does not delete tokens', function () {
    $customer = Customer::create([
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    $oldExpiredToken = $customer->createToken('old-expired');
    $oldExpiredToken->accessToken->update([
        'expires_at' => now()->subDays(10),
    ]);

    expect(PersonalAccessToken::count())->toBe(1);

    Artisan::call('tokens:cleanup', ['--days' => 7, '--dry-run' => true]);

    expect(PersonalAccessToken::count())->toBe(1);
    expect(PersonalAccessToken::find($oldExpiredToken->accessToken->id))->not->toBeNull();
});

test('tokens:cleanup command handles no expired tokens gracefully', function () {
    $customer = Customer::create([
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    $activeToken = $customer->createToken('active');
    $activeToken->accessToken->update([
        'expires_at' => now()->addDays(30),
    ]);

    expect(PersonalAccessToken::count())->toBe(1);

    $exitCode = Artisan::call('tokens:cleanup', ['--days' => 7]);

    expect($exitCode)->toBe(0);
    expect(PersonalAccessToken::count())->toBe(1);
});

test('tokens:cleanup respects custom days parameter', function () {
    $customer = Customer::create([
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    $token5DaysAgo = $customer->createToken('5-days-ago');
    $token5DaysAgo->accessToken->update([
        'expires_at' => now()->subDays(5),
    ]);

    $token15DaysAgo = $customer->createToken('15-days-ago');
    $token15DaysAgo->accessToken->update([
        'expires_at' => now()->subDays(15),
    ]);

    expect(PersonalAccessToken::count())->toBe(2);

    Artisan::call('tokens:cleanup', ['--days' => 10]);

    expect(PersonalAccessToken::count())->toBe(1);
    expect(PersonalAccessToken::find($token15DaysAgo->accessToken->id))->toBeNull();
    expect(PersonalAccessToken::find($token5DaysAgo->accessToken->id))->not->toBeNull();
});

test('enforceTokenLimit marks associated device as inactive when token deleted', function () {
    $customer = Customer::create([
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    $firstToken = $customer->createToken('device-1');

    $device = $customer->devices()->create([
        'sanctum_token_id' => $firstToken->accessToken->id,
        'fcm_token' => 'fcm-token-123',
        'device_identifier' => 'ABC123',
        'device_name' => 'iPhone',
        'is_active' => true,
    ]);

    expect($device->is_active)->toBeTrue();

    for ($i = 2; $i <= 5; $i++) {
        $customer->createToken("device-{$i}");
    }

    $customer->enforceTokenLimit(5);
    $customer->createToken('device-6');

    $device->refresh();
    expect($device->is_active)->toBeFalse();
});

test('enforceTokenLimit marks multiple associated devices as inactive', function () {
    $customer = Customer::create([
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    $token1 = $customer->createToken('device-1');
    $token2 = $customer->createToken('device-2');

    $device1 = $customer->devices()->create([
        'sanctum_token_id' => $token1->accessToken->id,
        'fcm_token' => 'fcm-token-1',
        'device_identifier' => 'ABC123',
        'device_name' => 'iPhone',
        'is_active' => true,
    ]);

    $device2 = $customer->devices()->create([
        'sanctum_token_id' => $token2->accessToken->id,
        'fcm_token' => 'fcm-token-2',
        'device_identifier' => 'XYZ789',
        'device_name' => 'Samsung',
        'is_active' => true,
    ]);

    expect($device1->is_active)->toBeTrue();
    expect($device2->is_active)->toBeTrue();

    for ($i = 3; $i <= 5; $i++) {
        $customer->createToken("device-{$i}");
    }

    $customer->enforceTokenLimit(5);
    $customer->createToken('device-6');

    $customer->enforceTokenLimit(5);
    $customer->createToken('device-7');

    $device1->refresh();
    $device2->refresh();

    expect($device1->is_active)->toBeFalse();
    expect($device2->is_active)->toBeFalse();
});
