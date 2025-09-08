<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $deliveryActive = $this->faker->boolean(80);
        $pickupActive = $this->faker->boolean(85);
        $isActive = $this->faker->boolean(90);
        
        $schedule = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            $isOpen = $this->faker->boolean(85);
            $schedule[$day] = [
                'is_open' => $isOpen,
                'open' => $isOpen ? $this->faker->time('H:i', '11:00') : null,
                'close' => $isOpen ? $this->faker->time('H:i', '22:00') : null,
            ];
        }

        $guatemalaNames = [
            'Restaurante El Quetzal',
            'Casa de Comida Maya',
            'El Rincón Chapín',
            'Restaurante Tikal',
            'La Cocina de Doña María',
            'El Sabor Guatemalteco',
            'Restaurante Atitlán',
            'Casa del Pollo Campero',
            'El Típico Guatemalteco',
            'Restaurante Antigua',
            'La Mesa Chapina',
            'El Fogón de la Abuela',
            'Restaurante Volcán de Agua',
            'La Tradición Guatemalteca',
            'El Patio Colonial'
        ];

        $guatemalaAddresses = [
            'Zona 10, Ciudad de Guatemala',
            'Avenida Las Américas, Zona 14',
            'Calzada Roosevelt, Zona 11',
            '6a Avenida, Zona 1, Centro Histórico',
            'Boulevard Los Próceres, Zona 10',
            'Carretera a El Salvador, Villa Nueva',
            'Centro Comercial Oakland Mall',
            'Avenida Petapa, Zona 12',
            'Boulevard Rafael Landívar, Zona 16',
            'Calzada San Juan, Zona 7'
        ];

        return [
            'name' => $this->faker->randomElement($guatemalaNames),
            'description' => $this->faker->paragraph(2),
            'latitude' => $this->faker->randomFloat(7, 14.5, 15.0),
            'longitude' => $this->faker->randomFloat(7, -91.8, -90.2),
            'address' => $this->faker->randomElement($guatemalaAddresses),
            'is_active' => $isActive,
            'delivery_active' => $deliveryActive,
            'pickup_active' => $pickupActive,
            'phone' => '+502 ' . $this->faker->numerify('#### ####'),
            'schedule' => $schedule,
            'minimum_order_amount' => $this->faker->randomFloat(2, 50, 200),
            'delivery_area' => null,
            'image' => null,
            'email' => $this->faker->companyEmail(),
            'manager_name' => $this->faker->name(),
            'delivery_fee' => $deliveryActive ? $this->faker->randomFloat(2, 15, 50) : 0,
            'estimated_delivery_time' => $deliveryActive ? $this->faker->numberBetween(20, 60) : null,
            'rating' => $this->faker->randomFloat(2, 3.0, 5.0),
            'total_reviews' => $this->faker->numberBetween(0, 500),
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}
