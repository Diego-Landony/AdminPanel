<?php

namespace Database\Factories\Menu;

use App\Models\Menu\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu\SectionOption>
 */
class SectionOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'name' => fake()->word(),
            'is_extra' => fake()->boolean(),
            'price_modifier' => 0.00,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
