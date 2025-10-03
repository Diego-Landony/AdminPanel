<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear 50 clientes de prueba
        Customer::factory()->count(50)->create();

        // Crear un cliente de prueba específico
        Customer::factory()->create([
            'full_name' => 'Cliente de Prueba',
            'email' => 'cliente@test.com',
            'subway_card' => '1234567890',
            'client_type' => 'premium',
        ]);
    }
}
