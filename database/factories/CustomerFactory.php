<?php

namespace Database\Factories;

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
            'password' => \Hash::make('password'),
            'subway_card' => fake()->unique()->numerify('##########'),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['masculino', 'femenino', 'otro']),
            'client_type' => fake()->randomElement(['regular', 'premium', 'vip']),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'location' => fake()->city(),
            'nit' => fake()->optional()->numerify('########-#'),
            'fcm_token' => fake()->optional()->sha256(),
            'last_login_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'last_activity_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
            'last_purchase_at' => fake()->optional()->dateTimeBetween('-60 days', 'now'),
            'timezone' => 'America/Guatemala',
            'remember_token' => \Str::random(10),
        ];
    }
}
