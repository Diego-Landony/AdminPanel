<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateCustomersLastPurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Actualizar todos los clientes existentes con fechas de última compra
        Customer::whereNull('last_purchase_at')->get()->each(function ($customer) {
            // Generar una fecha aleatoria de compra entre 60 días atrás y ahora
            $customer->update([
                'last_purchase_at' => fake()->optional(0.8)->dateTimeBetween('-60 days', 'now')
            ]);
        });

        $this->command->info('Updated existing customers with last purchase dates!');
    }
}
