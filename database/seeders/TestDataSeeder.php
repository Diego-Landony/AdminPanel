<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\Role;
use Illuminate\Database\Seeder;

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
                    'is_system' => $roleData['is_system'],
                ]
            );
        }

        $this->command->info('   ✅ '.count($roles).' roles verificados/creados');

        // 2. Crear 25 restaurantes
        $this->command->info('🍽️  Creando 25 restaurantes de prueba...');

        $restaurants = Restaurant::factory(25)->create();
        $this->command->info("   ✅ {$restaurants->count()} restaurantes creados");

        // 3. Mostrar estadísticas finales
        $this->command->line('');
        $this->command->info('📊 Resumen de datos de prueba creados:');
        $this->command->line('   🛡️  Roles: '.Role::count());
        $this->command->line('   🍽️  Restaurantes: '.Restaurant::count().' (25 nuevos + existentes)');

        $this->command->line('');
        $this->command->info('✨ Datos de prueba listos');
    }
}
