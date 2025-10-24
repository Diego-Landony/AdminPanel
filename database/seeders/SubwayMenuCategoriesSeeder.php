<?php

namespace Database\Seeders;

use App\Models\Menu\Category;
use Illuminate\Database\Seeder;

class SubwayMenuCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📁 Creando categorías del menú de Subway...');

        // Limpiar datos existentes
        Category::query()->delete();

        $categories = [
            [
                'name' => 'Subs',
                'is_active' => true,
                'uses_variants' => true,
                'variant_definitions' => ['15cm', '30cm'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Subway Series',
                'is_active' => true,
                'uses_variants' => false,
                'variant_definitions' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'Wraps',
                'is_active' => true,
                'uses_variants' => false,
                'variant_definitions' => null,
                'sort_order' => 3,
            ],
            [
                'name' => 'Bebidas',
                'is_active' => true,
                'uses_variants' => false,
                'variant_definitions' => null,
                'sort_order' => 4,
            ],
            [
                'name' => 'Ensaladas',
                'is_active' => true,
                'uses_variants' => false,
                'variant_definitions' => null,
                'sort_order' => 5,
            ],
            [
                'name' => 'Pizzas Personales',
                'is_active' => true,
                'uses_variants' => false,
                'variant_definitions' => null,
                'sort_order' => 6,
            ],
            [
                'name' => 'Complementos',
                'is_active' => true,
                'uses_variants' => false,
                'variant_definitions' => null,
                'sort_order' => 7,
            ],
            [
                'name' => 'Postres',
                'is_active' => true,
                'uses_variants' => false,
                'variant_definitions' => null,
                'sort_order' => 8,
            ],
            [
                'name' => 'Desayunos',
                'is_active' => true,
                'uses_variants' => true,
                'variant_definitions' => ['15cm', '30cm'],
                'sort_order' => 9,
            ],
            [
                'name' => 'Combos',
                'is_active' => true,
                'is_combo_category' => true,
                'uses_variants' => false,
                'variant_definitions' => null,
                'sort_order' => 10,
            ],
        ];

        foreach ($categories as $index => $categoryData) {
            $category = Category::create($categoryData);
            $this->command->line("   ✓ {$category->name}");
        }

        $this->command->info('   ✅ '.count($categories).' categorías creadas');
    }
}
