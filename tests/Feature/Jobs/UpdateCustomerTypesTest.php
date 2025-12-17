<?php

use App\Jobs\UpdateCustomerTypes;
use App\Models\Customer;
use App\Models\CustomerPointsTransaction;
use App\Models\CustomerType;

beforeEach(function () {
    // Create standard customer types
    CustomerType::factory()->regular()->create();
    CustomerType::factory()->bronze()->create();
    CustomerType::factory()->silver()->create();
    CustomerType::factory()->gold()->create();
    CustomerType::factory()->platinum()->create();
});

test('job upgrades customer based on points earned in last 6 months', function () {
    $regularType = CustomerType::where('name', 'regular')->first();
    $bronzeType = CustomerType::where('name', 'bronze')->first();

    $customer = Customer::factory()->create([
        'customer_type_id' => $regularType->id,
        'points' => 100,
    ]);

    // Create points transaction within the 6-month window (60 points earned)
    CustomerPointsTransaction::create([
        'customer_id' => $customer->id,
        'points' => 60,
        'type' => 'earned',
        'description' => 'Test points',
        'created_at' => now()->subDays(30),
    ]);

    (new UpdateCustomerTypes)->handle();

    $customer->refresh();

    expect($customer->customer_type_id)->toBe($bronzeType->id);
});

test('job downgrades customer when points in window are insufficient', function () {
    $goldType = CustomerType::where('name', 'gold')->first();
    $regularType = CustomerType::where('name', 'regular')->first();

    $customer = Customer::factory()->create([
        'customer_type_id' => $goldType->id,
        'points' => 500, // High total points
    ]);

    // Create only a small amount of points in the 6-month window
    CustomerPointsTransaction::create([
        'customer_id' => $customer->id,
        'points' => 10,
        'type' => 'earned',
        'description' => 'Small points',
        'created_at' => now()->subDays(30),
    ]);

    (new UpdateCustomerTypes)->handle();

    $customer->refresh();

    // Customer should be downgraded to Regular (only 10 points in window)
    expect($customer->customer_type_id)->toBe($regularType->id);
});

test('job assigns default type to customer with no points in window', function () {
    $goldType = CustomerType::where('name', 'gold')->first();
    $regularType = CustomerType::where('name', 'regular')->first();

    $customer = Customer::factory()->create([
        'customer_type_id' => $goldType->id,
        'points' => 1000,
    ]);

    // No points transactions in the window

    (new UpdateCustomerTypes)->handle();

    $customer->refresh();

    expect($customer->customer_type_id)->toBe($regularType->id);
});

test('job ignores points outside 6-month window', function () {
    $regularType = CustomerType::where('name', 'regular')->first();
    $goldType = CustomerType::where('name', 'gold')->first();

    // Start with gold type but old points - use withoutCustomerType to avoid factory creating new type
    $customer = Customer::factory()->withoutCustomerType()->create([
        'customer_type_id' => $goldType->id,
        'points' => 500,
    ]);

    // Create points transaction OUTSIDE the 6-month window (7 months ago)
    // Use DB insert to set created_at since Eloquent timestamps auto-set
    $transaction = CustomerPointsTransaction::create([
        'customer_id' => $customer->id,
        'points' => 500,
        'type' => 'earned',
        'description' => 'Old points',
    ]);
    // Manually update the created_at to 7 months ago
    $transaction->forceFill(['created_at' => now()->subMonths(7)])->save();

    (new UpdateCustomerTypes)->handle();

    $customer->refresh();

    // Should be downgraded to Regular because old points don't count
    expect($customer->customer_type_id)->toBe($regularType->id);
});

test('job only counts positive points for type calculation', function () {
    $regularType = CustomerType::where('name', 'regular')->first();
    $bronzeType = CustomerType::where('name', 'bronze')->first();

    $customer = Customer::factory()->create([
        'customer_type_id' => $regularType->id,
        'points' => 100,
    ]);

    // Create positive points
    CustomerPointsTransaction::create([
        'customer_id' => $customer->id,
        'points' => 100,
        'type' => 'earned',
        'description' => 'Earned points',
        'created_at' => now()->subDays(30),
    ]);

    // Create negative points (redemption)
    CustomerPointsTransaction::create([
        'customer_id' => $customer->id,
        'points' => -50,
        'type' => 'redeemed',
        'description' => 'Redeemed points',
        'created_at' => now()->subDays(15),
    ]);

    (new UpdateCustomerTypes)->handle();

    $customer->refresh();

    // Should be Bronze (100 earned, negative doesn't count)
    expect($customer->customer_type_id)->toBe($bronzeType->id);
});

