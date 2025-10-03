<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class TestPaginationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:pagination';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica que la paginación funcione correctamente con los datos de prueba';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('🧪 Verificando funcionamiento de la paginación...');
        $this->newLine();

        // Test Users pagination
        $this->info('👥 Usuarios:');
        $users = User::paginate(10);
        $this->line("   Total: {$users->total()} registros");
        $this->line("   Páginas: {$users->lastPage()} páginas");
        $this->line("   Por página: {$users->perPage()} elementos");
        $this->line("   Página actual: {$users->currentPage()}");

        if ($users->lastPage() > 1) {
            $this->info('   ✅ Paginación activa para usuarios');
        } else {
            $this->warn('   ⚠️  Paginación no necesaria (solo 1 página)');
        }

        $this->newLine();

        // Test Roles pagination
        $this->info('🛡️  Roles:');
        $roles = Role::paginate(5);
        $this->line("   Total: {$roles->total()} registros");
        $this->line("   Páginas: {$roles->lastPage()} páginas");
        $this->line("   Por página: {$roles->perPage()} elementos");

        if ($roles->lastPage() > 1) {
            $this->info('   ✅ Paginación activa para roles');
        } else {
            $this->warn('   ⚠️  Paginación no necesaria (solo 1 página)');
        }

        $this->newLine();

        // Test Restaurants pagination
        $this->info('🍽️  Restaurantes:');
        $restaurants = Restaurant::paginate(8);
        $this->line("   Total: {$restaurants->total()} registros");
        $this->line("   Páginas: {$restaurants->lastPage()} páginas");
        $this->line("   Por página: {$restaurants->perPage()} elementos");

        if ($restaurants->lastPage() > 1) {
            $this->info('   ✅ Paginación activa para restaurantes');
        } else {
            $this->warn('   ⚠️  Paginación no necesaria (solo 1 página)');
        }

        $this->newLine();

        // Test Customers pagination
        $this->info('👤 Clientes:');
        $customers = Customer::paginate(12);
        $this->line("   Total: {$customers->total()} registros");
        $this->line("   Páginas: {$customers->lastPage()} páginas");
        $this->line("   Por página: {$customers->perPage()} elementos");

        if ($customers->lastPage() > 1) {
            $this->info('   ✅ Paginación activa para clientes');
        } else {
            $this->warn('   ⚠️  Paginación no necesaria (solo 1 página)');
        }

        $this->newLine();
        $this->info('🎯 Prueba Manual:');
        $this->line('   1. Inicia el servidor: php artisan serve');
        $this->line('   2. Visita /users - Deberías ver 3 páginas');
        $this->line('   3. Visita /roles - Deberías ver 2 páginas');
        $this->line('   4. Visita /restaurants - Deberías ver 10+ páginas');
        $this->line('   5. Visita /customers - Deberías ver 3 páginas');

        $this->newLine();
        $this->info('✨ Verificación de paginación completada');
    }
}
