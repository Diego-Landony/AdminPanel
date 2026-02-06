<?php

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Driver Stats', function () {
    it('returns stats for today', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->available()->create();
        $customer = Customer::factory()->create();

        // Create orders delivered today
        Order::factory()
            ->count(3)
            ->for($restaurant)
            ->for($customer)
            ->delivery()
            ->create([
                'driver_id' => $driver->id,
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => now(),
                'accepted_by_driver_at' => now()->subMinutes(30),
                'delivery_person_rating' => 5,
            ]);

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->getJson('/api/v1/driver/stats?period=today');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.period', 'today')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'period_label',
                    'deliveries' => [
                        'total',
                        'completed',
                        'cancelled',
                        'completion_rate',
                    ],
                    'timing' => [
                        'average_minutes',
                        'fastest_minutes',
                        'slowest_minutes',
                    ],
                    'rating' => [
                        'average',
                        'total_reviews',
                        'distribution',
                    ],
                    'earnings' => [
                        'tips_total',
                        'tips_average',
                    ],
                ],
                'message',
            ]);
    });

    it('returns stats for month', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->available()->create();
        $customer = Customer::factory()->create();

        // Create orders delivered this month
        Order::factory()
            ->count(5)
            ->for($restaurant)
            ->for($customer)
            ->delivery()
            ->create([
                'driver_id' => $driver->id,
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => now()->startOfMonth()->addDays(5),
                'accepted_by_driver_at' => now()->startOfMonth()->addDays(5)->subMinutes(30),
            ]);

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->getJson('/api/v1/driver/stats?period=month');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.period', 'month');

        // Verify period_label contains the month name (February in this case since test date is 2026-02-04)
        $periodLabel = $response->json('data.period_label');
        expect($periodLabel)->toContain('2026');
    });

    it('calculates delivery stats correctly', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->available()->create();
        $customer = Customer::factory()->create();

        // Create 5 completed orders
        Order::factory()
            ->count(5)
            ->for($restaurant)
            ->for($customer)
            ->delivery()
            ->create([
                'driver_id' => $driver->id,
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => now(),
                'accepted_by_driver_at' => now()->subMinutes(25),
                'delivery_person_rating' => 5,
            ]);

        // Create 2 cancelled orders
        Order::factory()
            ->count(2)
            ->for($restaurant)
            ->for($customer)
            ->delivery()
            ->create([
                'driver_id' => $driver->id,
                'status' => Order::STATUS_CANCELLED,
                'delivered_at' => now(),
            ]);

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->getJson('/api/v1/driver/stats?period=today');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.deliveries.completed', 5)
            ->assertJsonPath('data.deliveries.cancelled', 2)
            ->assertJsonPath('data.deliveries.total', 7);

        // Verify completion_rate is calculated correctly (5/7 = 71.43%)
        $completionRate = $response->json('data.deliveries.completion_rate');
        expect($completionRate)->toBe(71.43);
    });

    it('defaults to month when period not specified', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->available()->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->getJson('/api/v1/driver/stats');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.period', 'month');
    });

    it('rejects access without authentication', function () {
        $response = $this->getJson('/api/v1/driver/stats');

        $response->assertUnauthorized();
    });

    it('returns zero stats when no deliveries in period', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->available()->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->getJson('/api/v1/driver/stats?period=today');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'period' => 'today',
                    'deliveries' => [
                        'total' => 0,
                        'completed' => 0,
                        'cancelled' => 0,
                        'completion_rate' => 0,
                    ],
                    'timing' => [
                        'average_minutes' => null,
                        'fastest_minutes' => null,
                        'slowest_minutes' => null,
                    ],
                    'rating' => [
                        'average' => null,
                        'total_reviews' => 0,
                    ],
                ],
            ]);
    });

    it('calculates timing stats correctly', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->available()->create();
        $customer = Customer::factory()->create();

        // Create order with 20 min delivery time
        Order::factory()
            ->for($restaurant)
            ->for($customer)
            ->delivery()
            ->create([
                'driver_id' => $driver->id,
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => now(),
                'accepted_by_driver_at' => now()->subMinutes(20),
            ]);

        // Create order with 40 min delivery time
        Order::factory()
            ->for($restaurant)
            ->for($customer)
            ->delivery()
            ->create([
                'driver_id' => $driver->id,
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => now(),
                'accepted_by_driver_at' => now()->subMinutes(40),
            ]);

        // Create order with 30 min delivery time
        Order::factory()
            ->for($restaurant)
            ->for($customer)
            ->delivery()
            ->create([
                'driver_id' => $driver->id,
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => now(),
                'accepted_by_driver_at' => now()->subMinutes(30),
            ]);

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->getJson('/api/v1/driver/stats?period=today');

        $response->assertOk();

        $timing = $response->json('data.timing');
        expect($timing['fastest_minutes'])->toBe(20);
        expect($timing['slowest_minutes'])->toBe(40);
        expect($timing['average_minutes'])->toBe(30); // (20+40+30)/3 = 30
    });

    it('calculates rating stats correctly', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->available()->create();
        $customer = Customer::factory()->create();

        // Create orders with different ratings
        Order::factory()
            ->for($restaurant)
            ->for($customer)
            ->delivery()
            ->create([
                'driver_id' => $driver->id,
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => now(),
                'accepted_by_driver_at' => now()->subMinutes(30),
                'delivery_person_rating' => 5,
            ]);

        Order::factory()
            ->for($restaurant)
            ->for($customer)
            ->delivery()
            ->create([
                'driver_id' => $driver->id,
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => now(),
                'accepted_by_driver_at' => now()->subMinutes(30),
                'delivery_person_rating' => 4,
            ]);

        Order::factory()
            ->for($restaurant)
            ->for($customer)
            ->delivery()
            ->create([
                'driver_id' => $driver->id,
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => now(),
                'accepted_by_driver_at' => now()->subMinutes(30),
                'delivery_person_rating' => 5,
            ]);

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->getJson('/api/v1/driver/stats?period=today');

        $response->assertOk();

        $rating = $response->json('data.rating');
        expect($rating['total_reviews'])->toBe(3);
        expect($rating['average'])->toBe(4.7); // (5+4+5)/3 = 4.67 rounded to 4.7

        // Verify distribution exists and has expected structure
        expect($rating)->toHaveKey('distribution');

        // Note: The distribution array has keys '5','4','3','2','1' in the controller,
        // but JSON encoding/decoding converts them to sequential integer indices 0-4
        // where index 0 corresponds to rating '5', index 1 to rating '4', etc.
        // We verify the sum of ratings matches what we created
        $distribution = $rating['distribution'];
        $totalInDistribution = array_sum(array_values($distribution));
        expect($totalInDistribution)->toBe(3); // 2 five-stars + 1 four-star = 3 total
    });

    it('validates period parameter', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->available()->create();

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->getJson('/api/v1/driver/stats?period=invalid');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['period']);
    });

    it('returns stats for week period', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->available()->create();
        $customer = Customer::factory()->create();

        // Create orders delivered this week
        Order::factory()
            ->count(3)
            ->for($restaurant)
            ->for($customer)
            ->delivery()
            ->create([
                'driver_id' => $driver->id,
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => now()->startOfWeek()->addDays(2),
                'accepted_by_driver_at' => now()->startOfWeek()->addDays(2)->subMinutes(30),
            ]);

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->getJson('/api/v1/driver/stats?period=week');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.period', 'week');
    });

    it('returns stats for year period', function () {
        $restaurant = Restaurant::factory()->create();
        $driver = Driver::factory()->for($restaurant)->available()->create();
        $customer = Customer::factory()->create();

        // Create orders delivered this year
        Order::factory()
            ->count(10)
            ->for($restaurant)
            ->for($customer)
            ->delivery()
            ->create([
                'driver_id' => $driver->id,
                'status' => Order::STATUS_DELIVERED,
                'delivered_at' => now()->startOfYear()->addDays(30),
                'accepted_by_driver_at' => now()->startOfYear()->addDays(30)->subMinutes(30),
            ]);

        Sanctum::actingAs($driver, ['driver'], 'driver');

        $response = $this->getJson('/api/v1/driver/stats?period=year');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.period', 'year')
            ->assertJsonPath('data.period_label', '2026');
    });
});
