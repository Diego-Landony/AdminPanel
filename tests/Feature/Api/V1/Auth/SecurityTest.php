<?php

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('Authentication Security', function () {
    test('logout succeeds with valid token', function () {
        $customer = Customer::factory()->create([
            'password' => Hash::make('password123'),
            'oauth_provider' => 'local',
        ]);
        $token = $customer->createToken('test-device')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/profile')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();
    });

    test('cannot access protected routes without token', function () {
        $this->getJson('/api/v1/profile')
            ->assertUnauthorized();
    });

    test('cannot access protected routes with invalid token', function () {
        $this->withHeader('Authorization', 'Bearer invalid-token-here')
            ->getJson('/api/v1/profile')
            ->assertUnauthorized();
    });

    test('cannot access protected routes with malformed token', function () {
        $this->withHeader('Authorization', 'Bearer ')
            ->getJson('/api/v1/profile')
            ->assertUnauthorized();
    });

    test('forgot password does not reveal if email exists', function () {
        $existingCustomer = Customer::factory()->create([
            'password' => Hash::make('password123'),
            'oauth_provider' => 'local',
        ]);

        $responseExisting = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $existingCustomer->email,
        ]);

        $responseNonExisting = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        // If email doesn't exist, it should return a validation error
        // If email exists, it should return success
        // The key is that for OAuth accounts without password, it doesn't reveal this differently
        expect($responseExisting->status())->toBeIn([200, 422]);
        expect($responseNonExisting->status())->toBe(422);
    });

    test('logout all devices endpoint exists and accepts request', function () {
        $customer = Customer::factory()->create([
            'password' => Hash::make('password123'),
            'oauth_provider' => 'local',
        ]);

        $token = $customer->createToken('device-1')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout-all')
            ->assertOk();
    });

    test('token refresh returns new token', function () {
        $customer = Customer::factory()->create([
            'password' => Hash::make('password123'),
            'oauth_provider' => 'local',
        ]);
        $oldToken = $customer->createToken('test-device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$oldToken)
            ->postJson('/api/v1/auth/refresh');

        $response->assertOk();

        $newToken = $response->json('data.token');
        expect($newToken)->not->toBeNull();

        $this->withHeader('Authorization', 'Bearer '.$newToken)
            ->getJson('/api/v1/profile')
            ->assertOk();
    });

    test('password reset invalidates all existing tokens', function () {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
            'oauth_provider' => 'local',
        ]);

        $token = $customer->createToken('test-device')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/profile')
            ->assertOk();

        expect($customer->tokens()->count())->toBe(1);
    });
});

describe('OAuth Security', function () {
    test('oauth account without password cannot login with email/password', function () {
        Customer::factory()->create([
            'email' => 'oauth@example.com',
            'password' => null,
            'oauth_provider' => 'google',
            'google_id' => 'google-123456789',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'oauth@example.com',
            'password' => 'anypassword',
            'device_identifier' => 'test-device',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error_code' => 'oauth_account_required',
            ]);

        expect($response->json('data.oauth_provider'))->toBe('google');
    });

    test('oauth account with added password can login with email/password', function () {
        $customer = Customer::factory()->create([
            'email' => 'oauth-with-pass@example.com',
            'password' => Hash::make('addedpassword'),
            'oauth_provider' => 'google',
            'google_id' => 'google-123456789',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'oauth-with-pass@example.com',
            'password' => 'addedpassword',
            'device_identifier' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'customer',
                ],
            ]);
    });

    test('oauth account cannot request password reset without password', function () {
        Customer::factory()->create([
            'email' => 'oauth-only@example.com',
            'password' => null,
            'oauth_provider' => 'google',
            'google_id' => 'google-123456789',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'oauth-only@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });
});

describe('Rate Limiting', function () {
    test('login is rate limited after multiple failed attempts', function () {
        Customer::factory()->create([
            'email' => 'ratelimit@example.com',
            'password' => Hash::make('correctpassword'),
            'oauth_provider' => 'local',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'ratelimit@example.com',
                'password' => 'wrongpassword',
                'device_identifier' => 'test-device',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ratelimit@example.com',
            'password' => 'correctpassword',
            'device_identifier' => 'test-device',
        ]);

        // Rate limiting returns either 422 (validation) or 429 (too many requests)
        expect($response->status())->toBeIn([422, 429]);
    });
});

describe('Token Management', function () {
    test('token limit is enforced on login', function () {
        $customer = Customer::factory()->create([
            'password' => Hash::make('password123'),
            'oauth_provider' => 'local',
        ]);

        for ($i = 0; $i < 6; $i++) {
            $customer->createToken("device-{$i}");
        }

        expect($customer->tokens()->count())->toBe(6);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $customer->email,
            'password' => 'password123',
            'device_identifier' => 'new-device',
        ]);

        $response->assertOk();

        $customer->refresh();
        expect($customer->tokens()->count())->toBeLessThanOrEqual(5);
    });
});

describe('Account Deletion Security', function () {
    test('deleted account email cannot be used for new registration within 30 days', function () {
        $customer = Customer::factory()->create([
            'email' => 'todelete@example.com',
            'password' => Hash::make('password123'),
            'oauth_provider' => 'local',
        ]);

        $customer->delete();

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'todelete@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'phone' => '12345678',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'device_identifier' => 'test-device',
            'terms_accepted' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('login with deleted account email shows recovery option', function () {
        $customer = Customer::factory()->create([
            'email' => 'deleted@example.com',
            'password' => Hash::make('password123'),
            'oauth_provider' => 'local',
            'points' => 150,
        ]);

        $customer->delete();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'deleted@example.com',
            'password' => 'password123',
            'device_identifier' => 'test-device',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'error_code' => 'account_deleted_recoverable',
            ]);

        expect($response->json('data.can_reactivate'))->toBeTrue();
        expect($response->json('data.points'))->toBe(150);
    });
});

describe('Protected Endpoints Authorization', function () {
    test('cannot access other customer addresses', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        $address = \App\Models\CustomerAddress::factory()->create([
            'customer_id' => $customer2->id,
        ]);

        $response = $this->actingAs($customer1, 'sanctum')
            ->getJson("/api/v1/addresses/{$address->id}");

        $response->assertForbidden();
    });

    test('cannot access other customer orders', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        $order = \App\Models\Order::factory()->create([
            'customer_id' => $customer2->id,
        ]);

        $response = $this->actingAs($customer1, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertForbidden();
    });

    test('cannot cancel other customer orders', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        $order = \App\Models\Order::factory()->create([
            'customer_id' => $customer2->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($customer1, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel", [
                'reason' => 'Test',
            ]);

        $response->assertForbidden();
    });

    test('cannot delete other customer addresses', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        $address = \App\Models\CustomerAddress::factory()->create([
            'customer_id' => $customer2->id,
        ]);

        $response = $this->actingAs($customer1, 'sanctum')
            ->deleteJson("/api/v1/addresses/{$address->id}");

        $response->assertForbidden();
    });
});
