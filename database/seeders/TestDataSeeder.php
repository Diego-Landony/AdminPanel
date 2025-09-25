<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Restaurant;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds para generar datos de prueba.
     */
    public function run(): void
    {
        $this->command->info('🧪 Generando datos de prueba para paginación...');

        // 1. Crear roles adicionales si no existen
        $this->command->info('🛡️  Creando roles de prueba...');

        $roles = [
            ['name' => 'admin', 'description' => 'Administrador del sistema con acceso completo', 'is_system' => true],
            ['name' => 'editor', 'description' => 'Editor con permisos de escritura limitados', 'is_system' => false],
            ['name' => 'viewer', 'description' => 'Visualizador con permisos de solo lectura', 'is_system' => false],
            ['name' => 'manager', 'description' => 'Gerente con permisos de gestión', 'is_system' => false],
            ['name' => 'supervisor', 'description' => 'Supervisor con permisos intermedios', 'is_system' => false],
            ['name' => 'operator', 'description' => 'Operador con permisos básicos', 'is_system' => false],
            ['name' => 'guest', 'description' => 'Invitado con permisos mínimos', 'is_system' => false],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name']],
                [
                    'description' => $roleData['description'],
                    'is_system' => $roleData['is_system']
                ]
            );
        }

        $this->command->info('   ✅ ' . count($roles) . ' roles verificados/creados');

        // 2. Crear 25 usuarios con diferentes estados
        $this->command->info('👥 Creando 25 usuarios de prueba...');

        $allRoles = Role::all();
        $createdUsers = 0;

        for ($i = 1; $i <= 25; $i++) {
            $email = "test{$i}@pagination.com";

            // Solo crear si el email no existe
            if (!User::where('email', $email)->exists()) {
                $user = User::factory()->create([
                    'name' => "Usuario de Prueba {$i}",
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'email_verified_at' => $i % 3 === 0 ? null : now(), // Algunos no verificados
                    'last_activity_at' => $this->getRandomActivityTime($i),
                ]);

                // Asignar rol aleatorio a cada usuario
                $randomRole = $allRoles->random();
                $user->roles()->attach($randomRole->id);
                $createdUsers++;
            }
        }

        $this->command->info("   ✅ {$createdUsers} usuarios creados");

        // 3. Crear 25 restaurantes
        $this->command->info('🍽️  Creando 25 restaurantes de prueba...');

        $restaurants = Restaurant::factory(25)->create();
        $this->command->info("   ✅ {$restaurants->count()} restaurantes creados");

        // 4. Crear 25 clientes
        $this->command->info('👤 Creando 25 clientes de prueba...');

        $customerTypes = \App\Models\CustomerType::all();
        $customersCreated = 0;

        if ($customerTypes->isNotEmpty()) {
            for ($i = 1; $i <= 25; $i++) {
                Customer::factory()->create([
                    'customer_type_id' => $customerTypes->random()->id,
                ]);
                $customersCreated++;
            }
        } else {
            $this->command->warn('   ⚠️  No hay tipos de cliente disponibles. Se omite la creación de clientes.');
        }

        $this->command->info("   ✅ {$customersCreated} clientes creados");

        // 5. Mostrar estadísticas finales
        $this->command->line('');
        $this->command->info('📊 Resumen de datos de prueba creados:');
        $this->command->line('   👥 Usuarios: ' . User::count() . ' (25 nuevos + existentes)');
        $this->command->line('   🛡️  Roles: ' . Role::count());
        $this->command->line('   🍽️  Restaurantes: ' . Restaurant::count() . ' (25 nuevos + existentes)');
        $this->command->line('   👤 Clientes: ' . Customer::count() . ' (25 nuevos + existentes)');

        $this->command->line('');
        $this->command->info('🎯 Para probar la paginación:');
        $this->command->line('   • Usuarios: Configurar 10 elementos por página');
        $this->command->line('   • Roles: Configurar 5 elementos por página');
        $this->command->line('   • Restaurantes: Configurar 8 elementos por página');
        $this->command->line('   • Clientes: Configurar 12 elementos por página');

        $this->command->line('');
        $this->command->info('✨ Datos de prueba listos para validar la paginación');
    }

    /**
     * Genera un timestamp aleatorio de actividad basado en diferentes patrones
     */
    private function getRandomActivityTime(int $userNumber): ?\Carbon\Carbon
    {
        $result = match($userNumber % 4) {
            0 => now()->subMinutes(fake()->numberBetween(1, 5)), // Online
            1 => now()->subMinutes(fake()->numberBetween(6, 60)), // Recent
            2 => now()->subDays(fake()->numberBetween(1, 30)), // Offline
            default => fake()->optional(0.8)->dateTimeBetween('-30 days', 'now'), // Random or null
        };

        return $result ? \Carbon\Carbon::instance($result) : null;
    }
}