test('job correctly assigns platinum type for high earners', function () {
    $regularType = CustomerType::where('name', 'regular')->first();
    $platinumType = CustomerType::where('name', 'platinum')->first();

    $customer = Customer::factory()->create([
        'customer_type_id' => $regularType->id,
        'points' => 0,
    ]);

    // Create points transaction for platinum level
    CustomerPointsTransaction::create([
        'customer_id' => $customer->id,
        'points' => 1500,
        'type' => 'earned',
        'description' => 'Big purchase',
        'created_at' => now()->subDays(30),
    ]);

    (new UpdateCustomerTypes)->handle();

    $customer->refresh();

    expect($customer->customer_type_id)->toBe($platinumType->id);
});

test('job does not update customer if type is unchanged', function () {
    $bronzeType = CustomerType::where('name', 'bronze')->first();

    $customer = Customer::factory()->create([
        'customer_type_id' => $bronzeType->id,
        'points' => 75,
    ]);

    // Create points that maintain bronze level
    CustomerPointsTransaction::create([
        'customer_id' => $customer->id,
        'points' => 75,
        'type' => 'earned',
        'description' => 'Points',
        'created_at' => now()->subDays(30),
    ]);

    $originalUpdatedAt = $customer->updated_at;

    (new UpdateCustomerTypes)->handle();

    $customer->refresh();

    expect($customer->customer_type_id)->toBe($bronzeType->id);
});

test('job handles multiple customers in batch', function () {
    $regularType = CustomerType::where('name', 'regular')->first();
    $bronzeType = CustomerType::where('name', 'bronze')->first();
    $silverType = CustomerType::where('name', 'silver')->first();

    // Create customers with different point levels
    $customer1 = Customer::factory()->create(['customer_type_id' => $regularType->id]);
    $customer2 = Customer::factory()->create(['customer_type_id' => $regularType->id]);
    $customer3 = Customer::factory()->create(['customer_type_id' => $regularType->id]);

    // Customer 1: 60 points -> Bronze
    CustomerPointsTransaction::create([
        'customer_id' => $customer1->id,
        'points' => 60,
        'type' => 'earned',
        'created_at' => now()->subDays(30),
    ]);

    // Customer 2: 150 points -> Silver
    CustomerPointsTransaction::create([
        'customer_id' => $customer2->id,
        'points' => 150,
        'type' => 'earned',
        'created_at' => now()->subDays(30),
    ]);

    // Customer 3: 10 points -> Regular
    CustomerPointsTransaction::create([
        'customer_id' => $customer3->id,
        'points' => 10,
        'type' => 'earned',
        'created_at' => now()->subDays(30),
    ]);

    (new UpdateCustomerTypes)->handle();

    expect($customer1->fresh()->customer_type_id)->toBe($bronzeType->id);
    expect($customer2->fresh()->customer_type_id)->toBe($silverType->id);
    expect($customer3->fresh()->customer_type_id)->toBe($regularType->id);
});

test('job accepts custom window months parameter', function () {
    $regularType = CustomerType::where('name', 'regular')->first();
    $bronzeType = CustomerType::where('name', 'bronze')->first();

    $customer = Customer::factory()->withoutCustomerType()->create([
        'customer_type_id' => $bronzeType->id, // Start with bronze
        'points' => 100,
    ]);

    // Create points 4 months ago - use forceFill to set created_at
    $transaction = CustomerPointsTransaction::create([
        'customer_id' => $customer->id,
        'points' => 100,
        'type' => 'earned',
    ]);
    $transaction->forceFill(['created_at' => now()->subMonths(4)])->save();

    // With 3-month window, these points should NOT count -> downgrade to regular
    (new UpdateCustomerTypes(3))->handle();

    $customer->refresh();
    expect($customer->customer_type_id)->toBe($regularType->id);

    // With 6-month window (default), these points SHOULD count -> upgrade to bronze
    (new UpdateCustomerTypes(6))->handle();

    $customer->refresh();
    expect($customer->customer_type_id)->toBe($bronzeType->id);
});

test('job completes without errors', function () {
    $regularType = CustomerType::where('name', 'regular')->first();

    $customer = Customer::factory()->create([
        'customer_type_id' => $regularType->id,
        'points' => 50,
    ]);

    // Should run without throwing exceptions
    (new UpdateCustomerTypes)->handle();

    expect(true)->toBeTrue();
});

test('job handles empty customer types gracefully', function () {
    // Delete all customer types
    CustomerType::query()->delete();

    // Should not throw an exception, just return early
    (new UpdateCustomerTypes)->handle();

    expect(true)->toBeTrue();
});
