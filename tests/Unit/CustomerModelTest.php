<?php

use App\Models\Customer;

/**
 * Test suite para el modelo Customer
 */

test('customer has fillable attributes', function () {
    $customer = new Customer();
    
    $expectedFillable = [
        'full_name',
        'email',
        'password',
        'subway_card',
        'birth_date',
        'gender',
        'client_type',
        'phone',
        'address',
        'location',
        'nit',
        'fcm_token',
        'last_login_at',
        'last_activity_at',
        'last_purchase_at',
        'puntos',
        'puntos_updated_at',
        'timezone',
        'customer_type_id',
    ];
    
    expect($customer->getFillable())->toEqual($expectedFillable);
});

test('customer has hidden attributes', function () {
    $customer = new Customer();
    
    $expectedHidden = [
        'password',
        'remember_token',
    ];
    
    expect($customer->getHidden())->toEqual($expectedHidden);
});

test('customer casts attributes correctly', function () {
    $customer = new Customer();
    
    $expectedCasts = [
        'id' => 'int',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'last_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'last_purchase_at' => 'datetime',
        'puntos' => 'integer',
        'puntos_updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    expect($customer->getCasts())->toEqual($expectedCasts);
});

test('customer factory creates valid customer', function () {
    $customer = Customer::factory()->create();
    
    expect($customer)->toBeInstanceOf(Customer::class);
    expect($customer->full_name)->not->toBeEmpty();
    expect($customer->email)->not->toBeEmpty();
    expect($customer->subway_card)->not->toBeEmpty();
    expect($customer->birth_date)->not->toBeNull();
    expect($customer->timezone)->toBe('America/Guatemala');
});

test('customer factory creates customer with specific attributes', function () {
    $customer = Customer::factory()->create([
        'full_name' => 'Test Customer',
        'email' => 'test@customer.com',
        'client_type' => 'vip',
    ]);
    
    expect($customer->full_name)->toBe('Test Customer');
    expect($customer->email)->toBe('test@customer.com');
    expect($customer->client_type)->toBe('vip');
});

test('customer email must be unique', function () {
    Customer::factory()->create(['email' => 'unique@test.com']);
    
    $this->expectException(\Illuminate\Database\QueryException::class);
    
    Customer::factory()->create(['email' => 'unique@test.com']);
});

test('customer subway_card must be unique', function () {
    Customer::factory()->create(['subway_card' => '1234567890']);
    
    $this->expectException(\Illuminate\Database\QueryException::class);
    
    Customer::factory()->create(['subway_card' => '1234567890']);
});

test('customer password is automatically hashed', function () {
    $customer = Customer::factory()->create(['password' => 'plainpassword']);
    
    expect($customer->password)->not->toBe('plainpassword');
    expect(\Hash::check('plainpassword', $customer->password))->toBeTrue();
});

test('customer birth_date is cast to carbon date', function () {
    $customer = Customer::factory()->create(['birth_date' => '1990-05-15']);
    
    expect($customer->birth_date)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($customer->birth_date->format('Y-m-d'))->toBe('1990-05-15');
});

test('customer uses soft deletes', function () {
    $customer = Customer::factory()->create();
    
    expect($customer->getDeletedAtColumn())->toBe('deleted_at');
    
    // Test soft delete
    $customer->delete();
    
    expect($customer->trashed())->toBeTrue();
    expect(Customer::count())->toBe(0);
    expect(Customer::withTrashed()->count())->toBe(1);
});

test('customer authenticatable implementation', function () {
    $customer = Customer::factory()->create();
    
    expect($customer)->toBeInstanceOf(\Illuminate\Contracts\Auth\Authenticatable::class);
    expect($customer->getAuthIdentifierName())->toBe('id');
    expect($customer->getAuthIdentifier())->toBe($customer->id);
    expect($customer->getAuthPasswordName())->toBe('password');
    expect($customer->getAuthPassword())->toBe($customer->password);
});

test('customer remember token functionality', function () {
    $customer = Customer::factory()->create();
    
    $token = 'test_remember_token';
    $customer->setRememberToken($token);
    
    expect($customer->getRememberToken())->toBe($token);
    expect($customer->getRememberTokenName())->toBe('remember_token');
});

test('customer default values', function () {
    $customer = new Customer();
    $customer->full_name = 'Test Customer';
    $customer->email = 'test@example.com';
    $customer->password = 'password';
    $customer->subway_card = '1234567890';
    $customer->birth_date = '1990-01-01';
    $customer->save();
    
    // Verificar valores por defecto
    expect($customer->timezone)->toBe('America/Guatemala');
    expect($customer->client_type)->toBeNull(); // Se asigna en el controller, no en el modelo
});

test('customer can have different client types', function () {
    $regular = Customer::factory()->create(['client_type' => 'regular']);
    $premium = Customer::factory()->create(['client_type' => 'premium']);
    $vip = Customer::factory()->create(['client_type' => 'vip']);
    
    expect($regular->client_type)->toBe('regular');
    expect($premium->client_type)->toBe('premium');
    expect($vip->client_type)->toBe('vip');
});

test('customer can have different genders', function () {
    $male = Customer::factory()->create(['gender' => 'masculino']);
    $female = Customer::factory()->create(['gender' => 'femenino']);
    $other = Customer::factory()->create(['gender' => 'otro']);
    
    expect($male->gender)->toBe('masculino');
    expect($female->gender)->toBe('femenino');
    expect($other->gender)->toBe('otro');
});

test('customer activity timestamps are nullable', function () {
    $customer = Customer::factory()->create([
        'last_login_at' => null,
        'last_activity_at' => null,
        'last_purchase_at' => null,
    ]);
    
    expect($customer->last_login_at)->toBeNull();
    expect($customer->last_activity_at)->toBeNull();
    expect($customer->last_purchase_at)->toBeNull();
});

test('customer activity timestamps can be set', function () {
    $now = now();
    $customer = Customer::factory()->create([
        'last_login_at' => $now,
        'last_activity_at' => $now,
        'last_purchase_at' => $now,
    ]);
    
    expect($customer->last_login_at)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($customer->last_activity_at)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($customer->last_purchase_at)->toBeInstanceOf(\Carbon\Carbon::class);
});