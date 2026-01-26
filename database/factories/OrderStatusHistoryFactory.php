<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = [
            Order::STATUS_PENDING,
            Order::STATUS_PREPARING,
            Order::STATUS_READY,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_DELIVERED,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ];

        $previousStatus = fake()->randomElement($statuses);
        $newStatus = fake()->randomElement(array_diff($statuses, [$previousStatus]));

        return [
            'order_id' => Order::factory(),
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'changed_by_type' => fake()->randomElement(['admin', 'system', 'customer']),
            'changed_by_id' => fake()->optional()->numberBetween(1, 100),
            'notes' => fake()->optional()->sentence(),
            'metadata' => [
                'ip_address' => fake()->optional()->ipv4(),
                'user_agent' => fake()->optional()->userAgent(),
            ],
            'created_at' => now(),
        ];
    }

    public function fromPendingToPreparing(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_status' => Order::STATUS_PENDING,
            'new_status' => Order::STATUS_PREPARING,
            'changed_by_type' => 'restaurant',
            'notes' => 'Orden aceptada por el restaurante',
        ]);
    }

    public function fromPreparingToReady(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_status' => Order::STATUS_PREPARING,
            'new_status' => Order::STATUS_READY,
            'changed_by_type' => 'admin',
            'notes' => 'Orden lista para entrega/pickup',
        ]);
    }

    public function fromReadyToCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_status' => Order::STATUS_READY,
            'new_status' => Order::STATUS_COMPLETED,
            'changed_by_type' => 'system',
            'notes' => 'Orden completada',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'new_status' => Order::STATUS_CANCELLED,
            'changed_by_type' => fake()->randomElement(['admin', 'customer']),
            'notes' => fake()->randomElement([
                'Cliente canceló la orden',
                'Producto no disponible',
                'Orden duplicada',
                'Error en el pago',
            ]),
        ]);
    }

    public function byAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'changed_by_type' => 'admin',
            'changed_by_id' => fake()->numberBetween(1, 50),
        ]);
    }

    public function bySystem(): static
    {
        return $this->state(fn (array $attributes) => [
            'changed_by_type' => 'system',
            'changed_by_id' => null,
            'notes' => 'Cambio automático del sistema',
        ]);
    }

    public function byCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'changed_by_type' => 'customer',
            'changed_by_id' => fake()->numberBetween(1, 100),
        ]);
    }
}
