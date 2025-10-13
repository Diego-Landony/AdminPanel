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
        $this->command->info('🏪 Creando restaurantes Subway Guatemala con ubicaciones reales...');

        $subwayRestaurants = [
            [
                'name' => 'Subway Pradera Zona 10',
                'address' => '20 Calle 25-85 Zona 10 Centro Comercial Pradera local 364',
                'latitude' => 14.5926,
                'longitude' => -90.5131,
                'phone' => '2386-8686',
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
                'name' => 'Subway Galerías Miraflores II',
                'address' => '21 Avenida 4-32, zona 11. Centro Comercial Galerías Miraflores',
                'latitude' => 14.6147,
                'longitude' => -90.5562,
                'phone' => '2386-8686',
                'email' => 'miraflores@subway.gt',
                'is_active' => true,
                'delivery_active' => true,
                'pickup_active' => true,
                'minimum_order_amount' => 50.00,
                'estimated_delivery_time' => 30,
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
                'name' => 'Subway Parque Las Américas',
                'address' => 'Avenida las Américas 6-69, Zona 14. C.C. Parque las Americas Guatemala Local 315 3 Nivel',
                'latitude' => 14.5964,
                'longitude' => -90.5081,
                'phone' => '2386-8686',
                'email' => 'americas@subway.gt',
                'is_active' => true,
                'delivery_active' => true,
                'pickup_active' => true,
                'minimum_order_amount' => 50.00,
                'estimated_delivery_time' => 30,
                'schedule' => [
                    'monday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:00'],
                    'tuesday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:00'],
                    'wednesday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:00'],
                    'thursday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:00'],
                    'friday' => ['is_open' => true, 'open' => '10:00', 'close' => '22:00'],
                    'saturday' => ['is_open' => true, 'open' => '10:00', 'close' => '22:00'],
                    'sunday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:00'],
                ],
            ],
            [
                'name' => 'Subway Europlaza',
                'address' => '5 Avenida 5-55, Zona 14. Edificio Europlaza Torre 3 1 Nivel Sec. Privada Area Corporativa 109',
                'latitude' => 14.5987,
                'longitude' => -90.5053,
                'phone' => '2386-8686',
                'email' => 'europlaza@subway.gt',
                'is_active' => true,
                'delivery_active' => true,
                'pickup_active' => true,
                'minimum_order_amount' => 45.00,
                'estimated_delivery_time' => 25,
                'schedule' => [
                    'monday' => ['is_open' => true, 'open' => '07:00', 'close' => '19:00'],
                    'tuesday' => ['is_open' => true, 'open' => '07:00', 'close' => '19:00'],
                    'wednesday' => ['is_open' => true, 'open' => '07:00', 'close' => '19:00'],
                    'thursday' => ['is_open' => true, 'open' => '07:00', 'close' => '19:00'],
                    'friday' => ['is_open' => true, 'open' => '07:00', 'close' => '19:00'],
                    'saturday' => ['is_open' => false, 'open' => '00:00', 'close' => '00:00'],
                    'sunday' => ['is_open' => false, 'open' => '00:00', 'close' => '00:00'],
                ],
            ],
            [
                'name' => 'Subway El Frutal Villa Nueva',
                'address' => 'Bulevar El Frutal 14-00, Zona 5. Villa Nueva C.C. El Frutal',
                'latitude' => 14.5253,
                'longitude' => -90.5883,
                'phone' => '2386-8686',
                'email' => 'frutal@subway.gt',
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
            [
                'name' => 'Subway Zona 1 Centro',
                'address' => '8 Avenida 8-14 Zona 1 Guatemala',
                'latitude' => 14.6380,
                'longitude' => -90.5136,
                'phone' => '2386-8686',
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
                'name' => 'Subway Naranjo Mall',
                'address' => '23 Calle 10-00 Zona 4, Condado Naranjo',
                'latitude' => 14.6200,
                'longitude' => -90.5100,
                'phone' => '2386-8686',
                'email' => 'naranjo@subway.gt',
                'is_active' => true,
                'delivery_active' => true,
                'pickup_active' => true,
                'minimum_order_amount' => 50.00,
                'estimated_delivery_time' => 30,
                'schedule' => [
                    'monday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:00'],
                    'tuesday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:00'],
                    'wednesday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:00'],
                    'thursday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:00'],
                    'friday' => ['is_open' => true, 'open' => '10:00', 'close' => '22:00'],
                    'saturday' => ['is_open' => true, 'open' => '10:00', 'close' => '22:00'],
                    'sunday' => ['is_open' => true, 'open' => '10:00', 'close' => '21:00'],
                ],
            ],
            [
                'name' => 'Subway El Recreo Zona 12',
                'address' => '11 avenida 30-33, zona 12. Colonia Santa Rosa local 8',
                'latitude' => 14.5800,
                'longitude' => -90.5400,
                'phone' => '2386-8686',
                'email' => 'recreo@subway.gt',
                'is_active' => true,
                'delivery_active' => true,
                'pickup_active' => true,
                'minimum_order_amount' => 50.00,
                'estimated_delivery_time' => 35,
                'schedule' => [
                    'monday' => ['is_open' => true, 'open' => '09:00', 'close' => '21:00'],
                    'tuesday' => ['is_open' => true, 'open' => '09:00', 'close' => '21:00'],
                    'wednesday' => ['is_open' => true, 'open' => '09:00', 'close' => '21:00'],
                    'thursday' => ['is_open' => true, 'open' => '09:00', 'close' => '21:00'],
                    'friday' => ['is_open' => true, 'open' => '09:00', 'close' => '22:00'],
                    'saturday' => ['is_open' => true, 'open' => '09:00', 'close' => '22:00'],
                    'sunday' => ['is_open' => true, 'open' => '09:00', 'close' => '20:00'],
                ],
            ],
            [
                'name' => 'Subway Plaza Madero Atanasio',
                'address' => 'Diagonal 3 Calzada Atanasio Tzul 17-13, Zona 12 C.C. Plaza Madero Atanasio',
                'latitude' => 14.5750,
                'longitude' => -90.5450,
                'phone' => '2386-8686',
                'email' => 'madero@subway.gt',
                'is_active' => true,
                'delivery_active' => true,
                'pickup_active' => true,
                'minimum_order_amount' => 50.00,
                'estimated_delivery_time' => 35,
                'schedule' => [
                    'monday' => ['is_open' => true, 'open' => '09:00', 'close' => '21:00'],
                    'tuesday' => ['is_open' => true, 'open' => '09:00', 'close' => '21:00'],
                    'wednesday' => ['is_open' => true, 'open' => '09:00', 'close' => '21:00'],
                    'thursday' => ['is_open' => true, 'open' => '09:00', 'close' => '21:00'],
                    'friday' => ['is_open' => true, 'open' => '09:00', 'close' => '22:00'],
                    'saturday' => ['is_open' => true, 'open' => '09:00', 'close' => '22:00'],
                    'sunday' => ['is_open' => true, 'open' => '09:00', 'close' => '20:00'],
                ],
            ],
        ];

        foreach ($subwayRestaurants as $restaurant) {
            Restaurant::create($restaurant);
        }

        $this->command->info('   ✅ 10 restaurantes Subway Guatemala creados con ubicaciones reales');
    }
}
