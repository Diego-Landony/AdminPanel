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
            'slug' => 'combo-2-subs-clasicos',
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
            'slug' => 'combo-familiar',
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
            'slug' => 'combo-personal',
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

        $this->command->info('✅ 3 combos creados exitosamente.');
    }
}
