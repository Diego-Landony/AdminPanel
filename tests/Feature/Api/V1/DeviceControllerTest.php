<?php

use App\Models\Customer;
use App\Models\CustomerDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('index (GET /api/v1/devices)', function () {
    test('returns only active devices for authenticated customer', function () {
        $customer = Customer::factory()->create();

        // Create active devices
        CustomerDevice::factory()->count(2)->active()->create([
            'customer_id' => $customer->id,
        ]);

        // Create inactive device (should not be returned)
        CustomerDevice::factory()->inactive()->create([
            'customer_id' => $customer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/devices');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    test('orders devices by last_used_at descending', function () {
        $customer = Customer::factory()->create();

        $oldDevice = CustomerDevice::factory()->active()->create([
            'customer_id' => $customer->id,
            'device_name' => 'Old Device',
            'last_used_at' => now()->subDays(5),
        ]);

        $newDevice = CustomerDevice::factory()->active()->create([
            'customer_id' => $customer->id,
            'device_name' => 'New Device',
            'last_used_at' => now(),
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/devices');

        $response->assertOk();
        expect($response->json('data.0.id'))->toBe($newDevice->id);
        expect($response->json('data.1.id'))->toBe($oldDevice->id);
    });

    test('does not return devices from other customers', function () {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        CustomerDevice::factory()->active()->create([
            'customer_id' => $customer->id,
        ]);

        CustomerDevice::factory()->active()->create([
            'customer_id' => $otherCustomer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/devices');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    test('requires authentication', function () {
        $response = $this->getJson('/api/v1/devices');

        $response->assertUnauthorized();
    });
});

describe('register (POST /api/v1/devices/register)', function () {
    test('registers new device with fcm token', function () {
        $customer = Customer::factory()->create();

        // Create a token for the customer
        $token = $customer->createToken('test-device');

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/devices/register', [
                'fcm_token' => 'test-fcm-token-123',
                'device_identifier' => 'test-device-uuid-123',
                'device_name' => 'iPhone de Test',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Dispositivo registrado exitosamente.')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'device_name',
                    'is_active',
                ],
            ]);

        $this->assertDatabaseHas('customer_devices', [
            'customer_id' => $customer->id,
            'fcm_token' => 'test-fcm-token-123',
            'device_identifier' => 'test-device-uuid-123',
            'device_name' => 'iPhone de Test',
        ]);
    });

    test('updates existing device by device_identifier', function () {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('test-device');

        $existingDevice = CustomerDevice::factory()->create([
            'customer_id' => $customer->id,
            'device_identifier' => 'existing-device-uuid',
            'fcm_token' => 'old-fcm-token',
            'device_name' => 'Old Name',
            'login_count' => 5,
        ]);

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/devices/register', [
                'fcm_token' => 'new-fcm-token',
                'device_identifier' => 'existing-device-uuid',
                'device_name' => 'New Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Dispositivo actualizado exitosamente.');

        $existingDevice->refresh();
        expect($existingDevice->fcm_token)->toBe('new-fcm-token');
        expect($existingDevice->device_name)->toBe('New Name');
        expect($existingDevice->login_count)->toBe(6);
    });

    test('validates required fields', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/devices/register', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['fcm_token', 'device_identifier', 'device_name']);
    });

    test('requires authentication', function () {
        $response = $this->postJson('/api/v1/devices/register', [
            'fcm_token' => 'test-fcm-token',
            'device_identifier' => 'test-uuid',
            'device_name' => 'Test Device',
        ]);

        $response->assertUnauthorized();
    });
});

describe('updateFcmToken (PATCH /api/v1/devices/{device}/fcm-token)', function () {
    test('updates fcm token for own device', function () {
        $customer = Customer::factory()->create();

        $device = CustomerDevice::factory()->create([
            'customer_id' => $customer->id,
            'fcm_token' => 'old-token',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->patchJson("/api/v1/devices/{$device->id}/fcm-token", [
                'fcm_token' => 'new-fcm-token-456',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Token FCM actualizado exitosamente.');

        $device->refresh();
        expect($device->fcm_token)->toBe('new-fcm-token-456');
    });

    test('updates last_used_at when updating fcm token', function () {
        $customer = Customer::factory()->create();

        $oldDate = now()->subDays(10);
        $device = CustomerDevice::factory()->create([
            'customer_id' => $customer->id,
            'last_used_at' => $oldDate,
        ]);

        actingAs($customer, 'sanctum')
            ->patchJson("/api/v1/devices/{$device->id}/fcm-token", [
                'fcm_token' => 'new-token',
            ]);

        $device->refresh();
        expect($device->last_used_at->isToday())->toBeTrue();
    });

    test('returns 403 when updating device of another customer', function () {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $otherDevice = CustomerDevice::factory()->create([
            'customer_id' => $otherCustomer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->patchJson("/api/v1/devices/{$otherDevice->id}/fcm-token", [
                'fcm_token' => 'malicious-token',
            ]);

        $response->assertForbidden();
    });

    test('validates fcm_token is required', function () {
        $customer = Customer::factory()->create();

        $device = CustomerDevice::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->patchJson("/api/v1/devices/{$device->id}/fcm-token", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['fcm_token']);
    });

    test('requires authentication', function () {
        $device = CustomerDevice::factory()->create();

        $response = $this->patchJson("/api/v1/devices/{$device->id}/fcm-token", [
            'fcm_token' => 'test-token',
        ]);

        $response->assertUnauthorized();
    });
});

describe('destroy (DELETE /api/v1/devices/{device})', function () {
    test('deactivates own device', function () {
        $customer = Customer::factory()->create();

        $device = CustomerDevice::factory()->active()->create([
            'customer_id' => $customer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/devices/{$device->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Dispositivo desactivado exitosamente.');

        $device->refresh();
        expect($device->is_active)->toBeFalse();
    });

    test('returns 403 when deleting device of another customer', function () {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $otherDevice = CustomerDevice::factory()->create([
            'customer_id' => $otherCustomer->id,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/devices/{$otherDevice->id}");

        $response->assertForbidden()
            ->assertJsonPath('message', 'No tienes permiso para eliminar este dispositivo.');
    });

    test('returns 404 for non-existent device', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->deleteJson('/api/v1/devices/99999');

        $response->assertNotFound();
    });

    test('requires authentication', function () {
        $device = CustomerDevice::factory()->create();

        $response = $this->deleteJson("/api/v1/devices/{$device->id}");

        $response->assertUnauthorized();
    });
});
