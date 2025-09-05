<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CustomerType;

class CustomerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerTypes = [
            [
                'name' => 'regular',
                'display_name' => 'Regular',
                'points_required' => 0,
                'multiplier' => 1.00,
                'color' => 'gray',
                'sort_order' => 1,
            ],
            [
                'name' => 'bronze',
                'display_name' => 'Bronce',
                'points_required' => 50,
                'multiplier' => 1.25,
                'color' => 'orange',
                'sort_order' => 2,
            ],
            [
                'name' => 'silver',
                'display_name' => 'Plata',
                'points_required' => 125,
                'multiplier' => 1.50,
                'color' => 'slate',
                'sort_order' => 3,
            ],
            [
                'name' => 'gold',
                'display_name' => 'Oro',
                'points_required' => 325,
                'multiplier' => 1.75,
                'color' => 'yellow',
                'sort_order' => 4,
            ],
            [
                'name' => 'platinum',
                'display_name' => 'Platino',
                'points_required' => 1000,
                'multiplier' => 2.00,
                'color' => 'purple',
                'sort_order' => 5,
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
