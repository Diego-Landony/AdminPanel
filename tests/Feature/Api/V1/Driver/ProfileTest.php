<?php

use App\Models\Driver;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Driver Profile - Show', function () {
    it('returns driver profile with stats', function () {
        $restaurant = Restaurant::factory()->create([
            'name' => 'Test Restaurant',
            'address' => '123 Main St',
            'phone' => '555-1234',
        ]);
        $driver = Driver::factory()->for($restaurant)->create([
            'name' => 'John Driver',
            'email' => 'john@example.com',
        ]);

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->getJson('/api/v1/driver/profile');

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
                    'restaurant' => [
                        'id',
                        'name',
                        'address',
                        'phone',
                    ],
                    'is_active',
                    'is_available',
                    'rating',
                    'total_deliveries',
                    'created_at',
                    'stats' => [
                        'deliveries_today',
                        'average_delivery_time',
                        'rating',
                        'total_deliveries',
                    ],
                    'has_active_order',
                ],
                'message',
            ]);

        expect($response->json('data.has_active_order'))->toBeFalse();
    });

    it('rejects access without authentication', function () {
        $response = $this->getJson('/api/v1/driver/profile');

        $response->assertUnauthorized();
    });
});

describe('Driver Profile - Update', function () {
    it('returns success even with empty update', function () {
        $driver = Driver::factory()->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->putJson('/api/v1/driver/profile', []);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);
    });
});

describe('Driver Profile - Password', function () {
    it('changes password with valid current password', function () {
        $driver = Driver::factory()->create([
            'password' => bcrypt('oldpassword123'),
        ]);

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->putJson('/api/v1/driver/profile/password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        // Verify new password works
        expect(Hash::check('newpassword456', $driver->fresh()->password))->toBeTrue();
        // Verify old password doesn't work
        expect(Hash::check('oldpassword123', $driver->fresh()->password))->toBeFalse();
    });

    it('rejects password change with invalid current password', function () {
        $driver = Driver::factory()->create([
            'password' => bcrypt('correctpassword'),
        ]);

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->putJson('/api/v1/driver/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'INVALID_PASSWORD',
            ]);
    });

    it('validates password confirmation', function () {
        $driver = Driver::factory()->create([
            'password' => bcrypt('oldpassword123'),
        ]);

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->putJson('/api/v1/driver/profile/password', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword456',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('validates minimum password length', function () {
        $driver = Driver::factory()->create([
            'password' => bcrypt('oldpassword123'),
        ]);

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->putJson('/api/v1/driver/profile/password', [
            'current_password' => 'oldpassword123',
            'password' => 'short', // Less than 8 characters
            'password_confirmation' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });
});
