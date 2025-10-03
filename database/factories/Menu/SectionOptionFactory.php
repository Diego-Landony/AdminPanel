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
            'base_price_modifier' => 0,
            'delivery_price_modifier' => 0,
            'interior_base_price_modifier' => 0,
            'interior_delivery_price_modifier' => 0,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
