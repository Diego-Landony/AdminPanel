<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerDevice;
use App\Models\CustomerNit;
use App\Models\CustomerType;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear 10 clientes de prueba con direcciones y dispositivos
        Customer::factory()
            ->count(10)
            ->create()
            ->each(function ($customer) {
                // Crear entre 0 y 2 direcciones para cada cliente
                $addressCount = rand(0, 2);

                if ($addressCount > 0) {
                    CustomerAddress::factory()
                        ->count($addressCount)
                        ->create([
                            'customer_id' => $customer->id,
                            'is_default' => false,
                        ]);

                    // Marcar la primera dirección como predeterminada
                    $customer->addresses()->first()->update(['is_default' => true]);
                }

                // Crear 1-2 dispositivos para cada cliente
                $deviceCount = rand(1, 2);

                for ($i = 0; $i < $deviceCount; $i++) {
                    CustomerDevice::factory()
                        ->active()
                        ->create([
                            'customer_id' => $customer->id,
                        ]);
                }

                // Crear 0-1 NITs para cada cliente
                if (rand(0, 1) === 1) {
                    CustomerNit::factory()->personal()->default()->create([
                        'customer_id' => $customer->id,
                    ]);
                }
            });

        // Crear un cliente de prueba específico con email conocido
        $testCustomer = Customer::factory()->create([
            'first_name' => 'Cliente',
            'last_name' => 'Demo',
            'email' => 'demo@subwaygt.com',
            'subway_card' => '80000000001',
            'customer_type_id' => CustomerType::where('name', 'gold')->first()?->id ?? CustomerType::first()?->id,
            'points' => 500,
            'email_offers_enabled' => true,
        ]);

        // Crear direcciones para el cliente demo
        CustomerAddress::factory()->create([
            'customer_id' => $testCustomer->id,
            'label' => 'Casa',
            'address_line' => 'Zona 10, Ciudad de Guatemala',
            'is_default' => true,
        ]);

        CustomerAddress::factory()->create([
            'customer_id' => $testCustomer->id,
            'label' => 'Oficina',
            'address_line' => 'Zona 4, Edificio Campus Tec',
            'is_default' => false,
        ]);

        // Crear dispositivo para el cliente demo
        CustomerDevice::factory()->active()->create([
            'customer_id' => $testCustomer->id,
            'device_name' => 'iPhone Demo',
        ]);

        // Crear NIT para el cliente demo
        CustomerNit::factory()->personal()->default()->create([
            'customer_id' => $testCustomer->id,
            'nit' => '12345678-9',
        ]);
    }
}
