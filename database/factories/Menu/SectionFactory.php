<?php

namespace Database\Factories\Menu;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu\Section>
 */
class SectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'is_required' => fake()->boolean(),
            'allow_multiple' => fake()->boolean(),
            'min_selections' => 1,
            'max_selections' => fake()->numberBetween(1, 5),
            'is_active' => true,
        ];
    }
}
