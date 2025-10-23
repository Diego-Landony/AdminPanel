<?php

namespace Database\Seeders;

use App\Models\Menu\Section;
use App\Models\Menu\SectionOption;
use Illuminate\Database\Seeder;

class SubwayMenuSectionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🥖 Creando secciones y opciones de personalización...');

        // Limpiar datos existentes
        Section::query()->delete();

        $sections = [
            [
                'title' => 'Tipo de Pan',
                'description' => 'Elige el pan para tu Sub',
                'is_required' => true,
                'allow_multiple' => false,
                'min_selections' => 1,
                'max_selections' => 1,
                'is_active' => true,
                'sort_order' => 1,
                'options' => [
                    ['name' => 'Italiano Blanco', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 1],
                    ['name' => 'Trigo Integral', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 2],
                    ['name' => 'Italiano con Hierbas y Queso', 'is_extra' => false, 'price_modifier' => 3.00, 'sort_order' => 3],
                    ['name' => 'Miel y Avena', 'is_extra' => false, 'price_modifier' => 2.00, 'sort_order' => 4],
                    ['name' => 'Multigrano', 'is_extra' => false, 'price_modifier' => 2.00, 'sort_order' => 5],
                ],
            ],
            [
                'title' => 'Queso',
                'description' => 'Agrega queso a tu Sub',
                'is_required' => false,
                'allow_multiple' => false,
                'min_selections' => 0,
                'max_selections' => 1,
                'is_active' => true,
                'sort_order' => 2,
                'options' => [
                    ['name' => 'Americano', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 1],
                    ['name' => 'Cheddar', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 2],
                    ['name' => 'Suizo', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 3],
                    ['name' => 'Pepper Jack', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 4],
                    ['name' => 'Provolone', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 5],
                ],
            ],
            [
                'title' => 'Vegetales',
                'description' => 'Selecciona los vegetales frescos',
                'is_required' => false,
                'allow_multiple' => true,
                'min_selections' => 0,
                'max_selections' => 20,
                'is_active' => true,
                'sort_order' => 3,
                'options' => [
                    ['name' => 'Lechuga', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 1],
                    ['name' => 'Tomate', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 2],
                    ['name' => 'Pepino', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 3],
                    ['name' => 'Cebolla', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 4],
                    ['name' => 'Pimientos', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 5],
                    ['name' => 'Aceitunas', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 6],
                    ['name' => 'Jalapeños', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 7],
                    ['name' => 'Espinaca', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 8],
                    ['name' => 'Aguacate', 'is_extra' => true, 'price_modifier' => 5.00, 'sort_order' => 9],
                ],
            ],
            [
                'title' => 'Salsas',
                'description' => 'Agrega tus salsas favoritas',
                'is_required' => false,
                'allow_multiple' => true,
                'min_selections' => 0,
                'max_selections' => 10,
                'is_active' => true,
                'sort_order' => 4,
                'options' => [
                    ['name' => 'Mayonesa', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 1],
                    ['name' => 'Mostaza', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 2],
                    ['name' => 'Mostaza Miel', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 3],
                    ['name' => 'Ranch', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 4],
                    ['name' => 'Chipotle', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 5],
                    ['name' => 'BBQ', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 6],
                    ['name' => 'Vinagreta Balsámica', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 7],
                    ['name' => 'Salsa Picante', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 8],
                    ['name' => 'Aceite y Vinagre', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 9],
                ],
            ],
            [
                'title' => 'Preparación',
                'description' => '¿Cómo deseas tu Sub?',
                'is_required' => true,
                'allow_multiple' => false,
                'min_selections' => 1,
                'max_selections' => 1,
                'is_active' => true,
                'sort_order' => 5,
                'options' => [
                    ['name' => 'Tostado', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 1],
                    ['name' => 'Sin Tostar', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 2],
                ],
            ],
            [
                'title' => 'Extras de Proteína',
                'description' => 'Agrega proteína extra',
                'is_required' => false,
                'allow_multiple' => true,
                'min_selections' => 0,
                'max_selections' => 5,
                'is_active' => true,
                'sort_order' => 6,
                'options' => [
                    ['name' => 'Doble Carne', 'is_extra' => true, 'price_modifier' => 15.00, 'sort_order' => 1],
                    ['name' => 'Tocino Extra', 'is_extra' => true, 'price_modifier' => 8.00, 'sort_order' => 2],
                    ['name' => 'Huevo Extra', 'is_extra' => true, 'price_modifier' => 5.00, 'sort_order' => 3],
                ],
            ],
        ];

        foreach ($sections as $sectionData) {
            $options = $sectionData['options'];
            unset($sectionData['options']);

            $section = Section::create($sectionData);
            $this->command->line("   ✓ {$section->title}");

            foreach ($options as $optionData) {
                $optionData['section_id'] = $section->id;
                SectionOption::create($optionData);
            }

            $this->command->line('      → '.count($options).' opciones creadas');
        }

        $this->command->info('   ✅ '.count($sections).' secciones creadas');
    }
}
