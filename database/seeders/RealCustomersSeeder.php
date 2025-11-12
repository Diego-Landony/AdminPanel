<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RealCustomersSeeder extends Seeder
{
    /**
     * Seeder de clientes realistas de Subway Guatemala
     * Basado en los tipos de cliente: Regular, Bronce, Plata, Oro, Platino
     */
    public function run(): void
    {
        $this->command->info('👤 Creando clientes realistas...');

        // Obtener tipos de cliente
        $tipoRegular = CustomerType::where('name', 'Regular')->first();
        $tipoBronce = CustomerType::where('name', 'Bronce')->first();
        $tipoPlata = CustomerType::where('name', 'Plata')->first();
        $tipoOro = CustomerType::where('name', 'Oro')->first();
        $tipoPlatino = CustomerType::where('name', 'Platino')->first();

        // Clientes REGULARES (10-40 points)
        $this->createRegularCustomers($tipoRegular);

        // Clientes BRONCE (50-120 points)
        $this->createBronceCustomers($tipoBronce);

        // Clientes PLATA (125-320 points)
        $this->createPlataCustomers($tipoPlata);

        // Clientes ORO (325-950 points)
        $this->createOroCustomers($tipoOro);

        // Clientes PLATINO (1000+ points)
        $this->createPlatinoCustomers($tipoPlatino);

        $this->command->info('   ✅ 50 clientes realistas creados');
    }

    private function createRegularCustomers(CustomerType $tipo): void
    {
        $clientes = [
            ['name' => 'Carlos Méndez', 'email' => 'carlos.mendez@gmail.com', 'points' => 15, 'phone' => '4123-4567'],
            ['name' => 'María García', 'email' => 'maria.garcia@hotmail.com', 'points' => 22, 'phone' => '5234-5678'],
            ['name' => 'José Hernández', 'email' => 'jose.hernandez@yahoo.com', 'points' => 8, 'phone' => '3345-6789'],
            ['name' => 'Ana López', 'email' => 'ana.lopez@gmail.com', 'points' => 30, 'phone' => '4456-7890'],
            ['name' => 'Pedro Ramírez', 'email' => 'pedro.ramirez@outlook.com', 'points' => 18, 'phone' => '5567-8901'],
            ['name' => 'Lucía Morales', 'email' => 'lucia.morales@gmail.com', 'points' => 25, 'phone' => '6678-9012'],
            ['name' => 'Miguel Torres', 'email' => 'miguel.torres@hotmail.com', 'points' => 12, 'phone' => '7789-0123'],
            ['name' => 'Carmen Flores', 'email' => 'carmen.flores@gmail.com', 'points' => 35, 'phone' => '4890-1234'],
            ['name' => 'Roberto Castro', 'email' => 'roberto.castro@yahoo.com', 'points' => 20, 'phone' => '5901-2345'],
            ['name' => 'Diana Reyes', 'email' => 'diana.reyes@gmail.com', 'points' => 28, 'phone' => '3012-3456'],
        ];

        foreach ($clientes as $cliente) {
            $customer = Customer::create([
                'name' => $cliente['name'],
                'email' => $cliente['email'],
                'password' => Hash::make('password123'),
                'subway_card' => $this->generateSubwayCard(),
                'birth_date' => now()->subYears(rand(20, 55))->format('Y-m-d'),
                'gender' => rand(0, 1) ? 'M' : 'F',
                'customer_type_id' => $tipo->id,
                'phone' => $cliente['phone'],
                'points' => $cliente['points'],
                'points_updated_at' => now()->subDays(rand(1, 30)),
                'last_purchase_at' => now()->subDays(rand(1, 15)),
                'last_login_at' => now()->subDays(rand(0, 7)),
                'email_verified_at' => now(),
            ]);

            $this->createCustomerRelations($customer);
        }

        $this->command->line('   ✓ 10 clientes Regular creados');
    }

    private function createBronceCustomers(CustomerType $tipo): void
    {
        $clientes = [
            ['name' => 'Fernando Gómez', 'email' => 'fernando.gomez@gmail.com', 'points' => 65, 'phone' => '4123-5678'],
            ['name' => 'Patricia Ruiz', 'email' => 'patricia.ruiz@hotmail.com', 'points' => 78, 'phone' => '5234-6789'],
            ['name' => 'Ricardo Vargas', 'email' => 'ricardo.vargas@gmail.com', 'points' => 55, 'phone' => '3345-7890'],
            ['name' => 'Sofía Ortiz', 'email' => 'sofia.ortiz@yahoo.com', 'points' => 92, 'phone' => '4456-8901'],
            ['name' => 'Antonio Sánchez', 'email' => 'antonio.sanchez@outlook.com', 'points' => 68, 'phone' => '5567-9012'],
            ['name' => 'Gabriela Díaz', 'email' => 'gabriela.diaz@gmail.com', 'points' => 85, 'phone' => '6678-0123'],
            ['name' => 'Javier Cruz', 'email' => 'javier.cruz@hotmail.com', 'points' => 72, 'phone' => '7789-1234'],
            ['name' => 'Valeria Jiménez', 'email' => 'valeria.jimenez@gmail.com', 'points' => 105, 'phone' => '4890-2345'],
            ['name' => 'Eduardo Navarro', 'email' => 'eduardo.navarro@yahoo.com', 'points' => 58, 'phone' => '5901-3456'],
            ['name' => 'Mónica Peña', 'email' => 'monica.pena@gmail.com', 'points' => 115, 'phone' => '3012-4567'],
        ];

        foreach ($clientes as $cliente) {
            $customer = Customer::create([
                'name' => $cliente['name'],
                'email' => $cliente['email'],
                'password' => Hash::make('password123'),
                'subway_card' => $this->generateSubwayCard(),
                'birth_date' => now()->subYears(rand(22, 50))->format('Y-m-d'),
                'gender' => rand(0, 1) ? 'M' : 'F',
                'customer_type_id' => $tipo->id,
                'phone' => $cliente['phone'],
                'points' => $cliente['points'],
                'points_updated_at' => now()->subDays(rand(1, 20)),
                'last_purchase_at' => now()->subDays(rand(1, 10)),
                'last_login_at' => now()->subDays(rand(0, 5)),
                'email_verified_at' => now(),
            ]);

            $this->createCustomerRelations($customer);
        }

        $this->command->line('   ✓ 10 clientes Bronce creados');
    }

    private function createPlataCustomers(CustomerType $tipo): void
    {
        $clientes = [
            ['name' => 'Alejandro Mendoza', 'email' => 'alejandro.mendoza@gmail.com', 'points' => 145, 'phone' => '4123-6789'],
            ['name' => 'Laura Guzmán', 'email' => 'laura.guzman@hotmail.com', 'points' => 198, 'phone' => '5234-7890'],
            ['name' => 'Francisco Herrera', 'email' => 'francisco.herrera@gmail.com', 'points' => 167, 'phone' => '3345-8901'],
            ['name' => 'Isabella Rojas', 'email' => 'isabella.rojas@yahoo.com', 'points' => 225, 'phone' => '4456-9012'],
            ['name' => 'Sergio Molina', 'email' => 'sergio.molina@outlook.com', 'points' => 178, 'phone' => '5567-0123'],
            ['name' => 'Camila Aguilar', 'email' => 'camila.aguilar@gmail.com', 'points' => 255, 'phone' => '6678-1234'],
            ['name' => 'Daniel Medina', 'email' => 'daniel.medina@hotmail.com', 'points' => 189, 'phone' => '7789-2345'],
            ['name' => 'Natalia Romero', 'email' => 'natalia.romero@gmail.com', 'points' => 310, 'phone' => '4890-3456'],
            ['name' => 'Andrés Silva', 'email' => 'andres.silva@yahoo.com', 'points' => 205, 'phone' => '5901-4567'],
            ['name' => 'Victoria Vega', 'email' => 'victoria.vega@gmail.com', 'points' => 268, 'phone' => '3012-5678'],
        ];

        foreach ($clientes as $cliente) {
            $customer = Customer::create([
                'name' => $cliente['name'],
                'email' => $cliente['email'],
                'password' => Hash::make('password123'),
                'subway_card' => $this->generateSubwayCard(),
                'birth_date' => now()->subYears(rand(25, 48))->format('Y-m-d'),
                'gender' => rand(0, 1) ? 'M' : 'F',
                'customer_type_id' => $tipo->id,
                'phone' => $cliente['phone'],
                'points' => $cliente['points'],
                'points_updated_at' => now()->subDays(rand(1, 14)),
                'last_purchase_at' => now()->subDays(rand(1, 7)),
                'last_login_at' => now()->subDays(rand(0, 3)),
                'email_verified_at' => now(),
            ]);

            $this->createCustomerRelations($customer);
        }

        $this->command->line('   ✓ 10 clientes Plata creados');
    }

    private function createOroCustomers(CustomerType $tipo): void
    {
        $clientes = [
            ['name' => 'Mauricio Campos', 'email' => 'mauricio.campos@gmail.com', 'points' => 385, 'phone' => '4123-7890'],
            ['name' => 'Elena Ramos', 'email' => 'elena.ramos@hotmail.com', 'points' => 452, 'phone' => '5234-8901'],
            ['name' => 'Rodrigo Márquez', 'email' => 'rodrigo.marquez@gmail.com', 'points' => 412, 'phone' => '3345-9012'],
            ['name' => 'Paola Núñez', 'email' => 'paola.nunez@yahoo.com', 'points' => 568, 'phone' => '4456-0123'],
            ['name' => 'Gustavo Paredes', 'email' => 'gustavo.paredes@outlook.com', 'points' => 495, 'phone' => '5567-1234'],
            ['name' => 'Mariana Soto', 'email' => 'mariana.soto@gmail.com', 'points' => 725, 'phone' => '6678-2345'],
            ['name' => 'Jorge Delgado', 'email' => 'jorge.delgado@hotmail.com', 'points' => 638, 'phone' => '7789-3456'],
            ['name' => 'Daniela Cabrera', 'email' => 'daniela.cabrera@gmail.com', 'points' => 892, 'phone' => '4890-4567'],
            ['name' => 'Luis Miranda', 'email' => 'luis.miranda@yahoo.com', 'points' => 547, 'phone' => '5901-5678'],
            ['name' => 'Andrea Escobar', 'email' => 'andrea.escobar@gmail.com', 'points' => 675, 'phone' => '3012-6789'],
        ];

        foreach ($clientes as $cliente) {
            $customer = Customer::create([
                'name' => $cliente['name'],
                'email' => $cliente['email'],
                'password' => Hash::make('password123'),
                'subway_card' => $this->generateSubwayCard(),
                'birth_date' => now()->subYears(rand(28, 55))->format('Y-m-d'),
                'gender' => rand(0, 1) ? 'M' : 'F',
                'customer_type_id' => $tipo->id,
                'phone' => $cliente['phone'],
                'points' => $cliente['points'],
                'points_updated_at' => now()->subDays(rand(1, 10)),
                'last_purchase_at' => now()->subDays(rand(1, 5)),
                'last_login_at' => now()->subDays(rand(0, 2)),
                'email_verified_at' => now(),
            ]);

            $this->createCustomerRelations($customer);
        }

        $this->command->line('   ✓ 10 clientes Oro creados');
    }

    private function createPlatinoCustomers(CustomerType $tipo): void
    {
        $clientes = [
            ['name' => 'Santiago Fuentes', 'email' => 'santiago.fuentes@gmail.com', 'points' => 1125, 'phone' => '4123-8901'],
            ['name' => 'Regina Villanueva', 'email' => 'regina.villanueva@hotmail.com', 'points' => 1456, 'phone' => '5234-9012'],
            ['name' => 'Emilio Córdoba', 'email' => 'emilio.cordoba@gmail.com', 'points' => 1298, 'phone' => '3345-0123'],
            ['name' => 'Valentina Padilla', 'email' => 'valentina.padilla@yahoo.com', 'points' => 1687, 'phone' => '4456-1234'],
            ['name' => 'Héctor Salazar', 'email' => 'hector.salazar@outlook.com', 'points' => 1543, 'phone' => '5567-2345'],
            ['name' => 'Alejandra Montes', 'email' => 'alejandra.montes@gmail.com', 'points' => 2125, 'phone' => '6678-3456'],
            ['name' => 'Cristian Ibarra', 'email' => 'cristian.ibarra@hotmail.com', 'points' => 1834, 'phone' => '7789-4567'],
            ['name' => 'Fernanda Paz', 'email' => 'fernanda.paz@gmail.com', 'points' => 2547, 'phone' => '4890-5678'],
            ['name' => 'Óscar Valencia', 'email' => 'oscar.valencia@yahoo.com', 'points' => 1975, 'phone' => '5901-6789'],
            ['name' => 'Carolina Ríos', 'email' => 'carolina.rios@gmail.com', 'points' => 3012, 'phone' => '3012-7890'],
        ];

        foreach ($clientes as $cliente) {
            $customer = Customer::create([
                'name' => $cliente['name'],
                'email' => $cliente['email'],
                'password' => Hash::make('password123'),
                'subway_card' => $this->generateSubwayCard(),
                'birth_date' => now()->subYears(rand(30, 60))->format('Y-m-d'),
                'gender' => rand(0, 1) ? 'M' : 'F',
                'customer_type_id' => $tipo->id,
                'phone' => $cliente['phone'],
                'points' => $cliente['points'],
                'points_updated_at' => now()->subDays(rand(1, 7)),
                'last_purchase_at' => now()->subDays(rand(1, 3)),
                'last_login_at' => now()->subHours(rand(1, 48)),
                'email_verified_at' => now(),
            ]);

            $this->createCustomerRelations($customer);
        }

        $this->command->line('   ✓ 10 clientes Platino creados');
    }

    private function generateSubwayCard(): string
    {
        return str_pad(rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
    }

    private function generateNIT(): string
    {
        return rand(100000, 9999999).'-'.rand(0, 9);
    }

    private function getRandomAddress(): string
    {
        $addresses = [
            '5ta Avenida 12-45 Zona 10, Guatemala',
            'Boulevard Los Próceres 24-69 Zona 10, Guatemala',
            '11 Calle 3-17 Zona 1, Guatemala',
            'Diagonal 6 10-01 Zona 10, Oakland Mall',
            'Calzada Roosevelt 22-43 Zona 11, Guatemala',
            '18 Calle 5-56 Zona 10, Pradera',
            'Avenida Las Américas 6-69 Zona 14, Guatemala',
            '7ma Avenida 15-23 Zona 9, Guatemala',
            'Boulevard Vista Hermosa 23-78 Zona 15, Guatemala',
            'Carretera a El Salvador Km 15.5, Villa Nueva',
        ];

        return $addresses[array_rand($addresses)];
    }

    private function getRandomLocation(): string
    {
        $locations = [
            'Guatemala, Guatemala',
            'Mixco, Guatemala',
            'Villa Nueva, Guatemala',
            'San Miguel Petapa, Guatemala',
            'Villa Canales, Guatemala',
        ];

        return $locations[array_rand($locations)];
    }

    private function createCustomerRelations(Customer $customer): void
    {
        // Crear dirección por defecto
        $customer->addresses()->create([
            'label' => 'Casa',
            'address_line' => $this->getRandomAddress(),
            'latitude' => 14.6000 + (rand(-1000, 1000) / 10000),
            'longitude' => -90.5000 + (rand(-1000, 1000) / 10000),
            'delivery_notes' => null,
            'is_default' => true,
        ]);

        // Crear NIT por defecto
        $customer->nits()->create([
            'nit' => $this->generateNIT(),
            'nit_type' => 'personal',
            'business_name' => null,
            'is_default' => true,
        ]);

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
