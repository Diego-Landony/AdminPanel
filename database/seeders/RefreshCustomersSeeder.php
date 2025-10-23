<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerType;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class RefreshCustomersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🗑️  Eliminando todos los clientes existentes...');

        // Eliminar todos los registros de customers
        Customer::query()->forceDelete(); // forceDelete para eliminar también soft deleted

        $this->command->info('✅ Clientes eliminados correctamente');

        $this->command->info('🌎 Configurando Faker con locale español...');

        // Configurar Faker con locale español
        $faker = Faker::create('es_ES');

        $this->command->info('👥 Creando 50 nuevos clientes...');

        // Obtener tipos de cliente disponibles
        $customerTypes = CustomerType::active()->get();

        if ($customerTypes->isEmpty()) {
            $this->command->warn('⚠️  No hay tipos de cliente disponibles. Creando tipos básicos...');

            // Crear tipos básicos si no existen
            $regularType = CustomerType::create([
                'name' => 'regular',
                'display_name' => 'Regular',
                'points_required' => 0,
                'multiplier' => 1.00,
                'color' => '#6b7280',
                'is_active' => true,
                'sort_order' => 1,
            ]);

            $bronzeType = CustomerType::create([
                'name' => 'bronze',
                'display_name' => 'Bronce',
                'points_required' => 50,
                'multiplier' => 1.10,
                'color' => '#cd7f32',
                'is_active' => true,
                'sort_order' => 2,
            ]);

            $silverType = CustomerType::create([
                'name' => 'silver',
                'display_name' => 'Plata',
                'points_required' => 125,
                'multiplier' => 1.25,
                'color' => '#c0c0c0',
                'is_active' => true,
                'sort_order' => 3,
            ]);

            $goldType = CustomerType::create([
                'name' => 'gold',
                'display_name' => 'Oro',
                'points_required' => 325,
                'multiplier' => 1.50,
                'color' => '#ffd700',
                'is_active' => true,
                'sort_order' => 4,
            ]);

            $platinumType = CustomerType::create([
                'name' => 'platinum',
                'display_name' => 'Platino',
                'points_required' => 1000,
                'multiplier' => 2.00,
                'color' => '#e5e4e2',
                'is_active' => true,
                'sort_order' => 5,
            ]);

            $customerTypes = collect([$regularType, $bronzeType, $silverType, $goldType, $platinumType]);
        }

        // Generar 50 clientes con datos en español
        for ($i = 1; $i <= 50; $i++) {
            // Seleccionar tipo de cliente aleatoriamente
            $customerType = $customerTypes->random();

            // Generar points apropiados para el tipo de cliente
            $points = $this->generatePointsForType($customerType, $faker);

            // Generar género y nombre apropiado
            $gender = $faker->randomElement(['masculino', 'femenino']);
            $fullName = $gender === 'femenino'
                ? $faker->firstNameFemale().' '.$faker->lastName().' '.$faker->lastName()
                : $faker->firstNameMale().' '.$faker->lastName().' '.$faker->lastName();

            // Generar datos con localización española
            Customer::create([
                'full_name' => $fullName,
                'email' => $faker->unique()->safeEmail(),
                'email_verified_at' => $faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
                'password' => bcrypt('password'), // Password por defecto
                'subway_card' => $faker->unique()->numerify('##########'),
                'birth_date' => $faker->dateTimeBetween('-65 years', '-16 years')->format('Y-m-d'),
                'gender' => $gender,
                'client_type' => $customerType->name,
                'customer_type_id' => $customerType->id,
                'phone' => $this->generateGuatemalaPhone($faker),
                'address' => $faker->streetAddress().', '.$faker->city(),
                'location' => $faker->randomElement([
                    'Ciudad de Guatemala', 'Mixco', 'Villa Nueva', 'Petapa', 'San Juan Sacatepéquez',
                    'Villa Canales', 'Fraijanes', 'Amatitlán', 'Santa Catarina Pinula', 'San José Pinula',
                    'Chinautla', 'San Pedro Ayampuc', 'Chuarrancho', 'San Raymundo', 'San Pedro Sacatepéquez',
                    'San José del Golfo', 'Palencia',
                ]),
                'nit' => $faker->optional(0.6)->numerify('########-#'),
                'fcm_token' => $faker->optional(0.3)->sha256(),
                'last_login_at' => $faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
                'last_activity_at' => $faker->optional(0.8)->dateTimeBetween('-7 days', 'now'),
                'last_purchase_at' => $faker->optional(0.5)->dateTimeBetween('-90 days', 'now'),
                'points' => $points,
                'points_updated_at' => $faker->optional(0.6)->dateTimeBetween('-60 days', 'now'),
                'timezone' => 'America/Guatemala',
            ]);

            // Mostrar progreso cada 10 clientes
            if ($i % 10 === 0) {
                $this->command->info("   📊 Creados {$i}/50 clientes...");
            }
        }

        $this->command->info('🎉 ¡50 clientes creados exitosamente con datos en español!');
        $this->command->line('');

        // Mostrar estadísticas finales
        $this->showStatistics();
    }

    /**
     * Genera points apropiados según el tipo de cliente
     */
    private function generatePointsForType(CustomerType $type, $faker): int
    {
        return match ($type->name) {
            'regular' => $faker->numberBetween(0, 49),
            'bronze' => $faker->numberBetween(50, 124),
            'silver' => $faker->numberBetween(125, 324),
            'gold' => $faker->numberBetween(325, 999),
            'platinum' => $faker->numberBetween(1000, 5000),
            default => $faker->numberBetween(0, 100),
        };
    }

    /**
     * Genera número de teléfono de Guatemala
     */
    private function generateGuatemalaPhone($faker): string
    {
        // Formatos comunes de Guatemala
        $formats = [
            '####-####',      // 1234-5678
            '#### ####',      // 1234 5678
            '########',       // 12345678
            '+502 ####-####', // +502 1234-5678
            '+502 #### ####', // +502 1234 5678
        ];

        $format = $faker->randomElement($formats);

        // Generar número con códigos de área válidos de Guatemala (2, 3, 4, 5, 6, 7)
        $areaCode = $faker->randomElement(['2', '3', '4', '5', '6', '7']);
        $number = $areaCode.$faker->numerify('#######');

        // Aplicar formato
        if (strpos($format, '+502') !== false) {
            return str_replace('####-####', substr($number, 0, 4).'-'.substr($number, 4, 4), $format);
        } else {
            return $faker->numerify($format);
        }
    }

    /**
     * Muestra estadísticas de los clientes creados
     */
    private function showStatistics(): void
    {
        $this->command->info('📊 Estadísticas de clientes creados:');

        $customersByType = Customer::join('customer_types', 'customers.customer_type_id', '=', 'customer_types.id')
            ->selectRaw('customer_types.display_name, COUNT(*) as count')
            ->groupBy('customer_types.id', 'customer_types.display_name')
            ->orderBy('customer_types.sort_order')
            ->get();

        foreach ($customersByType as $stat) {
            $this->command->line("   • {$stat->display_name}: {$stat->count} clientes");
        }

        $avgPoints = Customer::avg('points');
        $this->command->line('   • Promedio de points: '.number_format($avgPoints, 0).' points');

        $withEmail = Customer::whereNotNull('email_verified_at')->count();
        $this->command->line("   • Con email verificado: {$withEmail} clientes");

        $this->command->line('');
        $this->command->info('✅ Seeder completado exitosamente');
    }
}
