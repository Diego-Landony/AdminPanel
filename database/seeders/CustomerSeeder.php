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
        // Crear 50 clientes de prueba con direcciones y dispositivos
        Customer::factory()
            ->count(50)
            ->create()
            ->each(function ($customer) {
                // Crear entre 0 y 3 direcciones para cada cliente
                $addressCount = rand(0, 3);

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

                // Crear entre 1 y 3 dispositivos para cada cliente
                $deviceCount = rand(1, 3);

                for ($i = 0; $i < $deviceCount; $i++) {
                    CustomerDevice::factory()
                        ->active()
                        ->create([
                            'customer_id' => $customer->id,
                        ]);
                }

                // Crear entre 0 y 2 NITs para cada cliente
                $nitCount = rand(0, 2);

                if ($nitCount > 0) {
                    $nitTypes = ['personal', 'company', 'other'];

                    for ($i = 0; $i < $nitCount; $i++) {
                        $nitType = $nitTypes[$i % count($nitTypes)];

                        CustomerNit::factory()
                            ->state(['nit_type' => $nitType])
                            ->create([
                                'customer_id' => $customer->id,
                                'is_default' => false,
                            ]);
                    }

                    // Marcar el primer NIT como predeterminado
                    $customer->nits()->first()->update(['is_default' => true]);
                }
            });

        // Crear un cliente de prueba específico
        $testCustomer = Customer::factory()->create([
            'first_name' => 'Cliente',
            'last_name' => 'de Prueba',
            'email' => 'cliente@test.com',
            'subway_card' => '1234567890',
            'customer_type_id' => CustomerType::where('name', 'gold')->first()?->id ?? CustomerType::first()?->id,
        ]);

        // Crear direcciones para el cliente de prueba
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

        // Crear dispositivos para el cliente de prueba
        CustomerDevice::factory()->active()->create([
            'customer_id' => $testCustomer->id,
            'device_name' => 'iPhone de Prueba',
        ]);

        CustomerDevice::factory()->active()->create([
            'customer_id' => $testCustomer->id,
            'device_name' => 'Samsung de Prueba',
        ]);

        CustomerDevice::factory()->active()->create([
            'customer_id' => $testCustomer->id,
            'device_name' => 'Navegador Chrome',
        ]);

        // Crear NITs para el cliente de prueba
        CustomerNit::factory()->personal()->default()->create([
            'customer_id' => $testCustomer->id,
            'nit' => '12345678-9',
        ]);

        CustomerNit::factory()->company()->create([
            'customer_id' => $testCustomer->id,
            'nit' => '98765432-1',
            'business_name' => 'Subway Guatemala S.A.',
            'is_default' => false,
        ]);
    }
}
