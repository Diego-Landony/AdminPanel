<?php

use App\Models\Customer;

/**
 * Test suite para el modelo Customer
 */
test('customer has fillable attributes', function () {
    $customer = new Customer;

    $expectedFillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'subway_card',
        'birth_date',
        'gender',
        'phone',
        'last_login_at',
        'last_activity_at',
        'last_purchase_at',
        'points',
        'points_updated_at',
        'timezone',
        'customer_type_id',
    ];

    expect($customer->getFillable())->toEqual($expectedFillable);
});

test('customer has hidden attributes', function () {
    $customer = new Customer;

    $expectedHidden = [
        'password',
        'remember_token',
    ];

    expect($customer->getHidden())->toEqual($expectedHidden);
});

test('customer casts attributes correctly', function () {
    $customer = new Customer;

    $casts = $customer->getCasts();

    // Verify essential casts are present
    expect($casts)->toHaveKey('email_verified_at');
    expect($casts)->toHaveKey('password');
    expect($casts)->toHaveKey('birth_date');
    expect($casts)->toHaveKey('last_login_at');
    expect($casts)->toHaveKey('last_activity_at');
    expect($casts)->toHaveKey('last_purchase_at');
    expect($casts)->toHaveKey('points');
    expect($casts)->toHaveKey('points_updated_at');

    // Verify cast types
    expect($casts['email_verified_at'])->toBe('datetime');
    expect($casts['password'])->toBe('hashed');
    expect($casts['birth_date'])->toBe('date');
    expect($casts['last_login_at'])->toBe('datetime');
    expect($casts['last_activity_at'])->toBe('datetime');
    expect($casts['last_purchase_at'])->toBe('datetime');
    expect($casts['points'])->toBe('integer');
    expect($casts['points_updated_at'])->toBe('datetime');
});

test('customer factory creates valid customer data', function () {
    $customerData = Customer::factory()->make()->toArray();

    expect($customerData)->toHaveKey('name');
    expect($customerData)->toHaveKey('email');
    expect($customerData)->toHaveKey('subway_card');
    expect($customerData)->toHaveKey('birth_date');
    expect($customerData['timezone'])->toBe('America/Guatemala');
});

test('customer factory creates customer with specific attributes', function () {
    $customerData = Customer::factory()->make([
        'name' => 'Test Customer',
        'email' => 'test@customer.com',
        'customer_type_id' => null,
    ])->toArray();

    expect($customerData['name'])->toBe('Test Customer');
    expect($customerData['email'])->toBe('test@customer.com');
    expect($customerData['customer_type_id'])->toBeNull(); // New customers start without type
});

test('customer password is hashed when cast', function () {
    $customer = new Customer;
    $customer->password = 'plainpassword';

    expect($customer->password)->not->toBe('plainpassword');
});

test('customer birth_date cast is configured', function () {
    $customer = new Customer;

    expect($customer->getCasts()['birth_date'])->toBe('date');
});

test('customer uses soft deletes trait', function () {
    $customer = new Customer;

    expect($customer->getDeletedAtColumn())->toBe('deleted_at');
});

test('customer authenticatable implementation', function () {
    $customer = new Customer;

    expect($customer)->toBeInstanceOf(\Illuminate\Contracts\Auth\Authenticatable::class);
    expect($customer->getAuthIdentifierName())->toBe('id');
    expect($customer->getAuthPasswordName())->toBe('password');
    expect($customer->getRememberTokenName())->toBe('remember_token');
});

test('customer has relationship with customer type', function () {
    $customer = new Customer;

    expect($customer->customerType())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

test('customer has status attribute based on activity', function () {
    $customer = new Customer;

    // Default status when no activity
    expect($customer->status)->toBe('never');
});

test('customer has is_online attribute accessor', function () {
    $customer = new Customer;

    // Should be false when no activity
    expect($customer->is_online)->toBeFalse();
});
