<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('purges customers deleted more than specified days ago', function () {
    $recentlyDeleted = Customer::create([
        'first_name' => 'Recent',
        'last_name' => 'Customer',
        'email' => 'recent@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);
    $recentlyDeleted->delete();
    Customer::withTrashed()->where('id', $recentlyDeleted->id)->update(['deleted_at' => now()->subDays(15)]);

    $oldDeleted = Customer::create([
        'first_name' => 'Old',
        'last_name' => 'Customer',
        'email' => 'old@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '223456789012',
    ]);
    $oldDeleted->delete();
    Customer::withTrashed()->where('id', $oldDeleted->id)->update(['deleted_at' => now()->subDays(45)]);

    $activeCustomer = Customer::create([
        'first_name' => 'Active',
        'last_name' => 'Customer',
        'email' => 'active@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '323456789012',
    ]);

    expect(Customer::count())->toBe(1);
    expect(Customer::withTrashed()->count())->toBe(3);

    Artisan::call('customers:purge-deleted', ['--days' => 30, '--force' => true]);

    expect(Customer::count())->toBe(1);
    expect(Customer::withTrashed()->count())->toBe(2);
    expect(Customer::withTrashed()->find($oldDeleted->id))->toBeNull();
    expect(Customer::withTrashed()->find($recentlyDeleted->id))->not->toBeNull();
    expect(Customer::find($activeCustomer->id))->not->toBeNull();
});

test('dry-run mode does not delete customers', function () {
    $oldDeleted = Customer::create([
        'first_name' => 'Old',
        'last_name' => 'Customer',
        'email' => 'old@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);
    $oldDeleted->delete();
    Customer::withTrashed()->where('id', $oldDeleted->id)->update(['deleted_at' => now()->subDays(45)]);

    expect(Customer::withTrashed()->count())->toBe(1);

    Artisan::call('customers:purge-deleted', ['--days' => 30, '--dry-run' => true]);

    expect(Customer::withTrashed()->count())->toBe(1);
    expect(Customer::withTrashed()->find($oldDeleted->id))->not->toBeNull();
});

test('handles no customers to purge gracefully', function () {
    $recentlyDeleted = Customer::create([
        'first_name' => 'Recent',
        'last_name' => 'Customer',
        'email' => 'recent@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);
    $recentlyDeleted->delete();
    Customer::withTrashed()->where('id', $recentlyDeleted->id)->update(['deleted_at' => now()->subDays(15)]);

    expect(Customer::withTrashed()->count())->toBe(1);

    $exitCode = Artisan::call('customers:purge-deleted', ['--days' => 30, '--force' => true]);

    expect($exitCode)->toBe(0);
    expect(Customer::withTrashed()->count())->toBe(1);
});

test('respects custom days parameter', function () {
    $deleted20DaysAgo = Customer::create([
        'first_name' => 'Twenty',
        'last_name' => 'Days',
        'email' => '20days@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);
    $deleted20DaysAgo->delete();
    Customer::withTrashed()->where('id', $deleted20DaysAgo->id)->update(['deleted_at' => now()->subDays(20)]);

    $deleted40DaysAgo = Customer::create([
        'first_name' => 'Forty',
        'last_name' => 'Days',
        'email' => '40days@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '223456789012',
    ]);
    $deleted40DaysAgo->delete();
    Customer::withTrashed()->where('id', $deleted40DaysAgo->id)->update(['deleted_at' => now()->subDays(40)]);

    expect(Customer::withTrashed()->count())->toBe(2);

    Artisan::call('customers:purge-deleted', ['--days' => 25, '--force' => true]);

    expect(Customer::withTrashed()->count())->toBe(1);
    expect(Customer::withTrashed()->find($deleted40DaysAgo->id))->toBeNull();
    expect(Customer::withTrashed()->find($deleted20DaysAgo->id))->not->toBeNull();
});

test('uses default 30 days when days option not provided', function () {
    $deleted25DaysAgo = Customer::create([
        'first_name' => 'TwentyFive',
        'last_name' => 'Days',
        'email' => '25days@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);
    $deleted25DaysAgo->delete();
    Customer::withTrashed()->where('id', $deleted25DaysAgo->id)->update(['deleted_at' => now()->subDays(25)]);

    $deleted35DaysAgo = Customer::create([
        'first_name' => 'ThirtyFive',
        'last_name' => 'Days',
        'email' => '35days@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '223456789012',
    ]);
    $deleted35DaysAgo->delete();
    Customer::withTrashed()->where('id', $deleted35DaysAgo->id)->update(['deleted_at' => now()->subDays(35)]);

    expect(Customer::withTrashed()->count())->toBe(2);

    Artisan::call('customers:purge-deleted', ['--force' => true]);

    expect(Customer::withTrashed()->count())->toBe(1);
    expect(Customer::withTrashed()->find($deleted35DaysAgo->id))->toBeNull();
    expect(Customer::withTrashed()->find($deleted25DaysAgo->id))->not->toBeNull();
});

test('does not affect active customers', function () {
    $activeCustomer1 = Customer::create([
        'first_name' => 'Active',
        'last_name' => 'One',
        'email' => 'active1@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '123456789012',
    ]);

    $activeCustomer2 = Customer::create([
        'first_name' => 'Active',
        'last_name' => 'Two',
        'email' => 'active2@example.com',
        'password' => Hash::make('password'),
        'oauth_provider' => 'local',
        'subway_card' => '223456789012',
    ]);

    expect(Customer::count())->toBe(2);

    Artisan::call('customers:purge-deleted', ['--days' => 30, '--force' => true]);

    expect(Customer::count())->toBe(2);
    expect(Customer::find($activeCustomer1->id))->not->toBeNull();
    expect(Customer::find($activeCustomer2->id))->not->toBeNull();
});

test('purges multiple customers in single run', function () {
    for ($i = 1; $i <= 5; $i++) {
        $customer = Customer::create([
            'first_name' => "Customer{$i}",
            'last_name' => 'Test',
            'email' => "customer{$i}@example.com",
            'password' => Hash::make('password'),
            'oauth_provider' => 'local',
            'subway_card' => "{$i}23456789012",
        ]);
        $customer->delete();
        Customer::withTrashed()->where('id', $customer->id)->update(['deleted_at' => now()->subDays(45)]);
    }

    expect(Customer::withTrashed()->count())->toBe(5);

    Artisan::call('customers:purge-deleted', ['--days' => 30, '--force' => true]);

    expect(Customer::withTrashed()->count())->toBe(0);
});
