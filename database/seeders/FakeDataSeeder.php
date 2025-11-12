<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;

class FakeDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Creando 10 usuarios fake...');
        $this->createUsers();

        $this->command->info('👥 Creando 30 clientes fake con diferentes tipos...');
        $this->createCustomers();

        $this->command->info('🏪 Creando 30 restaurantes Subway fake...');
        $this->createRestaurants();

        $this->command->info('✅ Datos fake creados exitosamente!');
    }

    /**
     * Crear 10 usuarios fake
     */
    private function createUsers(): void
    {
        // Nombres guatemaltecos realistas
        $guatemalanNames = [
            ['name' => 'Carlos Enrique Morales García', 'email' => 'carlos.morales@gmail.com'],
            ['name' => 'María José Hernández López', 'email' => 'maria.hernandez@yahoo.com'],
            ['name' => 'José Antonio Pérez Rodríguez', 'email' => 'jose.perez@hotmail.com'],
            ['name' => 'Ana Lucía Ramírez Santos', 'email' => 'ana.ramirez@gmail.com'],
            ['name' => 'Luis Fernando García Martínez', 'email' => 'luis.garcia@outlook.com'],
            ['name' => 'Claudia Patricia Flores Díaz', 'email' => 'claudia.flores@gmail.com'],
            ['name' => 'Roberto Carlos Méndez Ruiz', 'email' => 'roberto.mendez@yahoo.com'],
            ['name' => 'Silvia Elena Torres Vásquez', 'email' => 'silvia.torres@gmail.com'],
            ['name' => 'Marco Antonio López Gómez', 'email' => 'marco.lopez@hotmail.com'],
            ['name' => 'Patricia Isabel Cruz Castillo', 'email' => 'patricia.cruz@gmail.com'],
        ];

        foreach ($guatemalanNames as $userData) {
            User::factory()
                ->state([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                ])
                ->create();
        }
    }

    /**
     * Crear 30 clientes fake distribuidos por tipos
     */
    private function createCustomers(): void
    {
        // Obtener los tipos de cliente desde la base de datos
        $customerTypes = \App\Models\CustomerType::pluck('id', 'name')->toArray();

        // Nombres guatemaltecos realistas para clientes
        $guatemalanCustomers = [
            // Regular (10 clientes con 0-49 puntos)
            ['name' => 'Juan Carlos Cifuentes Méndez', 'email' => 'juan.cifuentes@gmail.com', 'type_name' => 'Regular', 'points' => 15],
            ['name' => 'Sofía Alejandra Morales Díaz', 'email' => 'sofia.morales@yahoo.com', 'type_name' => 'Regular', 'points' => 22],
            ['name' => 'Pedro Luis González Ramírez', 'email' => 'pedro.gonzalez@hotmail.com', 'type_name' => 'Regular', 'points' => 8],
            ['name' => 'Carmen Rosa Pérez López', 'email' => 'carmen.perez@gmail.com', 'type_name' => 'Regular', 'points' => 35],
            ['name' => 'Diego Armando Castillo Flores', 'email' => 'diego.castillo@outlook.com', 'type_name' => 'Regular', 'points' => 18],
            ['name' => 'Gabriela Fernanda Herrera Santos', 'email' => 'gabriela.herrera@gmail.com', 'type_name' => 'Regular', 'points' => 42],
            ['name' => 'Miguel Ángel Rodríguez García', 'email' => 'miguel.rodriguez@yahoo.com', 'type_name' => 'Regular', 'points' => 12],
            ['name' => 'Valentina Isabella Ortiz Martínez', 'email' => 'valentina.ortiz@gmail.com', 'type_name' => 'Regular', 'points' => 28],
            ['name' => 'Fernando José Vásquez Ruiz', 'email' => 'fernando.vasquez@hotmail.com', 'type_name' => 'Regular', 'points' => 38],
            ['name' => 'Andrea Carolina Méndez Torres', 'email' => 'andrea.mendez@gmail.com', 'type_name' => 'Regular', 'points' => 45],

            // Bronce (8 clientes con 50-124 puntos)
            ['name' => 'Ricardo Daniel López Cruz', 'email' => 'ricardo.lopez@gmail.com', 'type_name' => 'Bronce', 'points' => 65],
            ['name' => 'Mónica Patricia García Sánchez', 'email' => 'monica.garcia@yahoo.com', 'type_name' => 'Bronce', 'points' => 78],
            ['name' => 'Javier Enrique Ramírez Gómez', 'email' => 'javier.ramirez@hotmail.com', 'type_name' => 'Bronce', 'points' => 92],
            ['name' => 'Lucía Fernanda Díaz Morales', 'email' => 'lucia.diaz@gmail.com', 'type_name' => 'Bronce', 'points' => 58],
            ['name' => 'Alejandro Manuel Flores Pérez', 'email' => 'alejandro.flores@outlook.com', 'type_name' => 'Bronce', 'points' => 105],
            ['name' => 'Diana Sofía Santos Hernández', 'email' => 'diana.santos@gmail.com', 'type_name' => 'Bronce', 'points' => 82],
            ['name' => 'Jorge Luis Martínez Castillo', 'email' => 'jorge.martinez@yahoo.com', 'type_name' => 'Bronce', 'points' => 115],
            ['name' => 'Isabella María Rodríguez López', 'email' => 'isabella.rodriguez@gmail.com', 'type_name' => 'Bronce', 'points' => 95],

            // Plata (6 clientes con 125-324 puntos)
            ['name' => 'Eduardo Antonio García Ruiz', 'email' => 'eduardo.garcia@gmail.com', 'type_name' => 'Plata', 'points' => 185],
            ['name' => 'Daniela Alejandra Pérez Torres', 'email' => 'daniela.perez@yahoo.com', 'type_name' => 'Plata', 'points' => 225],
            ['name' => 'Gustavo Adolfo Méndez Díaz', 'email' => 'gustavo.mendez@hotmail.com', 'type_name' => 'Plata', 'points' => 265],
            ['name' => 'Carolina Isabel Flores Morales', 'email' => 'carolina.flores@gmail.com', 'type_name' => 'Plata', 'points' => 198],
            ['name' => 'Rodrigo Sebastián López García', 'email' => 'rodrigo.lopez@outlook.com', 'type_name' => 'Plata', 'points' => 285],
            ['name' => 'Natalia Valeria Ramírez Santos', 'email' => 'natalia.ramirez@gmail.com', 'type_name' => 'Plata', 'points' => 245],

            // Oro (4 clientes con 325-999 puntos)
            ['name' => 'Alberto Francisco Martínez López', 'email' => 'alberto.martinez@gmail.com', 'type_name' => 'Oro', 'points' => 485],
            ['name' => 'Paola Andrea García Pérez', 'email' => 'paola.garcia@yahoo.com', 'type_name' => 'Oro', 'points' => 625],
            ['name' => 'Sergio Alejandro Díaz Rodríguez', 'email' => 'sergio.diaz@hotmail.com', 'type_name' => 'Oro', 'points' => 765],
            ['name' => 'Mariana José Flores Hernández', 'email' => 'mariana.flores@gmail.com', 'type_name' => 'Oro', 'points' => 545],

            // Platino (2 clientes con 1000+ puntos)
            ['name' => 'Francisco Javier López Martínez', 'email' => 'francisco.lopez@gmail.com', 'type_name' => 'Platino', 'points' => 1450],
            ['name' => 'Victoria Eugenia García Ruiz', 'email' => 'victoria.garcia@yahoo.com', 'type_name' => 'Platino', 'points' => 2150],
        ];

        foreach ($guatemalanCustomers as $customerData) {
            // Generar tarjeta Subway única (10 dígitos)
            $subwayCard = str_pad(rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);

            $customer = Customer::factory()
                ->state([
                    'name' => $customerData['name'],
                    'email' => $customerData['email'],
                    'customer_type_id' => $customerTypes[$customerData['type_name']],
                    'points' => $customerData['points'],
                    'subway_card' => $subwayCard,
                    'phone' => '+502 '.rand(3000, 5999).' '.rand(1000, 9999),
                    'gender' => rand(0, 1) ? 'masculino' : 'femenino',
                    'last_purchase_at' => now()->subDays(rand(1, 60)),
                ])
                ->create();

            // Crear 1-3 direcciones para cada cliente
            $numberOfAddresses = rand(1, 3);
            for ($i = 0; $i < $numberOfAddresses; $i++) {
                CustomerAddress::factory()
                    ->state([
                        'customer_id' => $customer->id,
                        'is_default' => $i === 0, // La primera es la predeterminada
                    ])
                    ->create();
            }

            // Crear 1-3 dispositivos para cada cliente
            $deviceCount = rand(1, 3);
            for ($i = 0; $i < $deviceCount; $i++) {
                \App\Models\CustomerDevice::factory()
                    ->active()
                    ->create([
                        'customer_id' => $customer->id,
                    ]);
            }
        }
    }

    /**
     * Crear 30 restaurantes Subway fake
     */
    private function createRestaurants(): void
    {
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
            Restaurant::factory()
                ->state([
                    'name' => $location['name'],
                    'address' => $location['address'],
                    'latitude' => $location['lat'],
                    'longitude' => $location['lng'],
                    'phone' => '+502 '.rand(2200, 2599).' '.rand(1000, 9999),
                    'email' => strtolower(str_replace(' ', '.', $location['name'])).'@subway.com.gt',
                    'is_active' => true,
                    'delivery_active' => rand(0, 10) > 2, // 80% tienen delivery
                    'pickup_active' => true,
                    'minimum_order_amount' => rand(50, 100),
                    'estimated_delivery_time' => rand(25, 45),
                ])
                ->create();
        }
    }

    /**
     * Obtener nombre guatemalteco aleatorio
     */
    private function getRandomGuatemalaName(): string
    {
        $names = [
            'Carlos Enrique Morales',
            'María José Hernández',
            'José Antonio Pérez',
            'Ana Lucía Ramírez',
            'Luis Fernando García',
            'Claudia Patricia Flores',
            'Roberto Carlos Méndez',
            'Silvia Elena Torres',
            'Marco Antonio López',
            'Patricia Isabel Cruz',
        ];

        return $names[array_rand($names)];
    }

    /**
     * Obtener coordenadas aleatorias de Guatemala City y alrededores
     */
    private function getRandomGuatemalaCoordinates(): array
    {
        // Coordenadas de diferentes zonas de Guatemala
        $zones = [
            ['lat' => 14.6349, 'lng' => -90.5069], // Centro Guatemala City
            ['lat' => 14.6095, 'lng' => -90.5289], // Zona 10
            ['lat' => 14.5927, 'lng' => -90.5131], // Zona 14
            ['lat' => 14.6123, 'lng' => -90.4892], // Zona 15
            ['lat' => 14.6234, 'lng' => -90.4721], // Zona 16
            ['lat' => 14.5260, 'lng' => -90.5866], // Villa Nueva
            ['lat' => 14.6333, 'lng' => -90.6144], // Mixco
            ['lat' => 14.5608, 'lng' => -90.7344], // Antigua Guatemala
            ['lat' => 14.3003, 'lng' => -90.7827], // Escuintla
            ['lat' => 14.6611, 'lng' => -90.8200], // Chimaltenango
        ];

        $baseCoords = $zones[array_rand($zones)];

        // Agregar pequeña variación aleatoria (±0.01 grados ≈ ±1km)
        return [
            'lat' => $baseCoords['lat'] + (rand(-100, 100) / 10000),
            'lng' => $baseCoords['lng'] + (rand(-100, 100) / 10000),
        ];
    }
}
