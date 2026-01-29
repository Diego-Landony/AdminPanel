<?php

use App\Models\Customer;
use App\Services\Wallet\AppleWalletService;
use App\Services\Wallet\GoogleWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

describe('Apple Wallet - POST /api/v1/wallet/apple/pass', function () {
    test('returns signed download URL for authenticated customer', function () {
        $customer = Customer::factory()->create([
            'subway_card' => '81234567890',
        ]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/wallet/apple/pass');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['url'],
            ]);

        $url = $response->json('data.url');
        expect($url)->toContain('/api/v1/wallet/apple/download/'.$customer->id);
        expect($url)->toContain('signature=');
    });

    test('returns 422 if customer has no subway card', function () {
        // subway_card is auto-generated on creation, so we must null it after
        $customer = Customer::factory()->create();
        $customer->update(['subway_card' => null]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/wallet/apple/pass');

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'No tienes una tarjeta Subway asignada.',
            ]);
    });

    test('requires authentication', function () {
        $response = $this->postJson('/api/v1/wallet/apple/pass');

        $response->assertUnauthorized();
    });
});

describe('Apple Wallet Download - GET /api/v1/wallet/apple/download/{customer}', function () {
    test('returns pkpass file with correct content type for valid signed URL', function () {
        $customer = Customer::factory()->create([
            'subway_card' => '81234567890',
        ]);

        $this->mock(AppleWalletService::class, function ($mock) {
            $mock->shouldReceive('generatePass')
                ->once()
                ->andReturn('fake-pkpass-binary-data');
        });

        $url = URL::temporarySignedRoute(
            'api.v1.wallet.apple.download',
            now()->addMinutes(15),
            ['customer' => $customer->id]
        );

        $response = $this->get($url);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.apple.pkpass');
        $response->assertHeader('Content-Disposition', 'attachment; filename="subway-loyalty.pkpass"');
        expect($response->getContent())->toBe('fake-pkpass-binary-data');
    });

    test('rejects unsigned URL', function () {
        $customer = Customer::factory()->create();

        $response = $this->get("/api/v1/wallet/apple/download/{$customer->id}");

        $response->assertForbidden();
    });

    test('rejects expired signed URL', function () {
        $customer = Customer::factory()->create();

        $url = URL::temporarySignedRoute(
            'api.v1.wallet.apple.download',
            now()->subMinute(),
            ['customer' => $customer->id]
        );

        $response = $this->get($url);

        $response->assertForbidden();
    });

    test('returns 404 for non-existent customer', function () {
        $url = URL::temporarySignedRoute(
            'api.v1.wallet.apple.download',
            now()->addMinutes(15),
            ['customer' => 999999]
        );

        $response = $this->get($url);

        $response->assertNotFound();
    });

    test('returns 500 when service throws exception', function () {
        $customer = Customer::factory()->create([
            'subway_card' => '81234567890',
        ]);

        $this->mock(AppleWalletService::class, function ($mock) {
            $mock->shouldReceive('generatePass')
                ->once()
                ->andThrow(new RuntimeException('Certificate not found'));
        });

        $url = URL::temporarySignedRoute(
            'api.v1.wallet.apple.download',
            now()->addMinutes(15),
            ['customer' => $customer->id]
        );

        $response = $this->get($url);

        $response->assertInternalServerError();
    });
});

describe('Google Wallet - POST /api/v1/wallet/google/pass', function () {
    test('returns save URL for authenticated customer', function () {
        $customer = Customer::factory()->create([
            'subway_card' => '81234567890',
        ]);

        $this->mock(GoogleWalletService::class, function ($mock) {
            $mock->shouldReceive('generateSaveUrl')
                ->once()
                ->andReturn('https://pay.google.com/gp/v/save/test-jwt-token');
        });

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/wallet/google/pass');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['save_url'],
            ]);

        expect($response->json('data.save_url'))
            ->toBe('https://pay.google.com/gp/v/save/test-jwt-token');
    });

    test('returns 422 if customer has no subway card', function () {
        // subway_card is auto-generated on creation, so we must null it after
        $customer = Customer::factory()->create();
        $customer->update(['subway_card' => null]);

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/wallet/google/pass');

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'No tienes una tarjeta Subway asignada.',
            ]);
    });

    test('returns 500 when service throws exception', function () {
        $customer = Customer::factory()->create([
            'subway_card' => '81234567890',
        ]);

        $this->mock(GoogleWalletService::class, function ($mock) {
            $mock->shouldReceive('generateSaveUrl')
                ->once()
                ->andThrow(new RuntimeException('Google API error'));
        });

        $response = actingAs($customer, 'sanctum')
            ->postJson('/api/v1/wallet/google/pass');

        $response->assertInternalServerError()
            ->assertJson([
                'message' => 'Error generando el pase de Google Wallet.',
            ]);
    });

    test('requires authentication', function () {
        $response = $this->postJson('/api/v1/wallet/google/pass');

        $response->assertUnauthorized();
    });
});
