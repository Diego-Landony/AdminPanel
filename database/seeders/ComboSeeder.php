<?php

namespace Database\Seeders;

use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use Illuminate\Database\Seeder;

class ComboSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener productos activos para los combos
        $products = Product::where('is_active', true)->get();

        if ($products->count() < 2) {
            $this->command->warn('No hay suficientes productos activos para crear combos.');

            return;
        }

        // Combo 1: 2 Subs Clásicos
        $combo1 = Combo::create([
            'name' => 'Combo 2 Subs Clásicos',
            'description' => '2 Subs de 15cm a elección',
            'precio_pickup_capital' => 120.00,
            'precio_domicilio_capital' => 130.00,
            'precio_pickup_interior' => 110.00,
            'precio_domicilio_interior' => 120.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Agregar 2 productos al combo
        $combo1->items()->createMany([
            [
                'product_id' => $products->random()->id,
                'quantity' => 1,
                'label' => 'Sub Principal',
                'sort_order' => 1,
            ],
            [
                'product_id' => $products->random()->id,
                'quantity' => 1,
                'label' => 'Sub Secundario',
                'sort_order' => 2,
            ],
        ]);

        $this->command->info('Combo "2 Subs Clásicos" creado con 2 productos.');

        // Combo 2: Combo Familiar
        $combo2 = Combo::create([
            'name' => 'Combo Familiar',
            'description' => '2 Subs grandes + 2 Bebidas + Papas',
            'precio_pickup_capital' => 220.00,
            'precio_domicilio_capital' => 240.00,
            'precio_pickup_interior' => 200.00,
            'precio_domicilio_interior' => 220.00,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Agregar 4 productos al combo
        $combo2->items()->createMany([
            [
                'product_id' => $products->random()->id,
                'quantity' => 1,
                'label' => 'Sub Grande 1',
                'sort_order' => 1,
            ],
            [
                'product_id' => $products->random()->id,
                'quantity' => 1,
                'label' => 'Sub Grande 2',
                'sort_order' => 2,
            ],
            [
                'product_id' => $products->random()->id,
                'quantity' => 2,
                'label' => 'Bebidas',
                'sort_order' => 3,
            ],
            [
                'product_id' => $products->random()->id,
                'quantity' => 1,
                'label' => 'Papas Fritas',
                'sort_order' => 4,
            ],
        ]);

        $this->command->info('Combo "Familiar" creado con 4 productos.');

        // Combo 3: Combo Personal
        $combo3 = Combo::create([
            'name' => 'Combo Personal',
            'description' => '1 Sub + Bebida',
            'precio_pickup_capital' => 80.00,
            'precio_domicilio_capital' => 90.00,
            'precio_pickup_interior' => 75.00,
            'precio_domicilio_interior' => 85.00,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $combo3->items()->createMany([
            [
                'product_id' => $products->random()->id,
                'quantity' => 1,
                'label' => 'Sub',
                'sort_order' => 1,
            ],
            [
                'product_id' => $products->random()->id,
                'quantity' => 1,
                'label' => 'Bebida',
                'sort_order' => 2,
            ],
        ]);

        $this->command->info('Combo "Personal" creado con 2 productos.');

        // Combo 4: Combo con Grupo de Elección
        $combo4 = Combo::create([
            'name' => 'Combo Flex',
            'description' => 'Elige 1 producto + Bebida',
            'precio_pickup_capital' => 95.00,
            'precio_domicilio_capital' => 105.00,
            'precio_pickup_interior' => 90.00,
            'precio_domicilio_interior' => 100.00,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Crear grupo de elección con 3 opciones
        $choiceGroup = $combo4->items()->create([
            'is_choice_group' => true,
            'choice_label' => 'Elige tu producto principal',
            'product_id' => null,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 1,
        ]);

        // Agregar 3 opciones al grupo
        $productOptions = $products->random(3);
        $sortOrder = 1;
        foreach ($productOptions as $product) {
            $choiceGroup->options()->create([
                'product_id' => $product->id,
                'variant_id' => null,
                'sort_order' => $sortOrder++,
            ]);
        }

        // Agregar bebida como item fijo
        $combo4->items()->create([
            'is_choice_group' => false,
            'product_id' => $products->random()->id,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 2,
        ]);

        $this->command->info('Combo "Flex" creado con 1 grupo de elección (3 opciones) + 1 item fijo.');

        // Combo 5: Combo Mixto con 2 grupos de elección
        $combo5 = Combo::create([
            'name' => 'Combo Personalizable',
            'description' => 'Elige 2 productos de categorías diferentes',
            'precio_pickup_capital' => 150.00,
            'precio_domicilio_capital' => 165.00,
            'precio_pickup_interior' => 145.00,
            'precio_domicilio_interior' => 160.00,
            'is_active' => true,
            'sort_order' => 5,
        ]);

        // Grupo 1: Elige producto principal
        $choiceGroup1 = $combo5->items()->create([
            'is_choice_group' => true,
            'choice_label' => 'Elige tu plato principal',
            'product_id' => null,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 1,
        ]);

        $mainOptions = $products->random(4);
        $sortOrder = 1;
        foreach ($mainOptions as $product) {
            $choiceGroup1->options()->create([
                'product_id' => $product->id,
                'variant_id' => null,
                'sort_order' => $sortOrder++,
            ]);
        }

        // Grupo 2: Elige bebida
        $choiceGroup2 = $combo5->items()->create([
            'is_choice_group' => true,
            'choice_label' => 'Elige tu bebida',
            'product_id' => null,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 2,
        ]);

        $drinkOptions = $products->random(3);
        $sortOrder = 1;
        foreach ($drinkOptions as $product) {
            $choiceGroup2->options()->create([
                'product_id' => $product->id,
                'variant_id' => null,
                'sort_order' => $sortOrder++,
            ]);
        }

        $this->command->info('Combo "Personalizable" creado con 2 grupos de elección (4 opciones + 3 opciones).');

        $this->command->info('✅ 5 combos creados exitosamente (3 fijos + 2 con grupos de elección).');
    }
}
