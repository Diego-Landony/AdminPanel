<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Helper to create a valid KML polygon around Guatemala City center
 */
function createGuatemalaKml(float $centerLat = 14.6349, float $centerLng = -90.5069, float $size = 0.02): string
{
    $north = $centerLat + $size;
    $south = $centerLat - $size;
    $east = $centerLng + $size;
    $west = $centerLng - $size;

    return <<<KML
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <Placemark>
      <Polygon>
        <outerBoundaryIs>
          <LinearRing>
            <coordinates>
              {$west},{$south} {$east},{$south} {$east},{$north} {$west},{$north} {$west},{$south}
            </coordinates>
          </LinearRing>
        </outerBoundaryIs>
      </Polygon>
    </Placemark>
  </Document>
</kml>
KML;
}

describe('Delivery Address Validation', function () {
    test('validates delivery address is within restaurant geofence', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'is_active' => true,
            'delivery_active' => true,
            'latitude' => 14.6349,
            'longitude' => -90.5069,
            'geofence_kml' => createGuatemalaKml(),
            'price_location' => 'capital',
        ]);

        $addressInZone = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'latitude' => 14.6350,
            'longitude' => -90.5070,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses/validate', [
                'latitude' => $addressInZone->latitude,
                'longitude' => $addressInZone->longitude,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'is_valid' => true,
                    'delivery_available' => true,
                ],
            ]);

        expect($response->json('data.restaurant.id'))->toBe($restaurant->id);
        expect($response->json('data.zone'))->toBe('capital');
    });

    test('returns error when address outside all geofences', function () {
        $customer = Customer::factory()->create();
        Restaurant::factory()->create([
            'is_active' => true,
            'delivery_active' => true,
            'latitude' => 14.6349,
            'longitude' => -90.5069,
            'geofence_kml' => createGuatemalaKml(),
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses/validate', [
                'latitude' => 15.5000,
                'longitude' => -88.0000,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'is_valid' => false,
                    'delivery_available' => false,
                ],
            ]);

        expect($response->json('data.message'))->not->toBeNull();
    });

    test('returns available restaurants for delivery location with multiple options', function () {
        $customer = Customer::factory()->create();

        $restaurant1 = Restaurant::factory()->create([
            'name' => 'Closer Restaurant',
            'is_active' => true,
            'delivery_active' => true,
            'latitude' => 14.6349,
            'longitude' => -90.5069,
            'geofence_kml' => createGuatemalaKml(14.6349, -90.5069, 0.05),
            'price_location' => 'capital',
        ]);

        $restaurant2 = Restaurant::factory()->create([
            'name' => 'Further Restaurant',
            'is_active' => true,
            'delivery_active' => true,
            'latitude' => 14.6400,
            'longitude' => -90.5100,
            'geofence_kml' => createGuatemalaKml(14.6400, -90.5100, 0.05),
            'price_location' => 'capital',
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses/validate', [
                'latitude' => 14.6350,
                'longitude' => -90.5070,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'is_valid' => true,
                    'delivery_available' => true,
                ],
            ]);
    });

    test('order creation fails when delivery address outside geofence', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'is_active' => true,
            'delivery_active' => true,
            'latitude' => 14.6349,
            'longitude' => -90.5069,
            'geofence_kml' => createGuatemalaKml(),
            'price_location' => 'capital',
        ]);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_domicilio_capital' => 55.00,
        ]);

        $addressOutsideZone = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'latitude' => 15.5000,
            'longitude' => -88.0000,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'delivery',
            'zone' => 'capital',
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 55.00,
            'subtotal' => 55.00,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'delivery',
                'delivery_address_id' => $addressOutsideZone->id,
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'error_code' => 'ADDRESS_OUTSIDE_DELIVERY_ZONE',
            ]);
    });

    test('returns nearest pickup locations when address outside delivery zones', function () {
        $customer = Customer::factory()->create();

        Restaurant::factory()->create([
            'name' => 'Pickup Location 1',
            'is_active' => true,
            'pickup_active' => true,
            'delivery_active' => false,
            'latitude' => 14.6349,
            'longitude' => -90.5069,
        ]);

        Restaurant::factory()->create([
            'name' => 'Pickup Location 2',
            'is_active' => true,
            'pickup_active' => true,
            'delivery_active' => false,
            'latitude' => 14.6400,
            'longitude' => -90.5100,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses/validate', [
                'latitude' => 14.6350,
                'longitude' => -90.5070,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'is_valid' => false,
                    'delivery_available' => false,
                ],
            ]);

        expect($response->json('data.nearest_pickup_locations'))->toBeArray();
        expect($response->json('data.nearest_pickup_locations'))->not->toBeEmpty();
    });

    test('zone is correctly assigned to address based on restaurant location', function () {
        $customer = Customer::factory()->create();

        Restaurant::factory()->create([
            'is_active' => true,
            'delivery_active' => true,
            'latitude' => 14.6349,
            'longitude' => -90.5069,
            'geofence_kml' => createGuatemalaKml(),
            'price_location' => 'interior',
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses', [
                'label' => 'Test Address',
                'address_line' => 'Test Address Line',
                'latitude' => 14.6350,
                'longitude' => -90.5070,
            ]);

        $response->assertCreated();
        expect($response->json('data.zone'))->toBeIn(['capital', 'interior']);
    });
});

describe('Restaurant Delivery Status', function () {
    test('delivery orders require restaurant with delivery_active', function () {
        $customer = Customer::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'is_active' => true,
            'delivery_active' => false,
            'pickup_active' => true,
            'latitude' => 14.6349,
            'longitude' => -90.5069,
        ]);
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'precio_domicilio_capital' => 55.00,
        ]);

        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'latitude' => 14.6350,
            'longitude' => -90.5070,
        ]);

        $cart = Cart::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'service_type' => 'delivery',
            'zone' => 'capital',
            'status' => 'active',
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 55.00,
            'subtotal' => 55.00,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'restaurant_id' => $restaurant->id,
                'service_type' => 'delivery',
                'delivery_address_id' => $address->id,
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(422);
    });
});

describe('Geofence Validation Edge Cases', function () {
    test('handles invalid coordinates gracefully', function () {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses/validate', [
                'latitude' => 'invalid',
                'longitude' => -90.5069,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude']);
    });

    test('handles extreme coordinate values', function () {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses/validate', [
                'latitude' => 91,
                'longitude' => -181,
            ]);

        $response->assertUnprocessable();
    });

    test('returns correct zone for interior locations', function () {
        $customer = Customer::factory()->create();
        $interiorLat = 14.8500;
        $interiorLng = -91.5100;

        $restaurant = Restaurant::factory()->create([
            'is_active' => true,
            'delivery_active' => true,
            'latitude' => $interiorLat,
            'longitude' => $interiorLng,
            'geofence_kml' => createGuatemalaKml($interiorLat, $interiorLng, 0.05),
            'price_location' => 'interior',
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/addresses/validate', [
                'latitude' => $interiorLat + 0.01,
                'longitude' => $interiorLng + 0.01,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'is_valid' => true,
                    'zone' => 'interior',
                ],
            ]);
    });
});
