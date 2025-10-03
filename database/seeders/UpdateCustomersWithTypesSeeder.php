<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class UpdateCustomersWithTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Iniciando actualización de tipos de clientes existentes...');

        // Obtener todos los clientes que no tienen tipo asignado
        $customersWithoutType = Customer::whereNull('customer_type_id')->get();

        $this->command->info("Encontrados {$customersWithoutType->count()} clientes sin tipo asignado.");

        $updatedCount = 0;

        foreach ($customersWithoutType as $customer) {
            // Actualizar el tipo de cliente basado en puntos
            $customer->updateCustomerType();

            if ($customer->customer_type_id) {
                $updatedCount++;
            }
        }

        $this->command->info("Actualización completada. {$updatedCount} clientes actualizados con su tipo correspondiente.");

        // Mostrar estadísticas
        $stats = Customer::with('customerType')
            ->get()
            ->groupBy(function ($customer) {
                return $customer->customerType ? $customer->customerType->display_name : 'Sin Tipo';
            })
            ->map->count()
            ->sortByDesc(function ($count) {
                return $count;
            });

        $this->command->info("\nEstadísticas de tipos de clientes:");
        foreach ($stats as $type => $count) {
            $this->command->info("- {$type}: {$count} clientes");
        }
    }
}
