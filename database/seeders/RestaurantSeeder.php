<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subwayRestaurants = [
            [
                'name' => 'Subway Zone 10',
                'address' => 'Avenida La Reforma 9-00, Zona 10, Ciudad de Guatemala',
                'latitude' => 14.600000,
                'longitude' => -90.513333,
                'phone' => '2360-0123',
                'email' => 'zone10@subway.gt',
                'is_active' => true,
                'delivery_active' => true,
                'pickup_active' => true,
                'minimum_order_amount' => 50.00,
                'estimated_delivery_time' => 30,
                'schedule' => [
                    'monday' => ['is_open' => true, 'open' => '07:00', 'close' => '22:00'],
                    'tuesday' => ['is_open' => true, 'open' => '07:00', 'close' => '22:00'],
                    'wednesday' => ['is_open' => true, 'open' => '07:00', 'close' => '22:00'],
                    'thursday' => ['is_open' => true, 'open' => '07:00', 'close' => '22:00'],
                    'friday' => ['is_open' => true, 'open' => '07:00', 'close' => '23:00'],
                    'saturday' => ['is_open' => true, 'open' => '08:00', 'close' => '23:00'],
                    'sunday' => ['is_open' => true, 'open' => '08:00', 'close' => '22:00'],
                ],
            ],
            [
                'name' => 'Subway Oakland Mall',
                'address' => 'Oakland Mall, Diagonal 6 13-01, Zona 10, Ciudad de Guatemala',
                'latitude' => 14.597222,
                'longitude' => -90.506667,
                'phone' => '2277-4567',
                'email' => 'oakland@subway.gt',
                'is_active' => true,
                'delivery_active' => true,
                'pickup_active' => true,
                'minimum_order_amount' => 45.00,
                'estimated_delivery_time' => 25,
                'schedule' => [
                    'monday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:30'],
                    'tuesday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:30'],
                    'wednesday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:30'],
                    'thursday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:30'],
                    'friday' => ['is_open' => true, 'open' => '10:00', 'close' => '22:00'],
                    'saturday' => ['is_open' => true, 'open' => '10:00', 'close' => '22:00'],
                    'sunday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:00'],
                ],
            ],
            [
                'name' => 'Subway Pradera Zona 10',
                'address' => 'Centro Comercial Pradera, 18 Calle 5-56, Zona 10, Ciudad de Guatemala',
                'latitude' => 14.592500,
                'longitude' => -90.513056,
                'phone' => '2360-7890',
                'email' => 'pradera@subway.gt',
                'is_active' => true,
                'delivery_active' => true,
                'pickup_active' => true,
                'minimum_order_amount' => 50.00,
                'estimated_delivery_time' => 35,
                'schedule' => [
                    'monday' => ['is_open' => true, 'open' => '08:00', 'close' => '22:00'],
                    'tuesday' => ['is_open' => true, 'open' => '08:00', 'close' => '22:00'],
                    'wednesday' => ['is_open' => true, 'open' => '08:00', 'close' => '22:00'],
                    'thursday' => ['is_open' => true, 'open' => '08:00', 'close' => '22:00'],
                    'friday' => ['is_open' => true, 'open' => '08:00', 'close' => '23:00'],
                    'saturday' => ['is_open' => true, 'open' => '08:00', 'close' => '23:00'],
                    'sunday' => ['is_open' => true, 'open' => '09:00', 'close' => '21:00'],
                ],
            ],
            [
                'name' => 'Subway Centro Histórico',
                'address' => '6a Avenida 12-23, Zona 1, Ciudad de Guatemala',
                'latitude' => 14.638056,
                'longitude' => -90.513611,
                'phone' => '2251-3456',
                'email' => 'centro@subway.gt',
                'is_active' => true,
                'delivery_active' => false,
                'pickup_active' => true,
                'minimum_order_amount' => 40.00,
                'estimated_delivery_time' => 20,
                'schedule' => [
                    'monday' => ['is_open' => true, 'open' => '07:30', 'close' => '18:00'],
                    'tuesday' => ['is_open' => true, 'open' => '07:30', 'close' => '18:00'],
                    'wednesday' => ['is_open' => true, 'open' => '07:30', 'close' => '18:00'],
                    'thursday' => ['is_open' => true, 'open' => '07:30', 'close' => '18:00'],
                    'friday' => ['is_open' => true, 'open' => '07:30', 'close' => '19:00'],
                    'saturday' => ['is_open' => true, 'open' => '08:00', 'close' => '17:00'],
                    'sunday' => ['is_open' => false, 'open' => '00:00', 'close' => '00:00'],
                ],
            ],
            [
                'name' => 'Subway Mixco',
                'address' => 'Centro Comercial Centra Norte, 5ta Calle A 4-20, Zona 4, Mixco',
                'latitude' => 14.631944,
                'longitude' => -90.606111,
                'phone' => '2433-9876',
                'email' => 'mixco@subway.gt',
                'is_active' => true,
                'delivery_active' => true,
                'pickup_active' => true,
                'minimum_order_amount' => 55.00,
                'estimated_delivery_time' => 40,
                'schedule' => [
                    'monday' => ['is_open' => true, 'open' => '08:30', 'close' => '21:30'],
                    'tuesday' => ['is_open' => true, 'open' => '08:30', 'close' => '21:30'],
                    'wednesday' => ['is_open' => true, 'open' => '08:30', 'close' => '21:30'],
                    'thursday' => ['is_open' => true, 'open' => '08:30', 'close' => '21:30'],
                    'friday' => ['is_open' => true, 'open' => '08:30', 'close' => '22:00'],
                    'saturday' => ['is_open' => true, 'open' => '09:00', 'close' => '22:00'],
                    'sunday' => ['is_open' => true, 'open' => '09:00', 'close' => '20:00'],
                ],
            ],
            [
                'name' => 'Subway Villa Nueva',
                'address' => 'Centro Comercial Metronorte, Km 16.5 Carretera a El Salvador, Villa Nueva',
                'latitude' => 14.525000,
                'longitude' => -90.588333,
                'phone' => '2440-5432',
                'email' => 'villanueva@subway.gt',
                'is_active' => true,
                'delivery_active' => true,
                'pickup_active' => true,
                'minimum_order_amount' => 60.00,
                'estimated_delivery_time' => 45,
                'schedule' => [
                    'monday' => ['is_open' => true, 'open' => '09:00', 'close' => '21:00'],
                    'tuesday' => ['is_open' => true, 'open' => '09:00', 'close' => '21:00'],
                    'wednesday' => ['is_open' => true, 'open' => '09:00', 'close' => '21:00'],
                    'thursday' => ['is_open' => true, 'open' => '09:00', 'close' => '21:00'],
                    'friday' => ['is_open' => true, 'open' => '09:00', 'close' => '22:00'],
                    'saturday' => ['is_open' => true, 'open' => '09:00', 'close' => '22:00'],
                    'sunday' => ['is_open' => true, 'open' => '10:00', 'close' => '20:00'],
                ],
            ],
        ];

        foreach ($subwayRestaurants as $restaurant) {
            Restaurant::create($restaurant);
        }
    }
}
