<?php

use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Order;
use App\Models\PointsSetting;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('PointsService', function () {
    beforeEach(function () {
        // Create default points settings for all tests
        PointsSetting::create([
            'quetzales_per_point' => 10,
            'expiration_method' => 'total',
            'expiration_months' => 6,
            'rounding_threshold' => 0.70,
        ]);
        $this->service = new PointsService;
    });

    describe('calculatePointsToEarn', function () {
        describe('basic calculation (1 point per Q10)', function () {
            test('Q100 gives 10 points', function () {
                $points = $this->service->calculatePointsToEarn(100.00);
                expect($points)->toBe(10);
            });

            test('Q50 gives 5 points', function () {
                $points = $this->service->calculatePointsToEarn(50.00);
                expect($points)->toBe(5);
            });

            test('Q10 gives 1 point', function () {
                $points = $this->service->calculatePointsToEarn(10.00);
                expect($points)->toBe(1);
            });

            test('Q5 gives 0 points', function () {
                $points = $this->service->calculatePointsToEarn(5.00);
                expect($points)->toBe(0);
            });

            test('Q0 gives 0 points', function () {
                $points = $this->service->calculatePointsToEarn(0.00);
                expect($points)->toBe(0);
            });
        });

        describe('rounding threshold at 0.7', function () {
            test('Q96 (9.6) truncates to 9 points', function () {
                $points = $this->service->calculatePointsToEarn(96.00);
                expect($points)->toBe(9);
            });

            test('Q97 (9.7) rounds up to 10 points', function () {
                $points = $this->service->calculatePointsToEarn(97.00);
                expect($points)->toBe(10);
            });

            test('Q98 (9.8) rounds up to 10 points', function () {
                $points = $this->service->calculatePointsToEarn(98.00);
                expect($points)->toBe(10);
            });

            test('Q99 (9.9) rounds up to 10 points', function () {
                $points = $this->service->calculatePointsToEarn(99.00);
                expect($points)->toBe(10);
            });

            test('Q69 (6.9) rounds up to 7 points', function () {
                $points = $this->service->calculatePointsToEarn(69.00);
                expect($points)->toBe(7);
            });

            test('Q66 (6.6) truncates to 6 points', function () {
                $points = $this->service->calculatePointsToEarn(66.00);
                expect($points)->toBe(6);
            });

            test('Q67 (6.7) rounds up to 7 points', function () {
                $points = $this->service->calculatePointsToEarn(67.00);
                expect($points)->toBe(7);
            });

            test('Q7 (0.7) does NOT round up - must have at least 1 base point', function () {
                $points = $this->service->calculatePointsToEarn(7.00);
                expect($points)->toBe(0);
            });

            test('Q6 (0.6) truncates to 0 points', function () {
                $points = $this->service->calculatePointsToEarn(6.00);
                expect($points)->toBe(0);
            });
        });

        describe('customer type multiplier', function () {
            test('regular customer (1x) gets base points', function () {
                $customerType = CustomerType::factory()->regular()->create();
                $customer = Customer::factory()->create(['customer_type_id' => $customerType->id]);

                $points = $this->service->calculatePointsToEarn(100.00, $customer);

                expect($points)->toBe(10);
            });

            test('bronze customer (1.25x) gets multiplied points', function () {
                $customerType = CustomerType::factory()->bronze()->create();
                $customer = Customer::factory()->create(['customer_type_id' => $customerType->id]);

                // Q100 = 10 base points * 1.25 = 12.5 -> 12 (truncates, 0.5 < 0.7)
                $points = $this->service->calculatePointsToEarn(100.00, $customer);

                expect($points)->toBe(12);
            });

            test('silver customer (1.5x) gets multiplied points', function () {
                $customerType = CustomerType::factory()->silver()->create();
                $customer = Customer::factory()->create(['customer_type_id' => $customerType->id]);

                // Q100 = 10 base points * 1.5 = 15
                $points = $this->service->calculatePointsToEarn(100.00, $customer);

                expect($points)->toBe(15);
            });

            test('gold customer (1.75x) gets multiplied points', function () {
                $customerType = CustomerType::factory()->gold()->create();
                $customer = Customer::factory()->create(['customer_type_id' => $customerType->id]);

                // Q100 = 10 base points * 1.75 = 17.5 -> 17 (truncates, 0.5 < 0.7)
                $points = $this->service->calculatePointsToEarn(100.00, $customer);

                expect($points)->toBe(17);
            });

            test('platinum customer (2x) gets double points', function () {
                $customerType = CustomerType::factory()->platinum()->create();
                $customer = Customer::factory()->create(['customer_type_id' => $customerType->id]);

                // Q100 = 10 base points * 2.0 = 20
                $points = $this->service->calculatePointsToEarn(100.00, $customer);

                expect($points)->toBe(20);
            });

            test('multiplier result at 0.7 rounds up', function () {
                $customerType = CustomerType::factory()->create(['multiplier' => 1.27]);
                $customer = Customer::factory()->create(['customer_type_id' => $customerType->id]);

                // Q100 = 10 base points * 1.27 = 12.7 -> 13 (rounds up, 0.7 >= 0.7)
                $points = $this->service->calculatePointsToEarn(100.00, $customer);

                expect($points)->toBe(13);
            });

            test('null customer gets base points', function () {
                $points = $this->service->calculatePointsToEarn(100.00, null);

                expect($points)->toBe(10);
            });

            test('customer without type gets base points', function () {
                $customer = Customer::factory()->create(['customer_type_id' => null]);

                $points = $this->service->calculatePointsToEarn(100.00, $customer);

                expect($points)->toBe(10);
            });

            test('customer with zero multiplier gets base points', function () {
                $customerType = CustomerType::factory()->create(['multiplier' => 0]);
                $customer = Customer::factory()->create(['customer_type_id' => $customerType->id]);

                $points = $this->service->calculatePointsToEarn(100.00, $customer);

                expect($points)->toBe(10);
            });
        });

        describe('combined rounding and multiplier', function () {
            test('Q97 with gold (1.75x) applies rounding correctly', function () {
                $customerType = CustomerType::factory()->gold()->create();
                $customer = Customer::factory()->create(['customer_type_id' => $customerType->id]);

                // Q97 = 9.7 base -> rounds to 10 points * 1.75 = 17.5 -> 17 (truncates)
                $points = $this->service->calculatePointsToEarn(97.00, $customer);

                expect($points)->toBe(17);
            });

            test('Q87 with silver (1.5x)', function () {
                $customerType = CustomerType::factory()->silver()->create();
                $customer = Customer::factory()->create(['customer_type_id' => $customerType->id]);

                // Q87 = 8.7 base -> rounds to 9 points * 1.5 = 13.5 -> 13 (truncates, 0.5 < 0.7)
                $points = $this->service->calculatePointsToEarn(87.00, $customer);

                expect($points)->toBe(13);
            });
        });
    });

    describe('creditPoints', function () {
        test('credits correct points to customer', function () {
            $customer = Customer::factory()->withoutCustomerType()->create(['points' => 0]);
            $order = Order::factory()->create(['total' => 100.00]);

            $this->service->creditPoints($customer, $order);

            expect($customer->fresh()->points)->toBe(10);
        });

        test('credits points with customer type multiplier', function () {
            $customerType = CustomerType::factory()->platinum()->create();
            $customer = Customer::factory()->create([
                'points' => 0,
                'customer_type_id' => $customerType->id,
            ]);
            $order = Order::factory()->create(['total' => 100.00]);

            $this->service->creditPoints($customer, $order);

            // 10 base * 2.0 = 20
            expect($customer->fresh()->points)->toBe(20);
        });

        test('accumulates points', function () {
            $customer = Customer::factory()->withoutCustomerType()->create(['points' => 50]);
            $order = Order::factory()->create(['total' => 100.00]);

            $this->service->creditPoints($customer, $order);

            expect($customer->fresh()->points)->toBe(60);
        });

        test('does not credit zero points', function () {
            $customer = Customer::factory()->create(['points' => 50]);
            $order = Order::factory()->create(['total' => 5.00]);

            $this->service->creditPoints($customer, $order);

            expect($customer->fresh()->points)->toBe(50);
        });

        test('updates points_updated_at timestamp', function () {
            $customer = Customer::factory()->create([
                'points' => 0,
                'points_updated_at' => null,
            ]);
            $order = Order::factory()->create(['total' => 100.00]);

            $this->service->creditPoints($customer, $order);

            expect($customer->fresh()->points_updated_at)->not->toBeNull();
        });
    });

});
