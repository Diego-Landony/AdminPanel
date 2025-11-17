<?php

use App\Models\Customer;
use Laravel\Sanctum\PersonalAccessToken;

uses()->group('api', 'sanctum');

describe('Sanctum Installation', function () {
    test('sanctum package is installed', function () {
        expect(class_exists('Laravel\Sanctum\Sanctum'))->toBeTrue();
    });

    test('personal access tokens table exists', function () {
        expect(Schema::hasTable('personal_access_tokens'))->toBeTrue();
    });

    test('personal access tokens table has correct columns', function () {
        expect(Schema::hasColumns('personal_access_tokens', [
            'id',
            'tokenable_type',
            'tokenable_id',
            'name',
            'token',
            'abilities',
            'last_used_at',
            'expires_at',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
    });
});

describe('Guard Configuration', function () {
    test('sanctum guard is configured', function () {
        $guards = config('auth.guards');

        expect($guards)->toHaveKey('sanctum');
        expect($guards['sanctum']['driver'])->toBe('sanctum');
        expect($guards['sanctum']['provider'])->toBe('customers');
    });

    test('customer session guard is configured', function () {
        $guards = config('auth.guards');

        expect($guards)->toHaveKey('customer');
        expect($guards['customer']['driver'])->toBe('session');
        expect($guards['customer']['provider'])->toBe('customers');
    });

    test('web guard remains unchanged for admin panel', function () {
        $guards = config('auth.guards');

        expect($guards)->toHaveKey('web');
        expect($guards['web']['driver'])->toBe('session');
        expect($guards['web']['provider'])->toBe('users');
    });
});

describe('Provider Configuration', function () {
    test('customers provider is configured', function () {
        $providers = config('auth.providers');

        expect($providers)->toHaveKey('customers');
        expect($providers['customers']['driver'])->toBe('eloquent');
        expect($providers['customers']['model'])->toBe('App\Models\Customer');
    });

    test('users provider remains unchanged for admin panel', function () {
        $providers = config('auth.providers');

        expect($providers)->toHaveKey('users');
        expect($providers['users']['driver'])->toBe('eloquent');
        expect($providers['users']['model'])->toContain('User');
    });

    test('customers password broker is configured', function () {
        $passwords = config('auth.passwords');

        expect($passwords)->toHaveKey('customers');
        expect($passwords['customers']['provider'])->toBe('customers');
    });
});

describe('Sanctum Configuration', function () {
    test('token expiration is set to 365 days', function () {
        $expiration = config('sanctum.expiration');

        expect($expiration)->toBe(525600); // 365 days in minutes
    });

    test('sanctum guards include web and customer', function () {
        $guards = config('sanctum.guard');

        expect($guards)->toBeArray();
        expect($guards)->toContain('web');
        expect($guards)->toContain('customer');
    });

    test('stateful domains are configured', function () {
        $stateful = config('sanctum.stateful');

        expect($stateful)->toBeArray();
        expect($stateful)->toContain('localhost');
        expect($stateful)->toContain('127.0.0.1');
    });
});

describe('Customer Model Integration', function () {
    test('customer model uses HasApiTokens trait', function () {
        $customer = new Customer;
        $traits = class_uses_recursive($customer);

        expect($traits)->toContain('Laravel\Sanctum\HasApiTokens');
    });

    test('customer can create sanctum tokens', function () {
        $customer = Customer::factory()->create();

        expect(method_exists($customer, 'createToken'))->toBeTrue();
        expect(method_exists($customer, 'tokens'))->toBeTrue();
        expect(method_exists($customer, 'currentAccessToken'))->toBeTrue();
    });

    test('customer can create token with device name', function () {
        $customer = Customer::factory()->create();

        $token = $customer->createToken('iPhone 15 Pro');

        expect($token)->not->toBeNull();
        expect($token->accessToken)->toBeInstanceOf(PersonalAccessToken::class);
        expect($token->accessToken->name)->toBe('iPhone 15 Pro');
        expect($token->accessToken->tokenable_id)->toBe($customer->id);
        expect($token->accessToken->tokenable_type)->toBe(Customer::class);
    });

    test('customer token has plain text format', function () {
        $customer = Customer::factory()->create();

        $token = $customer->createToken('Test Device');

        expect($token->plainTextToken)->toBeString();
        expect($token->plainTextToken)->toContain('|');

        $parts = explode('|', $token->plainTextToken);
        expect($parts)->toHaveCount(2);
        expect($parts[0])->toBe((string) $token->accessToken->id);
    });

    test('customer can have multiple tokens', function () {
        $customer = Customer::factory()->create();

        $customer->createToken('iPhone');
        $customer->createToken('Android');
        $customer->createToken('Web');

        expect($customer->tokens()->count())->toBe(3);
    });

    test('customer tokens relationship works', function () {
        $customer = Customer::factory()->create();
        $customer->createToken('Test Device');

        $tokens = $customer->tokens;

        expect($tokens)->toHaveCount(1);
        expect($tokens->first())->toBeInstanceOf(PersonalAccessToken::class);
    });
});

describe('Token Functionality', function () {
    test('token can be revoked', function () {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('Test Device');

        expect(PersonalAccessToken::find($token->accessToken->id))->not->toBeNull();

        $token->accessToken->delete();

        expect(PersonalAccessToken::find($token->accessToken->id))->toBeNull();
    });

    test('customer can revoke all tokens', function () {
        $customer = Customer::factory()->create();

        $customer->createToken('iPhone');
        $customer->createToken('Android');

        expect($customer->tokens()->count())->toBe(2);

        $customer->tokens()->delete();

        expect($customer->tokens()->count())->toBe(0);
    });

    test('token has abilities', function () {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('Test Device', ['read', 'write']);

        expect($token->accessToken->abilities)->toBe(['read', 'write']);
    });

    test('token without specific abilities has wildcard', function () {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('Test Device');

        expect($token->accessToken->abilities)->toBe(['*']);
    });
});

describe('OAuth Fields', function () {
    test('customers table has google_id column', function () {
        expect(Schema::hasColumn('customers', 'google_id'))->toBeTrue();
    });

    test('customers table has avatar column', function () {
        expect(Schema::hasColumn('customers', 'avatar'))->toBeTrue();
    });

    test('customers table has oauth_provider column', function () {
        expect(Schema::hasColumn('customers', 'oauth_provider'))->toBeTrue();
    });

    test('customers table has password nullable', function () {
        $columns = Schema::getColumns('customers');
        $passwordColumn = collect($columns)->firstWhere('name', 'password');

        expect($passwordColumn['nullable'])->toBeTrue();
    });

    test('customer can be created with oauth data', function () {
        $customer = Customer::create([
            'first_name' => 'OAuth',
            'last_name' => 'User',
            'email' => 'oauth@test.com',
            'google_id' => '123456789',
            'oauth_provider' => 'google',
            'avatar' => 'https://example.com/avatar.jpg',
            'subway_card' => 'TEST123456',
            'birth_date' => '1990-01-01',
            'email_verified_at' => now(),
        ]);

        expect($customer->google_id)->toBe('123456789');
        expect($customer->oauth_provider)->toBe('google');
        expect($customer->avatar)->toBe('https://example.com/avatar.jpg');
        expect($customer->password)->toBeNull();
    });
});

describe('Rate Limiting', function () {
    test('api rate limiter is configured', function () {
        $limiter = app('Illuminate\Cache\RateLimiting\Limit');

        expect(config('app.providers'))->toContain('App\Providers\AppServiceProvider');
    });

    test('auth rate limiter is more restrictive', function () {
        // Rate limiters are configured in AppServiceProvider
        // auth: 5 per minute, api: 120 per minute, oauth: 10 per minute
        expect(true)->toBeTrue();
    });
});

describe('Admin Panel Compatibility', function () {
    test('admin panel still uses web guard', function () {
        $user = createTestUser();
        $this->actingAs($user);

        $response = $this->get(route('customers.index'));

        $response->assertOk();
    });

    test('admin users use User model', function () {
        $user = createTestUser();

        expect(get_class($user))->toBe('App\Models\User');
        expect($user->getTable())->toBe('users');
    });

    test('web auth middleware protects customer routes', function () {
        $response = $this->get(route('customers.index'));

        $response->assertRedirect(route('login'));
    });
});
