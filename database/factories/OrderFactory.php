<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerNit;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $serviceType = fake()->randomElement(['delivery', 'pickup']);
        $zone = fake()->randomElement(['capital', 'interior']);
        $status = fake()->randomElement([
            Order::STATUS_PENDING,
            Order::STATUS_PREPARING,
            Order::STATUS_READY,
            Order::STATUS_COMPLETED,
        ]);

        $subtotal = fake()->randomFloat(2, 20, 200);
        $discountTotal = fake()->randomFloat(2, 0, 20);
        $total = $subtotal - $discountTotal;

        return [
            'order_number' => fake()->unique()->numerify('ORD-######'),
            'customer_id' => Customer::factory(),
            'restaurant_id' => Restaurant::factory(),
            'service_type' => $serviceType,
            'zone' => $zone,
            'delivery_address_id' => $serviceType === 'delivery' ? CustomerAddress::factory() : null,
            'delivery_address_snapshot' => $serviceType === 'delivery' ? [
                'label' => fake()->randomElement(['Casa', 'Trabajo', 'Otro']),
                'address_line' => fake()->address(),
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
                'delivery_notes' => fake()->optional()->sentence(),
            ] : null,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'total' => $total,
            'status' => $status,
            'payment_method' => fake()->randomElement(['card', 'cash']),
            'payment_status' => fake()->randomElement(['pending', 'paid', 'failed']),
            'paid_at' => fake()->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            'estimated_ready_at' => fake()->optional(0.8)->dateTimeBetween('now', '+2 hours'),
            'ready_at' => in_array($status, [Order::STATUS_READY, Order::STATUS_COMPLETED]) ? fake()->dateTimeBetween('-1 hour', 'now') : null,
            'delivered_at' => $status === Order::STATUS_COMPLETED && $serviceType === 'delivery' ? fake()->dateTimeBetween('-30 minutes', 'now') : null,
            'points_earned' => fake()->numberBetween(0, 50),
            'nit_id' => fake()->optional(0.3)->boolean() ? CustomerNit::factory() : null,
            'nit_snapshot' => fake()->optional(0.3)->boolean() ? [
                'nit' => fake()->numerify('########-#'),
                'nit_name' => fake()->name(),
            ] : null,
            'notes' => fake()->optional()->sentence(),
            'cancellation_reason' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PENDING,
            'ready_at' => null,
            'delivered_at' => null,
        ]);
    }

    public function preparing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PREPARING,
            'ready_at' => null,
            'delivered_at' => null,
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_READY,
            'ready_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'delivered_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_COMPLETED,
            'ready_at' => fake()->dateTimeBetween('-2 hours', '-1 hour'),
            'delivered_at' => $attributes['service_type'] === 'delivery' ? fake()->dateTimeBetween('-30 minutes', 'now') : null,
            'payment_status' => 'paid',
            'paid_at' => fake()->dateTimeBetween('-3 hours', '-2 hours'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_CANCELLED,
            'cancellation_reason' => fake()->sentence(),
            'ready_at' => null,
            'delivered_at' => null,
        ]);
    }

    public function delivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'delivery',
            'delivery_address_id' => CustomerAddress::factory(),
            'delivery_address_snapshot' => [
                'label' => fake()->randomElement(['Casa', 'Trabajo', 'Otro']),
                'address_line' => fake()->address(),
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
                'delivery_notes' => fake()->optional()->sentence(),
            ],
        ]);
    }

    public function pickup(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'pickup',
            'delivery_address_id' => null,
            'delivery_address_snapshot' => null,
        ]);
    }

    public function withNit(): static
    {
        return $this->state(fn (array $attributes) => [
            'nit_id' => CustomerNit::factory(),
            'nit_snapshot' => [
                'nit' => fake()->numerify('########-#'),
                'nit_name' => fake()->name(),
            ],
        ]);
    }
}
