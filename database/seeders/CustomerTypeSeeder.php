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
        $customerTypes = [
            [
                'name' => 'Regular',
                'points_required' => 0,
                'multiplier' => 1.00,
                'color' => 'gray',
            ],
            [
                'name' => 'Bronce',
                'points_required' => 50,
                'multiplier' => 1.25,
                'color' => 'orange',
            ],
            [
                'name' => 'Plata',
                'points_required' => 125,
                'multiplier' => 1.50,
                'color' => 'slate',
            ],
            [
                'name' => 'Oro',
                'points_required' => 325,
                'multiplier' => 1.75,
                'color' => 'yellow',
            ],
            [
                'name' => 'Platino',
                'points_required' => 1000,
                'multiplier' => 2.00,
                'color' => 'purple',
            ],
        ];

        foreach ($customerTypes as $type) {
            CustomerType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
