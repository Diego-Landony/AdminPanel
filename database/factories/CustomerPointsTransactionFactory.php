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
        $types = ['earned', 'redeemed', 'expired', 'adjusted'];
        $type = fake()->randomElement($types);

        return [
            'customer_id' => \App\Models\Customer::factory(),
            'points' => $type === 'redeemed' || $type === 'expired' ? -fake()->numberBetween(1, 100) : fake()->numberBetween(1, 100),
            'type' => $type,
            'reference_type' => null,
            'reference_id' => null,
            'description' => fake()->sentence(),
        ];
    }
}
