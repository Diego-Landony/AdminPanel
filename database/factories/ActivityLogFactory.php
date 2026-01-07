<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_type' => fake()->randomElement(['created', 'updated', 'deleted', 'login', 'logout']),
            'target_model' => fake()->randomElement(['App\\Models\\User', 'App\\Models\\Customer', 'App\\Models\\Order']),
            'target_id' => fake()->numberBetween(1, 100),
            'description' => fake()->sentence(),
            'old_values' => null,
            'new_values' => null,
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * State for created events.
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'created',
            'old_values' => null,
            'new_values' => ['name' => fake()->name()],
        ]);
    }

    /**
     * State for updated events.
     */
    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'updated',
            'old_values' => ['name' => fake()->name()],
            'new_values' => ['name' => fake()->name()],
        ]);
    }

    /**
     * State for deleted events.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'deleted',
            'old_values' => ['name' => fake()->name()],
            'new_values' => null,
        ]);
    }
}
