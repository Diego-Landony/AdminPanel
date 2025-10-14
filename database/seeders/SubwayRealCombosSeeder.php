<?php

namespace Database\Seeders;

use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use Illuminate\Database\Seeder;

class SubwayRealCombosSeeder extends Seeder
{
    /**
     * Seeder de combos reales de Subway Guatemala
     * Basado en información oficial del menú de Subway Guatemala
     */
    public function run(): void
    {
        $this->command->info('🎁 Creando combos reales de Subway Guatemala...');

        // Crear categoría especial para combos
        $comboCategory = Category::firstOrCreate(
            ['name' => 'Combos'],
            [
                'is_active' => true,
                'uses_variants' => false,
                'is_combo_category' => true,
                'variant_definitions' => null,
                'sort_order' => 1,
            ]
        );

        $this->command->line('   ✓ Categoría "Combos" creada/actualizada');

        $this->createComboPersonal($comboCategory);
        $this->createComboDoble($comboCategory);
        $this->createComboFamiliar($comboCategory);
        $this->createComboDesayuno($comboCategory);
        $this->createComboEconomico($comboCategory);

        $this->command->info('   ✅ 5 combos reales de Subway Guatemala creados exitosamente');
    }

    private function createComboPersonal(Category $comboCategory): void
    {
        // Combo Personal: Sub 15cm + Bebida + Complemento
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Personal',
            'description' => 'Sub de 15cm a elección + Bebida mediana + Papas o Galleta',
            'precio_pickup_capital' => 48.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 50.00,
            'precio_domicilio_interior' => 57.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Sub 15cm (Italian BMT)
        $italianBMT = Product::where('name', 'Italian B.M.T.')->first();
        if ($italianBMT) {
            $variant15cm = $italianBMT->variants()->where('size', '15cm')->first();
            if ($variant15cm) {
                $combo->items()->create([
                    'product_id' => $italianBMT->id,
                    'variant_id' => $variant15cm->id,
                    'quantity' => 1,
                    'sort_order' => 1,
                ]);
            }
        }

        // Bebida mediana (Coca-Cola)
        $cocaCola = Product::where('name', 'Coca-Cola')->first();
        if ($cocaCola) {
            $variantMediano = $cocaCola->variants()->where('size', 'mediano')->first();
            if ($variantMediano) {
                $combo->items()->create([
                    'product_id' => $cocaCola->id,
                    'variant_id' => $variantMediano->id,
                    'quantity' => 1,
                    'sort_order' => 2,
                ]);
            }
        }

        // Papas Lays
        $papas = Product::where('name', 'Papas Lays')->first();
        if ($papas) {
            $combo->items()->create([
                'product_id' => $papas->id,
                'variant_id' => null,
                'quantity' => 1,
                'sort_order' => 3,
            ]);
        }

        $this->command->line('      ✓ Combo Personal (Q48 pickup / Q55 delivery)');
    }

    private function createComboDoble(Category $comboCategory): void
    {
        // Combo Doble: 2 Subs 15cm + 2 Bebidas
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Doble',
            'description' => '2 Subs de 15cm a elección + 2 Bebidas medianas',
            'precio_pickup_capital' => 75.00,
            'precio_domicilio_capital' => 85.00,
            'precio_pickup_interior' => 78.00,
            'precio_domicilio_interior' => 88.00,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Sub 1: Italian BMT 15cm
        $italianBMT = Product::where('name', 'Italian B.M.T.')->first();
        if ($italianBMT) {
            $variant15cm = $italianBMT->variants()->where('size', '15cm')->first();
            if ($variant15cm) {
                $combo->items()->create([
                    'product_id' => $italianBMT->id,
                    'variant_id' => $variant15cm->id,
                    'quantity' => 1,
                    'sort_order' => 1,
                ]);
            }
        }

        // Sub 2: Pollo Teriyaki 15cm
        $polloTeriyaki = Product::where('name', 'Pollo Teriyaki')->first();
        if ($polloTeriyaki) {
            $variant15cm = $polloTeriyaki->variants()->where('size', '15cm')->first();
            if ($variant15cm) {
                $combo->items()->create([
                    'product_id' => $polloTeriyaki->id,
                    'variant_id' => $variant15cm->id,
                    'quantity' => 1,
                    'sort_order' => 2,
                ]);
            }
        }

        // 2 Bebidas medianas
        $cocaCola = Product::where('name', 'Coca-Cola')->first();
        if ($cocaCola) {
            $variantMediano = $cocaCola->variants()->where('size', 'mediano')->first();
            if ($variantMediano) {
                $combo->items()->create([
                    'product_id' => $cocaCola->id,
                    'variant_id' => $variantMediano->id,
                    'quantity' => 2,
                    'sort_order' => 3,
                ]);
            }
        }

        $this->command->line('      ✓ Combo Doble (Q75 pickup / Q85 delivery)');
    }

