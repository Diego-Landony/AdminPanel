<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seeder de clientes deshabilitado
        $this->command->info('ℹ️  CustomerSeeder está deshabilitado - no se crean clientes de prueba');
    }
}
