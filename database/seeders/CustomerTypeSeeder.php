<?php

namespace Database\Seeders;

use App\Models\CustomerType;
use Illuminate\Database\Seeder;

class CustomerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('👥 Creando tipos de cliente...');

        // Datos exactos según la imagen proporcionada
        $customerTypes = [
            [
                'name' => 'Regular',
                'points_required' => 25,
                'multiplier' => 1.00,
                'color' => 'gray',
                'is_active' => true,
            ],
            [
                'name' => 'Bronce',
                'points_required' => 50,
                'multiplier' => 1.25,
                'color' => 'orange',
                'is_active' => true,
            ],
            [
                'name' => 'Plata',
                'points_required' => 125,
                'multiplier' => 1.50,
                'color' => 'slate',
                'is_active' => true,
            ],
            [
                'name' => 'Oro',
                'points_required' => 325,
                'multiplier' => 1.75,
                'color' => 'yellow',
                'is_active' => true,
            ],
            [
                'name' => 'Platino',
                'points_required' => 1000,
                'multiplier' => 2.00,
                'color' => 'purple',
                'is_active' => true,
            ],
        ];

        foreach ($customerTypes as $type) {
            CustomerType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
            $this->command->line("   ✓ {$type['name']} - {$type['points_required']} puntos (x{$type['multiplier']})");
        }

        $this->command->info('   ✅ 5 tipos de cliente creados');
    }
}
