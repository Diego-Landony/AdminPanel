<?php

namespace Database\Factories;

use App\Models\CustomerType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'subway_card' => fake()->unique()->numerify('##########'),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['masculino', 'femenino', 'otro']),
            'customer_type_id' => CustomerType::factory(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'location' => fake()->city(),
            'nit' => fake()->optional()->numerify('########-#'),
            'fcm_token' => fake()->optional()->sha256(),
            'last_login_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'last_activity_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
            'last_purchase_at' => fake()->optional()->dateTimeBetween('-60 days', 'now'),
            'puntos' => fake()->numberBetween(0, 2000),
            'puntos_updated_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'timezone' => 'America/Guatemala',
            'remember_token' => fake()->sha256(),
        ];
    }

    /**
     * Create a customer with regular type.
     */
    public function regular(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type_id' => CustomerType::factory()->regular(),
            'puntos' => fake()->numberBetween(0, 49),
        ]);
    }

    /**
     * Create a customer with bronze type.
     */
    public function bronze(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type_id' => CustomerType::factory()->bronze(),
            'puntos' => fake()->numberBetween(50, 124),
        ]);
    }

    /**
     * Create a customer with silver type.
     */
    public function silver(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type_id' => CustomerType::factory()->silver(),
            'puntos' => fake()->numberBetween(125, 324),
        ]);
    }

    /**
     * Create a customer with gold type.
     */
    public function gold(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type_id' => CustomerType::factory()->gold(),
            'puntos' => fake()->numberBetween(325, 999),
        ]);
    }

    /**
     * Create a customer with platinum type.
     */
    public function platinum(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type_id' => CustomerType::factory()->platinum(),
            'puntos' => fake()->numberBetween(1000, 5000),
        ]);
    }

    /**
     * Create a customer without customer type (for legacy testing).
     */
    public function withoutCustomerType(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type_id' => null,
        ]);
    }
}
