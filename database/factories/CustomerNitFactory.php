<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerNit>
 */
class CustomerNitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'nit' => fake()->numerify('########-#'),
            'nit_type' => fake()->randomElement(['personal', 'company', 'other']),
            'nit_name' => fake()->optional(0.3)->name(),
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the NIT is personal.
     */
    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'nit_type' => 'personal',
            'nit_name' => fake()->name(),
        ]);
    }

    /**
     * Indicate that the NIT is for a company.
     */
    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'nit_type' => 'company',
            'nit_name' => fake()->company(),
        ]);
    }

    /**
     * Indicate that the NIT is of other type.
     */
    public function other(): static
    {
        return $this->state(fn (array $attributes) => [
            'nit_type' => 'other',
            'nit_name' => fake()->optional(0.5)->name(),
        ]);
    }

    /**
     * Indicate that this is the default NIT.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
