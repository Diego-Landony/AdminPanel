<?php

use App\Models\Driver;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Driver Authentication', function () {
    describe('Login', function () {
        it('allows a driver to login with valid credentials', function () {
            $restaurant = Restaurant::factory()->create();
            $driver = Driver::factory()->for($restaurant)->create([
                'email' => 'driver@example.com',
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]);

            $response = $this->postJson('/api/v1/driver/auth/login', [
                'email' => 'driver@example.com',
                'password' => 'password123',
                'device_name' => 'iPhone 15 Pro',
            ]);

            $response->assertOk()
                ->assertJson(['success' => true])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'restaurant' => [
                            'id',
                            'name',
                        ],
                        'is_active',
                        'is_available',
                        'token',
                        'token_type',
                    ],
                    'message',
                ]);

            // Verify token is not empty
            expect($response->json('data.token'))->not->toBeEmpty();
            expect($response->json('data.token_type'))->toBe('Bearer');
            expect($response->json('data.email'))->toBe('driver@example.com');
        });

        it('rejects login with invalid credentials', function () {
            $driver = Driver::factory()->create([
                'email' => 'driver@example.com',
                'password' => bcrypt('password123'),
                'is_active' => true,
            ]);

            $response = $this->postJson('/api/v1/driver/auth/login', [
                'email' => 'driver@example.com',
                'password' => 'wrongpassword',
                'device_name' => 'Test Device',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        it('rejects login with non-existent email', function () {
            $response = $this->postJson('/api/v1/driver/auth/login', [
                'email' => 'nonexistent@example.com',
                'password' => 'password123',
                'device_name' => 'Test Device',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('rejects login when driver account is inactive', function () {
            $driver = Driver::factory()->inactive()->create([
                'email' => 'inactive@example.com',
                'password' => bcrypt('password123'),
            ]);

            $response = $this->postJson('/api/v1/driver/auth/login', [
                'email' => 'inactive@example.com',
                'password' => 'password123',
                'device_name' => 'Test Device',
            ]);

            $response->assertForbidden()
                ->assertJson([
                    'success' => false,
                    'error_code' => 'DRIVER_INACTIVE',
                ]);
        });

        it('requires device_name for login', function () {
            $driver = Driver::factory()->create([
                'email' => 'driver@example.com',
                'password' => bcrypt('password123'),
            ]);

            $response = $this->postJson('/api/v1/driver/auth/login', [
                'email' => 'driver@example.com',
                'password' => 'password123',
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['device_name']);
        });

        it('updates last_login_at timestamp on successful login', function () {
            $driver = Driver::factory()->create([
                'email' => 'driver@example.com',
                'password' => bcrypt('password123'),
                'last_login_at' => null,
            ]);

            $this->postJson('/api/v1/driver/auth/login', [
                'email' => 'driver@example.com',
                'password' => 'password123',
                'device_name' => 'Test Device',
            ]);

            $driver->refresh();
            expect($driver->last_login_at)->not->toBeNull();
        });
    });

    describe('Logout', function () {
        it('allows authenticated driver to logout', function () {
            $driver = Driver::factory()->create();

            Sanctum::actingAs($driver, ['driver'], 'driver');

            $response = $this->postJson('/api/v1/driver/auth/logout');

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => null,
                ]);
        });

        it('revokes current token on logout', function () {
            $driver = Driver::factory()->create();
            $token = $driver->createToken('test-token', ['driver']);

            $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
                ->postJson('/api/v1/driver/auth/logout');

            $response->assertOk();

            // Token should be revoked
            expect($driver->tokens()->count())->toBe(0);
        });

        it('rejects logout without authentication', function () {
            $response = $this->postJson('/api/v1/driver/auth/logout');

            $response->assertUnauthorized();
        });
    });

    describe('Me Endpoint', function () {
        it('returns authenticated driver data via /me endpoint', function () {
            $restaurant = Restaurant::factory()->create([
                'name' => 'Test Restaurant',
            ]);
            $driver = Driver::factory()->for($restaurant)->create([
                'name' => 'John Driver',
                'email' => 'john@example.com',
            ]);

            Sanctum::actingAs($driver, ['driver'], 'driver');

            $response = $this->getJson('/api/v1/driver/auth/me');

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $driver->id,
                        'name' => 'John Driver',
                        'email' => 'john@example.com',
                        'restaurant' => [
                            'id' => $restaurant->id,
                            'name' => 'Test Restaurant',
                        ],
                    ],
                ])
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'email',
                        'restaurant',
                        'is_active',
                        'is_available',
                        'rating',
                        'total_deliveries',
                        'created_at',
                    ],
                    'message',
                ]);
        });

        it('rejects access to protected routes without token', function () {
            $response = $this->getJson('/api/v1/driver/auth/me');

            $response->assertUnauthorized();
        });

        it('rejects access to protected routes when driver becomes inactive', function () {
            $driver = Driver::factory()->create([
                'is_active' => true,
            ]);

            // Create token while driver is active
            $token = $driver->createToken('test-token', ['driver']);

            // Deactivate the driver
            $driver->update(['is_active' => false]);

            // Try to access protected route
            $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
                ->getJson('/api/v1/driver/auth/me');

            $response->assertForbidden()
                ->assertJson([
                    'success' => false,
                    'error_code' => 'DRIVER_INACTIVE',
                ]);
        });
    });

    describe('Token Management', function () {
        it('limits tokens per driver to maximum of 3', function () {
            $driver = Driver::factory()->create([
                'email' => 'driver@example.com',
                'password' => bcrypt('password123'),
            ]);

            // Login 4 times (should keep only last 3 tokens)
            for ($i = 1; $i <= 4; $i++) {
                $this->postJson('/api/v1/driver/auth/login', [
                    'email' => 'driver@example.com',
                    'password' => 'password123',
                    'device_name' => "Device $i",
                ]);
            }

            // Should have max 3 tokens
            expect($driver->tokens()->count())->toBe(3);
        });
    });
});
