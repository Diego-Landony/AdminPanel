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
                'description' => 'Elige tu tipo de pan',
                'is_required' => true,
                'allow_multiple' => false,
                'min_selections' => 1,
                'max_selections' => 1,
                'is_active' => true,
                'sort_order' => 1,
                'options' => [
                    ['name' => 'Blanco', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 0],
                    ['name' => 'Italianisimo', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 1],
                    ['name' => 'Orégano parmesano', 'is_extra' => false, 'price_modifier' => 3.00, 'sort_order' => 2],
                    ['name' => 'Avena', 'is_extra' => false, 'price_modifier' => 2.00, 'sort_order' => 3],
                    ['name' => 'Integral', 'is_extra' => false, 'price_modifier' => 2.00, 'sort_order' => 4],
                ],
            ],
            [
                'title' => 'Tipo de tortilla',
                'description' => 'Elige tu tortilla',
                'is_required' => true,
                'allow_multiple' => false,
                'min_selections' => 1,
                'max_selections' => 1,
                'is_active' => true,
                'sort_order' => 2,
                'options' => [
                    ['name' => 'Blanca', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 0],
                    ['name' => 'Integral', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 1],
                ],
            ],
            [
                'title' => 'Queso',
                'description' => 'Elige tu tipo de queso',
                'is_required' => false,
                'allow_multiple' => false,
                'min_selections' => 0,
                'max_selections' => 1,
                'is_active' => true,
                'sort_order' => 3,
                'options' => [
                    ['name' => 'Blanco', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 0],
                    ['name' => 'Amarillo', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 1],
                ],
            ],
            [
                'title' => 'Vegetales',
                'description' => 'Selecciona los vegetales frescos',
                'is_required' => false,
                'allow_multiple' => true,
                'min_selections' => 0,
                'max_selections' => 8,
                'is_active' => true,
                'sort_order' => 4,
                'options' => [
                    ['name' => 'Lechuga', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 0],
                    ['name' => 'Tomate', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 1],
                    ['name' => 'Pepino', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 2],
                    ['name' => 'Pepinillos', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 3],
                    ['name' => 'Cebolla', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 4],
                    ['name' => 'Chile pimiento', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 5],
                    ['name' => 'Aceitunas', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 6],
                    ['name' => 'Jalapeño', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 7],
                ],
            ],
            [
                'title' => 'Especias',
                'description' => 'Elige tus especias',
                'is_required' => false,
                'allow_multiple' => true,
                'min_selections' => 0,
                'max_selections' => 4,
                'is_active' => true,
                'sort_order' => 5,
                'options' => [
                    ['name' => 'Sal', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 0],
                    ['name' => 'Pimienta', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 1],
                    ['name' => 'Aceite', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 2],
                    ['name' => 'Vinagre', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 3],
                ],
            ],
            [
                'title' => 'Aderezos',
                'description' => 'Elige tus aderezos',
                'is_required' => false,
                'allow_multiple' => true,
                'min_selections' => 0,
                'max_selections' => 5,
                'is_active' => true,
                'sort_order' => 6,
                'options' => [
                    ['name' => 'Mayonesa', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 0],
                    ['name' => 'Mostaza', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 1],
                    ['name' => 'Mostaza Miel', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 2],
                    ['name' => 'Ranch', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 3],
                    ['name' => 'Chipotle', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 4],
                    ['name' => 'BBQ', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 5],
                    ['name' => 'Vinagreta Balsámica', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 6],
                    ['name' => 'Salsa Picante', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 7],
                    ['name' => 'Aceite y Vinagre', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 8],
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
                'sort_order' => 7,
                'options' => [
                    ['name' => 'Tostado', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 1],
                    ['name' => 'Sin Tostar', 'is_extra' => false, 'price_modifier' => 0.00, 'sort_order' => 2],
                ],
            ],
            [
                'title' => 'Extras',
                'description' => '¿Deseas agregar algún extra?',
                'is_required' => false,
                'allow_multiple' => true,
                'min_selections' => 0,
                'max_selections' => 4,
                'is_active' => true,
                'sort_order' => 8,
                'options' => [
                    ['name' => 'Doble de carne', 'is_extra' => true, 'price_modifier' => 16.00, 'sort_order' => 0],
                    ['name' => 'Extra de queso', 'is_extra' => true, 'price_modifier' => 11.00, 'sort_order' => 1],
                    ['name' => 'Aguate', 'is_extra' => true, 'price_modifier' => 11.00, 'sort_order' => 2],
                    ['name' => 'Tocino', 'is_extra' => true, 'price_modifier' => 11.00, 'sort_order' => 3],
                    ['name' => 'Hongos', 'is_extra' => true, 'price_modifier' => 11.00, 'sort_order' => 4],
                    ['name' => 'Flijol', 'is_extra' => true, 'price_modifier' => 11.00, 'sort_order' => 5],
                    ['name' => 'Queso rayado', 'is_extra' => true, 'price_modifier' => 11.00, 'sort_order' => 6],
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
