<?php

use App\Models\Customer;
use App\Models\CustomerNit;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('index (GET /api/v1/nits)', function () {
    test('returns empty list when customer has no NITs', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/nits');

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJson(['data' => []]);
    });

    test('returns customer NITs ordered by default first', function () {
        $customer = Customer::factory()->create();

        CustomerNit::factory()->create([
            'customer_id' => $customer->id,
            'nit' => '1234567890',
            'is_default' => false,
        ]);

        CustomerNit::factory()->create([
            'customer_id' => $customer->id,
            'nit' => '9876543210',
            'is_default' => true,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson('/api/v1/nits');

        $response->assertOk();
        $data = $response->json('data');

        expect($data)->toHaveCount(2);
        expect($data[0]['nit'])->toBe('9876543210');
        expect($data[0]['is_default'])->toBeTrue();
    });

    test('requires authentication', function () {
        $response = $this->getJson('/api/v1/nits');

        $response->assertUnauthorized();
    });
});

describe('store (POST /api/v1/nits)', function () {
    test('creates new NIT successfully', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/nits', [
                'nit' => '1234567890',
                'nit_type' => 'personal',
                'nit_name' => 'Juan Pérez',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'nit',
                    'nit_type',
                    'nit_name',
                    'is_default',
                    'created_at',
                ],
                'message',
            ]);

        expect($response->json('data.nit'))->toBe('1234567890');
        expect($response->json('data.nit_type'))->toBe('personal');
        expect($response->json('data.nit_name'))->toBe('Juan Pérez');
    });

    test('sets as default when is_default is true', function () {
        $customer = Customer::factory()->create();

        CustomerNit::factory()->create([
            'customer_id' => $customer->id,
            'nit' => '1111111111',
            'is_default' => true,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/nits', [
                'nit' => '2222222222',
                'nit_type' => 'company',
                'is_default' => true,
            ]);

        $response->assertCreated();

        expect(CustomerNit::where('nit', '1111111111')->first()->is_default)->toBeFalse();
        expect(CustomerNit::where('nit', '2222222222')->first()->is_default)->toBeTrue();
    });

    test('requires nit field', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/nits', [
                'nit_type' => 'personal',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nit']);
    });

    test('validates nit max length', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/nits', [
                'nit' => str_repeat('1', 21),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nit']);
    });

    test('prevents duplicate NITs for same customer', function () {
        $customer = Customer::factory()->create();

        CustomerNit::factory()->create([
            'customer_id' => $customer->id,
            'nit' => '1234567890',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/nits', [
                'nit' => '1234567890',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nit']);
    });

    test('allows same NIT for different customers', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        CustomerNit::factory()->create([
            'customer_id' => $customer1->id,
            'nit' => '1234567890',
        ]);

        $response = actingAs($customer2, 'sanctum')
            ->postJson('/api/v1/nits', [
                'nit' => '1234567890',
            ]);

        $response->assertCreated();
    });

    test('validates nit_type enum values', function () {
        $customer = Customer::factory()->create();

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/nits', [
                'nit' => '1234567890',
                'nit_type' => 'invalid',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nit_type']);
    });

    test('requires authentication', function () {
        $response = $this->postJson('/api/v1/nits', [
            'nit' => '1234567890',
        ]);

        $response->assertUnauthorized();
    });
});

describe('show (GET /api/v1/nits/{nit})', function () {
    test('returns NIT details', function () {
        $customer = Customer::factory()->create();
        $nit = CustomerNit::factory()->create([
            'customer_id' => $customer->id,
            'nit' => '1234567890',
            'nit_type' => 'company',
            'nit_name' => 'Test Company',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->getJson("/api/v1/nits/{$nit->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $nit->id,
                    'nit' => '1234567890',
                    'nit_type' => 'company',
                    'nit_name' => 'Test Company',
                ],
            ]);
    });

    test('prevents access to other customer NITs', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $nit = CustomerNit::factory()->create(['customer_id' => $customer2->id]);

        $response = actingAs($customer1, 'sanctum')
            ->getJson("/api/v1/nits/{$nit->id}");

        $response->assertForbidden();
    });

    test('requires authentication', function () {
        $nit = CustomerNit::factory()->create();

        $response = $this->getJson("/api/v1/nits/{$nit->id}");

        $response->assertUnauthorized();
    });
});

