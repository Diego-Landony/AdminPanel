<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $labels = ['Casa', 'Trabajo', 'Oficina', 'Casa de mamá', 'Departamento'];
        $zones = [
            ['lat' => 14.6349, 'lng' => -90.5069, 'area' => 'Centro Guatemala City'],
            ['lat' => 14.6095, 'lng' => -90.5289, 'area' => 'Zona 10'],
            ['lat' => 14.5927, 'lng' => -90.5131, 'area' => 'Zona 14'],
            ['lat' => 14.6123, 'lng' => -90.4892, 'area' => 'Zona 15'],
            ['lat' => 14.6234, 'lng' => -90.4721, 'area' => 'Zona 16'],
            ['lat' => 14.5260, 'lng' => -90.5866, 'area' => 'Villa Nueva'],
            ['lat' => 14.6333, 'lng' => -90.6144, 'area' => 'Mixco'],
        ];

        $selectedZone = $zones[array_rand($zones)];
        $latitude = $selectedZone['lat'] + (rand(-100, 100) / 10000);
        $longitude = $selectedZone['lng'] + (rand(-100, 100) / 10000);

        $deliveryNotes = [
            'Portón azul',
            'Casa esquinera',
            'Edificio color blanco',
            'Código de acceso: 1234',
            'Tocar timbre',
            null,
            null,
        ];

        return [
            'customer_id' => \App\Models\Customer::factory(),
            'label' => fake()->randomElement($labels),
            'address_line' => fake()->streetAddress().', '.$selectedZone['area'].', Guatemala',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'delivery_notes' => fake()->randomElement($deliveryNotes),
            'is_default' => false,
        ];
    }

    /**
     * Marcar como dirección predeterminada
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
