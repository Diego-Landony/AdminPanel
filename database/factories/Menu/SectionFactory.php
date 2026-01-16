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
            'bundle_discount_enabled' => false,
            'bundle_size' => 2,
            'bundle_discount_amount' => null,
            'is_active' => true,
        ];
    }

    /**
     * Configure the section with bundle pricing enabled.
     */
    public function withBundlePricing(float $discountAmount = 5.00, int $bundleSize = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'bundle_discount_enabled' => true,
            'bundle_size' => $bundleSize,
            'bundle_discount_amount' => $discountAmount,
        ]);
    }
}