describe('update (PUT /api/v1/nits/{nit})', function () {
    test('updates NIT successfully', function () {
        $customer = Customer::factory()->create();
        $nit = CustomerNit::factory()->create([
            'customer_id' => $customer->id,
            'nit' => '1234567890',
            'nit_type' => 'personal',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->putJson("/api/v1/nits/{$nit->id}", [
                'nit' => '9876543210',
                'nit_type' => 'company',
                'nit_name' => 'Updated Company',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'nit' => '9876543210',
                    'nit_type' => 'company',
                    'nit_name' => 'Updated Company',
                ],
                'message' => 'NIT actualizado exitosamente',
            ]);
    });

    test('updates default status', function () {
        $customer = Customer::factory()->create();

        $nit1 = CustomerNit::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
        ]);

        $nit2 = CustomerNit::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => false,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->putJson("/api/v1/nits/{$nit2->id}", [
                'nit' => $nit2->nit,
                'is_default' => true,
            ]);

        $response->assertOk();

        expect($nit1->fresh()->is_default)->toBeFalse();
        expect($nit2->fresh()->is_default)->toBeTrue();
    });

    test('allows same NIT when updating own record', function () {
        $customer = Customer::factory()->create();
        $nit = CustomerNit::factory()->create([
            'customer_id' => $customer->id,
            'nit' => '1234567890',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->putJson("/api/v1/nits/{$nit->id}", [
                'nit' => '1234567890',
                'nit_name' => 'Updated Name',
            ]);

        $response->assertOk();
    });

    test('prevents duplicate NITs with other records', function () {
        $customer = Customer::factory()->create();

        CustomerNit::factory()->create([
            'customer_id' => $customer->id,
            'nit' => '1111111111',
        ]);

        $nit = CustomerNit::factory()->create([
            'customer_id' => $customer->id,
            'nit' => '2222222222',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->putJson("/api/v1/nits/{$nit->id}", [
                'nit' => '1111111111',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nit']);
    });

    test('prevents access to other customer NITs', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $nit = CustomerNit::factory()->create(['customer_id' => $customer2->id]);

        $response = actingAs($customer1, 'sanctum')
            ->putJson("/api/v1/nits/{$nit->id}", [
                'nit' => '9999999999',
            ]);

        $response->assertForbidden();
    });

    test('requires authentication', function () {
        $nit = CustomerNit::factory()->create();

        $response = $this->putJson("/api/v1/nits/{$nit->id}", [
            'nit' => '9999999999',
        ]);

        $response->assertUnauthorized();
    });
});

describe('destroy (DELETE /api/v1/nits/{nit})', function () {
    test('deletes NIT successfully', function () {
        $customer = Customer::factory()->create();
        $nit = CustomerNit::factory()->create(['customer_id' => $customer->id]);

        $response = actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/nits/{$nit->id}");

        $response->assertOk()
            ->assertJson(['message' => 'NIT eliminado exitosamente']);

        expect(CustomerNit::find($nit->id))->toBeNull();
    });

    test('prevents access to other customer NITs', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $nit = CustomerNit::factory()->create(['customer_id' => $customer2->id]);

        $response = actingAs($customer1, 'sanctum')
            ->deleteJson("/api/v1/nits/{$nit->id}");

        $response->assertForbidden();
    });

    test('requires authentication', function () {
        $nit = CustomerNit::factory()->create();

        $response = $this->deleteJson("/api/v1/nits/{$nit->id}");

        $response->assertUnauthorized();
    });
});

describe('setDefault (POST /api/v1/nits/{nit}/set-default)', function () {
    test('sets NIT as default', function () {
        $customer = Customer::factory()->create();

        $nit1 = CustomerNit::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
        ]);

        $nit2 = CustomerNit::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => false,
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson("/api/v1/nits/{$nit2->id}/set-default");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $nit2->id,
                    'is_default' => true,
                ],
                'message' => 'NIT marcado como predeterminado',
            ]);

        expect($nit1->fresh()->is_default)->toBeFalse();
        expect($nit2->fresh()->is_default)->toBeTrue();
    });

    test('prevents access to other customer NITs', function () {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $nit = CustomerNit::factory()->create(['customer_id' => $customer2->id]);

        $response = actingAs($customer1, 'sanctum')
            ->postJson("/api/v1/nits/{$nit->id}/set-default");

        $response->assertForbidden();
    });

    test('requires authentication', function () {
        $nit = CustomerNit::factory()->create();

        $response = $this->postJson("/api/v1/nits/{$nit->id}/set-default");

        $response->assertUnauthorized();
    });
});
