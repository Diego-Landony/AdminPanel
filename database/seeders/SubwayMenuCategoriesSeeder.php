<?php

namespace Database\Seeders;

use App\Models\Menu\Category;
use Illuminate\Database\Seeder;

class SubwayMenuCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📁 Creando categorías del menú de Subway...');

        $categories = [
            [
                'name' => 'Subs',
                'is_active' => true,
                'uses_variants' => true,
                'variant_definitions' => json_encode([
                    [
                        'name' => 'Tamaño',
                        'options' => [
                            ['label' => '15cm (6 pulgadas)', 'value' => '15cm'],
                            ['label' => '30cm (pie largo)', 'value' => '30cm'],
                        ],
                    ],
                ]),
                'sort_order' => 1,
            ],
            [
                'name' => 'Bebidas',
                'is_active' => true,
                'uses_variants' => true,
                'variant_definitions' => json_encode([
                    [
                        'name' => 'Tamaño',
                        'options' => [
                            ['label' => 'Personal', 'value' => 'personal'],
                            ['label' => 'Mediano', 'value' => 'mediano'],
                            ['label' => 'Grande', 'value' => 'grande'],
                        ],
                    ],
                ]),
                'sort_order' => 2,
            ],
            [
                'name' => 'Ensaladas',
                'is_active' => true,
                'uses_variants' => false,
                'variant_definitions' => null,
                'sort_order' => 3,
            ],
            [
                'name' => 'Complementos',
                'is_active' => true,
                'uses_variants' => false,
                'variant_definitions' => null,
                'sort_order' => 4,
            ],
            [
                'name' => 'Postres',
                'is_active' => true,
                'uses_variants' => false,
                'variant_definitions' => null,
                'sort_order' => 5,
            ],
            [
                'name' => 'Desayunos',
                'is_active' => true,
                'uses_variants' => true,
                'variant_definitions' => json_encode([
                    [
                        'name' => 'Tamaño',
                        'options' => [
                            ['label' => '15cm (6 pulgadas)', 'value' => '15cm'],
                            ['label' => '30cm (pie largo)', 'value' => '30cm'],
                        ],
                    ],
                ]),
                'sort_order' => 6,
            ],
            [
                'name' => 'Combos',
                'is_active' => true,
                'uses_variants' => false,
                'variant_definitions' => null,
                'sort_order' => 7,
            ],
        ];

        foreach ($categories as $index => $categoryData) {
            $category = Category::create($categoryData);
            $this->command->line("   ✓ {$category->name}");
        }

        $this->command->info('   ✅ '.count($categories).' categorías creadas');
    }
}
