<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class RestaurantsOnlySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏪 Creando 30 restaurantes Subway fake...');

        $subwayLocations = [
            ['name' => 'Subway Zona 10', 'address' => 'Boulevard Los Próceres, Zona 10, Guatemala', 'lat' => 14.5977, 'lng' => -90.5138],
            ['name' => 'Subway Oakland Mall', 'address' => 'Centro Comercial Oakland Mall, Diagonal 6, Zona 10', 'lat' => 14.6023, 'lng' => -90.5104],
            ['name' => 'Subway Pradera Concepción', 'address' => 'C.C. Pradera Concepción, Carretera a El Salvador', 'lat' => 14.5658, 'lng' => -90.4932],
            ['name' => 'Subway Zona 1', 'address' => '6a Avenida, Zona 1, Centro Histórico, Guatemala', 'lat' => 14.6349, 'lng' => -90.5069],
            ['name' => 'Subway Miraflores', 'address' => 'C.C. Miraflores, 21 Avenida, Zona 11', 'lat' => 14.5981, 'lng' => -90.5507],
            ['name' => 'Subway Las Americas', 'address' => 'Avenida Las Américas, Zona 14, Guatemala', 'lat' => 14.5889, 'lng' => -90.5006],
            ['name' => 'Subway Roosevelt', 'address' => 'Calzada Roosevelt, Zona 11, Guatemala', 'lat' => 14.6125, 'lng' => -90.5478],
            ['name' => 'Subway Zona 4', 'address' => 'Boulevard Liberación, Zona 4, Guatemala', 'lat' => 14.6176, 'lng' => -90.5215],
            ['name' => 'Subway Tikal Futura', 'address' => 'C.C. Tikal Futura, Calzada Roosevelt, Zona 11', 'lat' => 14.6089, 'lng' => -90.5512],
            ['name' => 'Subway Portales', 'address' => 'C.C. Portales, 18 Calle, Zona 10', 'lat' => 14.5955, 'lng' => -90.5098],
            ['name' => 'Subway Arkadia', 'address' => 'C.C. Arkadia, Boulevard Rafael Landívar, Zona 16', 'lat' => 14.6312, 'lng' => -90.5234],
            ['name' => 'Subway Plaza Madero', 'address' => 'C.C. Plaza Madero, 3a Avenida, Zona 10', 'lat' => 14.5945, 'lng' => -90.5112],
            ['name' => 'Subway Majadas Once', 'address' => 'C.C. Majadas Once, Calzada Aguilar Batres, Villa Nueva', 'lat' => 14.5264, 'lng' => -90.5892],
            ['name' => 'Subway Petapa', 'address' => 'Avenida Petapa, Zona 12, Guatemala', 'lat' => 14.5823, 'lng' => -90.5445],
            ['name' => 'Subway Metronorte', 'address' => 'C.C. Metronorte, Calzada San Juan, Zona 7', 'lat' => 14.6445, 'lng' => -90.5267],
            ['name' => 'Subway Naranjo Mall', 'address' => 'Naranjo Mall, Bulevar Vista Hermosa, Zona 15', 'lat' => 14.6089, 'lng' => -90.4923],
            ['name' => 'Subway Gran Via', 'address' => 'C.C. Gran Vía, Calzada Roosevelt, Mixco', 'lat' => 14.6234, 'lng' => -90.5678],
            ['name' => 'Subway Montufar', 'address' => '12 Calle, Zona 9, Guatemala', 'lat' => 14.6023, 'lng' => -90.5156],
            ['name' => 'Subway Pradera Escuintla', 'address' => 'C.C. Pradera Escuintla, Escuintla', 'lat' => 14.3050, 'lng' => -90.7850],
            ['name' => 'Subway Antigua Guatemala', 'address' => '5a Avenida Sur, Antigua Guatemala, Sacatepéquez', 'lat' => 14.5586, 'lng' => -90.7342],
            ['name' => 'Subway Paseo Cayalá', 'address' => 'Paseo Cayalá, 16 Calle, Zona 16', 'lat' => 14.6156, 'lng' => -90.4889],
            ['name' => 'Subway Galerías Primma', 'address' => 'Galerías Primma, Carretera a El Salvador', 'lat' => 14.5612, 'lng' => -90.4845],
            ['name' => 'Subway Pradera Chimaltenango', 'address' => 'C.C. Pradera Chimaltenango, Chimaltenango', 'lat' => 14.6603, 'lng' => -90.8192],
            ['name' => 'Subway Megacentro', 'address' => 'C.C. Megacentro, Villa Nueva', 'lat' => 14.5334, 'lng' => -90.5923],
            ['name' => 'Subway Ciudad San Cristobal', 'address' => 'Ciudad San Cristóbal, Zona 8, Mixco', 'lat' => 14.6567, 'lng' => -90.5634],
            ['name' => 'Subway Metroplaza', 'address' => 'C.C. Metroplaza, Villa Nueva', 'lat' => 14.5289, 'lng' => -90.5867],
            ['name' => 'Subway Pradera Xela', 'address' => 'C.C. Pradera Xela, Quetzaltenango', 'lat' => 14.8333, 'lng' => -91.5167],
            ['name' => 'Subway Interplaza Zona 7', 'address' => 'Interplaza Zona 7, Calzada San Juan', 'lat' => 14.6389, 'lng' => -90.5278],
            ['name' => 'Subway Condado Concepción', 'address' => 'C.C. Condado Concepción, Carretera a El Salvador', 'lat' => 14.5678, 'lng' => -90.4923],
            ['name' => 'Subway Fontabella', 'address' => 'C.C. Fontabella, Boulevard Rafael Landívar, Zona 16', 'lat' => 14.6234, 'lng' => -90.5189],
        ];

        foreach ($subwayLocations as $location) {
            Restaurant::create([
                'name' => $location['name'],
                'address' => $location['address'],
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
                'phone' => '+502 '.rand(2200, 2599).'-'.rand(1000, 9999),
                'email' => strtolower(str_replace(' ', '.', $location['name'])).'@subway.com.gt',
                'is_active' => true,
                'delivery_active' => rand(0, 10) > 2, // 80% tienen delivery
                'pickup_active' => true,
                'minimum_order_amount' => rand(50, 100),
                'estimated_delivery_time' => rand(25, 45),
            ]);
        }

        $this->command->info('✅ 30 restaurantes Subway creados exitosamente!');
    }
}
