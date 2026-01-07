<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerPointsTransaction>
 */
class CustomerPointsTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['earned', 'expired', 'adjusted'];
        $type = fake()->randomElement($types);

        return [
            'customer_id' => \App\Models\Customer::factory(),
            'points' => $type === 'expired' ? -fake()->numberBetween(1, 100) : fake()->numberBetween(1, 100),
            'type' => $type,
            'reference_type' => null,
            'reference_id' => null,
            'description' => fake()->sentence(),
            'expires_at' => $type === 'earned' ? now()->addMonths(6) : null,
            'is_expired' => false,
        ];
    }

    /**
     * Create an earned transaction.
     */
    public function earned(?int $points = null): static
    {
        return $this->state(fn (array $attributes) => [
            'points' => $points ?? fake()->numberBetween(1, 100),
            'type' => 'earned',
            'expires_at' => now()->addMonths(6),
            'is_expired' => false,
        ]);
    }

    /**
     * Create an earned transaction that has already expired (date-wise).
     */
    public function earnedExpired(?int $points = null): static
    {
        return $this->state(fn (array $attributes) => [
            'points' => $points ?? fake()->numberBetween(1, 100),
            'type' => 'earned',
            'expires_at' => now()->subDays(1),
            'is_expired' => false,
        ]);
    }

    /**
     * Create an expired transaction (already processed).
     */
    public function expired(?int $points = null): static
    {
        return $this->state(fn (array $attributes) => [
            'points' => -abs($points ?? fake()->numberBetween(1, 100)),
            'type' => 'expired',
            'expires_at' => null,
            'is_expired' => true,
        ]);
    }

    /**
     * Set a specific expiration date.
     */
    public function expiresAt(\DateTimeInterface $date): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $date,
        ]);
    }

    /**
     * Set the transaction as already expired (marked).
     */
    public function markedAsExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_expired' => true,
        ]);
    }
}