    private function createComboFamiliar(Category $comboCategory): void
    {
        // Combo Familiar: 2 Subs 30cm + 2 Bebidas grandes + 2 Papas
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Familiar',
            'description' => '2 Subs de 30cm a elección + 2 Bebidas grandes + 2 Papas',
            'precio_pickup_capital' => 145.00,
            'precio_domicilio_capital' => 160.00,
            'precio_pickup_interior' => 150.00,
            'precio_domicilio_interior' => 165.00,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Sub 1: Subway Club 30cm
        $subwayClub = Product::where('name', 'Subway Club')->first();
        if ($subwayClub) {
            $variant30cm = $subwayClub->variants()->where('size', '30cm')->first();
            if ($variant30cm) {
                $combo->items()->create([
                    'product_id' => $subwayClub->id,
                    'variant_id' => $variant30cm->id,
                    'quantity' => 1,
                    'sort_order' => 1,
                ]);
            }
        }

        // Sub 2: Steak & Cheese 30cm
        $steakCheese = Product::where('name', 'Steak & Cheese')->first();
        if ($steakCheese) {
            $variant30cm = $steakCheese->variants()->where('size', '30cm')->first();
            if ($variant30cm) {
                $combo->items()->create([
                    'product_id' => $steakCheese->id,
                    'variant_id' => $variant30cm->id,
                    'quantity' => 1,
                    'sort_order' => 2,
                ]);
            }
        }

        // 2 Bebidas grandes
        $cocaCola = Product::where('name', 'Coca-Cola')->first();
        if ($cocaCola) {
            $variantGrande = $cocaCola->variants()->where('size', 'grande')->first();
            if ($variantGrande) {
                $combo->items()->create([
                    'product_id' => $cocaCola->id,
                    'variant_id' => $variantGrande->id,
                    'quantity' => 2,
                    'sort_order' => 3,
                ]);
            }
        }

        // 2 Papas
        $papas = Product::where('name', 'Papas Lays')->first();
        if ($papas) {
            $combo->items()->create([
                'product_id' => $papas->id,
                'variant_id' => null,
                'quantity' => 2,
                'sort_order' => 4,
            ]);
        }

        $this->command->line('      ✓ Combo Familiar (Q145 pickup / Q160 delivery)');
    }

    private function createComboDesayuno(Category $comboCategory): void
    {
        // Combo Desayuno: Desayuno 15cm + Bebida personal + Muffin
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Desayuno',
            'description' => 'Desayuno de 15cm a elección + Bebida personal + Muffin o Galleta',
            'precio_pickup_capital' => 42.00,
            'precio_domicilio_capital' => 48.00,
            'precio_pickup_interior' => 44.00,
            'precio_domicilio_interior' => 50.00,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Desayuno con Tocino y Huevo 15cm
        $desayuno = Product::where('name', 'Desayuno con Tocino y Huevo')->first();
        if ($desayuno) {
            $variant15cm = $desayuno->variants()->where('size', '15cm')->first();
            if ($variant15cm) {
                $combo->items()->create([
                    'product_id' => $desayuno->id,
                    'variant_id' => $variant15cm->id,
                    'quantity' => 1,
                    'sort_order' => 1,
                ]);
            }
        }

        // Bebida personal
        $cocaCola = Product::where('name', 'Coca-Cola')->first();
        if ($cocaCola) {
            $variantPersonal = $cocaCola->variants()->where('size', 'personal')->first();
            if ($variantPersonal) {
                $combo->items()->create([
                    'product_id' => $cocaCola->id,
                    'variant_id' => $variantPersonal->id,
                    'quantity' => 1,
                    'sort_order' => 2,
                ]);
            }
        }

        // Muffin de Arándanos
        $muffin = Product::where('name', 'Muffin de Arándanos')->first();
        if ($muffin) {
            $combo->items()->create([
                'product_id' => $muffin->id,
                'variant_id' => null,
                'quantity' => 1,
                'sort_order' => 3,
            ]);
        }

        $this->command->line('      ✓ Combo Desayuno (Q42 pickup / Q48 delivery)');
    }

    private function createComboEconomico(Category $comboCategory): void
    {
        // Combo Económico: Sub 15cm económico + Bebida personal
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Económico',
            'description' => 'Sub de 15cm seleccionado + Bebida personal',
            'precio_pickup_capital' => 38.00,
            'precio_domicilio_capital' => 43.00,
            'precio_pickup_interior' => 40.00,
            'precio_domicilio_interior' => 45.00,
            'is_active' => true,
            'sort_order' => 5,
        ]);

        // Sub económico: Jamón 15cm
        $jamon = Product::where('name', 'Jamón')->first();
        if ($jamon) {
            $variant15cm = $jamon->variants()->where('size', '15cm')->first();
            if ($variant15cm) {
                $combo->items()->create([
                    'product_id' => $jamon->id,
                    'variant_id' => $variant15cm->id,
                    'quantity' => 1,
                    'sort_order' => 1,
                ]);
            }
        }

        // Bebida personal
        $cocaCola = Product::where('name', 'Coca-Cola')->first();
        if ($cocaCola) {
            $variantPersonal = $cocaCola->variants()->where('size', 'personal')->first();
            if ($variantPersonal) {
                $combo->items()->create([
                    'product_id' => $cocaCola->id,
                    'variant_id' => $variantPersonal->id,
                    'quantity' => 1,
                    'sort_order' => 2,
                ]);
            }
        }

        $this->command->line('      ✓ Combo Económico (Q38 pickup / Q43 delivery)');
    }
}
