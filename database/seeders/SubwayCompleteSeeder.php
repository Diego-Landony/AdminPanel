<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SubwayCompleteSeeder extends Seeder
{
    /**
     * Seeder maestro para poblar toda la base de datos de Subway Guatemala
     * con datos realistas del menú, productos, categorías, secciones y promociones.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->info('  🥖 SUBWAY GUATEMALA - POBLADO COMPLETO DE BASE DE DATOS');
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->info('');

        $startTime = microtime(true);

        // 1. Categorías del menú (Subs, Bebidas, Ensaladas, etc.)
        $this->command->info('PASO 1/8: Creando categorías del menú...');
        $this->call(SubwayMenuCategoriesSeeder::class);
        $this->command->info('');

        // 2. Secciones de personalización (Panes, Quesos, Vegetales, Salsas)
        $this->command->info('PASO 2/8: Creando secciones de personalización...');
        $this->call(SubwayMenuSectionsSeeder::class);
        $this->command->info('');

        // 3. Productos con variantes (Subs 15cm/30cm, Bebidas, Ensaladas, etc.)
        $this->command->info('PASO 3/8: Creando productos y variantes...');
        $this->call(SubwayMenuProductsSeeder::class);
        $this->command->info('');

        // 4. Promociones (2x1, Sub del Día, Descuentos)
        $this->command->info('PASO 4/8: Creando promociones y Sub del Día...');
        $this->call(SubwayPromotionsSeeder::class);
        $this->command->info('');

        // 5. Combos reales de Subway Guatemala
        $this->command->info('PASO 5/8: Creando combos reales de Subway Guatemala...');
        $this->call(SubwayRealCombosSeeder::class);
        $this->command->info('');

        // 6. Tipos de cliente
        $this->command->info('PASO 6/9: Creando tipos de cliente...');
        $this->call(CustomerTypeSeeder::class);
        $this->command->info('');

        // 7. Restaurantes de Guatemala con ubicaciones reales
        $this->command->info('PASO 7/9: Creando restaurantes Subway con ubicaciones reales...');
        $this->call(RestaurantSeeder::class);
        $this->command->info('');

        // 8. Clientes reales con datos completos
        $this->command->info('PASO 8/9: Creando clientes realistas con todos los niveles...');
        $this->call(RealCustomersSeeder::class);
        $this->command->info('');

        // 9. Clientes de prueba adicionales
        $this->command->info('PASO 9/9: Creando clientes de prueba adicionales...');
        $this->call(FakeDataSeeder::class);
        $this->command->info('');

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('   ✅ BASE DE DATOS POBLADA EXITOSAMENTE');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->line("   ⏱️  Tiempo de ejecución: {$executionTime} segundos");
        $this->command->info('');
        $this->command->info('📊 Resumen de datos creados:');
        $this->command->line('   • Categorías de menú con variantes configuradas');
        $this->command->line('   • Secciones de personalización (panes, quesos, vegetales, salsas)');
        $this->command->line('   • Productos Subway con precios diferenciados por ubicación');
        $this->command->line('   • Variantes de productos (tamaños 15cm/30cm, tamaños de bebidas)');
        $this->command->line('   • Promociones activas (2x1, Sub del Día, descuentos)');
        $this->command->line('   • 5 Combos reales con variantes correctas (Personal, Doble, Familiar, Desayuno, Económico)');
        $this->command->line('   • 5 Tipos de cliente (Regular, Bronce, Plata, Oro, Platino)');
        $this->command->line('   • 10 Restaurantes Subway en Guatemala con ubicaciones reales');
        $this->command->line('   • 50 Clientes realistas distribuidos en todos los niveles con datos completos');
        $this->command->info('');
        $this->command->info('🔐 Credenciales de acceso:');
        $this->command->line('   Email: admin@admin.com');
        $this->command->line('   Contraseña: admin');
        $this->command->info('');
    }
}
