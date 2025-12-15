<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('index (GET /api/v1/addresses)', function () {
    test('returns empty list when customer has no addresses', function () {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/addresses');

        $response->assertOk()
            ->assertJson(['data' => []]);
    });

    test('returns customer addresses ordered by default first', function () {
        $customer = Customer::factory()->create();

        CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'label' => 'Trabajo',
            'is_default' => false,
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'label' => 'Casa',
            'is_default' => true,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/addresses');

        $response->assertOk();
        $data = $response->json('data');
        expect($data)->toHaveCount(2);
        expect($data[0]['label'])->toBe('Casa');
        expect($data[0]['is_default'])->toBeTrue();
    });

    test('only returns addresses belonging to authenticated customer', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        CustomerAddress::factory()->count(2)->create(['customer_id' => $customer1->id]);
        CustomerAddress::factory()->count(3)->create(['customer_id' => $customer2->id]);

        $response = $this->actingAs($customer1, 'sanctum')
            ->getJson('/api/v1/addresses');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(2);
    });

    test('requires authentication', function () {
        $response = $this->getJson('/api/v1/addresses');

        $response->assertUnauthorized();
    });
});

describe('store (POST /api/v1/addresses)', function () {
    test('creates new address successfully', function () {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses', [
                'label' => 'Casa',
                'address_line' => '5a Avenida 10-20 Zona 10, Guatemala',
                'latitude' => 14.6349,
                'longitude' => -90.5069,
                'delivery_notes' => 'Portón azul',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'label',
                    'address_line',
                    'latitude',
                    'longitude',
                ],
                'message',
            ]);

        expect($response->json('data.label'))->toBe('Casa');
    });

    test('sets as default when is_default is true', function () {
        $customer = Customer::factory()->create();

        CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses', [
                'label' => 'Nueva Casa',
                'address_line' => 'Nueva dirección',
                'latitude' => 14.6349,
                'longitude' => -90.5069,
                'is_default' => true,
            ]);

        $response->assertCreated();
        expect($response->json('data.is_default'))->toBeTrue();
        expect(CustomerAddress::where('customer_id', $customer->id)->where('is_default', true)->count())->toBe(1);
    });

    test('requires label field', function () {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses', [
                'address_line' => 'Dirección test',
                'latitude' => 14.6349,
                'longitude' => -90.5069,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['label']);
    });

    test('requires address_line field', function () {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses', [
                'label' => 'Casa',
                'latitude' => 14.6349,
                'longitude' => -90.5069,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['address_line']);
    });

    test('requires latitude field', function () {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses', [
                'label' => 'Casa',
                'address_line' => 'Dirección test',
                'longitude' => -90.5069,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude']);
    });

    test('requires longitude field', function () {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses', [
                'label' => 'Casa',
                'address_line' => 'Dirección test',
                'latitude' => 14.6349,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['longitude']);
    });

    test('requires authentication', function () {
        $response = $this->postJson('/api/v1/addresses', [
            'label' => 'Casa',
            'address_line' => 'Dirección test',
            'latitude' => 14.6349,
            'longitude' => -90.5069,
        ]);

        $response->assertUnauthorized();
    });
});

describe('show (GET /api/v1/addresses/{address})', function () {
    test('returns address details', function () {
        $customer = Customer::factory()->create();
        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'label' => 'Casa Principal',
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/addresses/{$address->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $address->id)
            ->assertJsonPath('data.label', 'Casa Principal');
    });

    test('prevents access to other customer addresses', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $address = CustomerAddress::factory()->create(['customer_id' => $customer2->id]);

        $response = $this->actingAs($customer1, 'sanctum')
            ->getJson("/api/v1/addresses/{$address->id}");

        $response->assertForbidden();
    });

    test('requires authentication', function () {
        $address = CustomerAddress::factory()->create();

        $response = $this->getJson("/api/v1/addresses/{$address->id}");

        $response->assertUnauthorized();
    });
});

describe('update (PUT /api/v1/addresses/{address})', function () {
    test('updates address successfully', function () {
        $customer = Customer::factory()->create();
        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'label' => 'Casa',
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/addresses/{$address->id}", [
                'label' => 'Casa Nueva',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.label', 'Casa Nueva');
    });

    test('updates default status', function () {
        $customer = Customer::factory()->create();

        $address1 = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
        ]);

        $address2 = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => false,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/addresses/{$address2->id}", [
                'is_default' => true,
            ]);

        $response->assertOk();
        expect($address1->fresh()->is_default)->toBeFalse();
        expect($address2->fresh()->is_default)->toBeTrue();
    });

    test('prevents access to other customer addresses', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $address = CustomerAddress::factory()->create(['customer_id' => $customer2->id]);

        $response = $this->actingAs($customer1, 'sanctum')
            ->putJson("/api/v1/addresses/{$address->id}", [
                'label' => 'New Label',
            ]);

        $response->assertForbidden();
    });
});

describe('destroy (DELETE /api/v1/addresses/{address})', function () {
    test('deletes address successfully', function () {
        $customer = Customer::factory()->create();
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/addresses/{$address->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Dirección eliminada exitosamente']);

        expect(CustomerAddress::find($address->id))->toBeNull();
    });

    test('prevents access to other customer addresses', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $address = CustomerAddress::factory()->create(['customer_id' => $customer2->id]);

        $response = $this->actingAs($customer1, 'sanctum')
            ->deleteJson("/api/v1/addresses/{$address->id}");

        $response->assertForbidden();
    });
});

describe('setDefault (POST /api/v1/addresses/{address}/set-default)', function () {
    test('sets address as default', function () {
        $customer = Customer::factory()->create();

        $address1 = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
        ]);

        $address2 = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => false,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/addresses/{$address2->id}/set-default");

        $response->assertOk()
            ->assertJsonPath('data.is_default', true);

        expect($address1->fresh()->is_default)->toBeFalse();
        expect($address2->fresh()->is_default)->toBeTrue();
    });

    test('prevents access to other customer addresses', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $address = CustomerAddress::factory()->create(['customer_id' => $customer2->id]);

        $response = $this->actingAs($customer1, 'sanctum')
            ->postJson("/api/v1/addresses/{$address->id}/set-default");

        $response->assertForbidden();
    });
});

describe('validateLocation (POST /api/v1/addresses/validate)', function () {
    test('requires latitude field', function () {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses/validate', [
                'longitude' => -90.5069,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude']);
    });

    test('requires longitude field', function () {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses/validate', [
                'latitude' => 14.6349,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['longitude']);
    });

    test('requires authentication', function () {
        $response = $this->postJson('/api/v1/addresses/validate', [
            'latitude' => 14.6349,
            'longitude' => -90.5069,
        ]);

        $response->assertUnauthorized();
    });
});
