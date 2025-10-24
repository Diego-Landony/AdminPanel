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

        // Limpiar datos existentes (respetando foreign keys - force delete por SoftDeletes)
        Combo::query()->forceDelete();

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

    /**
     * Obtiene la variante de un producto por índice (ordenado por sort_order)
     *
     * @param Product $product
     * @param int $index 0 = primera variante (normalmente la más pequeña), 1 = segunda variante, etc.
     * @return \App\Models\Menu\ProductVariant|null
     */
    private function getVariantByIndex(Product $product, int $index = 0)
    {
        return $product->variants()->orderBy('sort_order')->skip($index)->first();
    }

    private function createComboPersonal(Category $comboCategory): void
    {
        // Combo Personal: Sub (primera variante) + Bebida + Complemento
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Personal',
            'description' => 'Sub a elección + Bebida mediana + Papas o Galleta',
            'precio_pickup_capital' => 48.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 50.00,
            'precio_domicilio_interior' => 57.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Sub (primera variante - normalmente la más pequeña)
        $italianBMT = Product::where('name', 'Italian B.M.T.')->first();
        if ($italianBMT) {
            $firstVariant = $this->getVariantByIndex($italianBMT, 0);
            if ($firstVariant) {
                $combo->items()->create([
                    'product_id' => $italianBMT->id,
                    'variant_id' => $firstVariant->id,
                    'quantity' => 1,
                    'sort_order' => 1,
                ]);
            }
        }

        // Bebida mediana (Coca-Cola)
        $cocaColaMediano = Product::where('name', 'Coca-Cola Mediano')->first();
        if ($cocaColaMediano) {
            $combo->items()->create([
                'product_id' => $cocaColaMediano->id,
                'variant_id' => null,
                'quantity' => 1,
                'sort_order' => 2,
            ]);
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
        // Combo Doble: 2 Subs (primera variante) + 2 Bebidas
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Doble',
            'description' => '2 Subs a elección + 2 Bebidas medianas',
            'precio_pickup_capital' => 75.00,
            'precio_domicilio_capital' => 85.00,
            'precio_pickup_interior' => 78.00,
            'precio_domicilio_interior' => 88.00,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Sub 1: Italian BMT (primera variante)
        $italianBMT = Product::where('name', 'Italian B.M.T.')->first();
        if ($italianBMT) {
            $firstVariant = $this->getVariantByIndex($italianBMT, 0);
            if ($firstVariant) {
                $combo->items()->create([
                    'product_id' => $italianBMT->id,
                    'variant_id' => $firstVariant->id,
                    'quantity' => 1,
                    'sort_order' => 1,
                ]);
            }
        }

        // Sub 2: Pollo Teriyaki (primera variante)
        $polloTeriyaki = Product::where('name', 'Pollo Teriyaki')->first();
        if ($polloTeriyaki) {
            $firstVariant = $this->getVariantByIndex($polloTeriyaki, 0);
            if ($firstVariant) {
                $combo->items()->create([
                    'product_id' => $polloTeriyaki->id,
                    'variant_id' => $firstVariant->id,
                    'quantity' => 1,
                    'sort_order' => 2,
                ]);
            }
        }

        // 2 Bebidas medianas
        $cocaColaMediano = Product::where('name', 'Coca-Cola Mediano')->first();
        if ($cocaColaMediano) {
            $combo->items()->create([
                'product_id' => $cocaColaMediano->id,
                'variant_id' => null,
                'quantity' => 2,
                'sort_order' => 3,
            ]);
        }

        $this->command->line('      ✓ Combo Doble (Q75 pickup / Q85 delivery)');
    }

    private function createComboFamiliar(Category $comboCategory): void
    {
        // Combo Familiar: 2 Subs (segunda variante si existe, sino primera) + 2 Bebidas grandes + 2 Papas
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Familiar',
            'description' => '2 Subs a elección + 2 Bebidas grandes + 2 Papas',
            'precio_pickup_capital' => 145.00,
            'precio_domicilio_capital' => 160.00,
            'precio_pickup_interior' => 150.00,
            'precio_domicilio_interior' => 165.00,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Sub 1: Subway Club (segunda variante si existe, sino primera - normalmente la mediana)
        $subwayClub = Product::where('name', 'Subway Club')->first();
        if ($subwayClub) {
            $secondVariant = $this->getVariantByIndex($subwayClub, 1) ?? $this->getVariantByIndex($subwayClub, 0);
            if ($secondVariant) {
                $combo->items()->create([
                    'product_id' => $subwayClub->id,
                    'variant_id' => $secondVariant->id,
                    'quantity' => 1,
                    'sort_order' => 1,
                ]);
            }
        }

        // Sub 2: Steak & Cheese (segunda variante si existe, sino primera)
        $steakCheese = Product::where('name', 'Steak & Cheese')->first();
        if ($steakCheese) {
            $secondVariant = $this->getVariantByIndex($steakCheese, 1) ?? $this->getVariantByIndex($steakCheese, 0);
            if ($secondVariant) {
                $combo->items()->create([
                    'product_id' => $steakCheese->id,
                    'variant_id' => $secondVariant->id,
                    'quantity' => 1,
                    'sort_order' => 2,
                ]);
            }
        }

        // 2 Bebidas grandes
        $cocaColaGrande = Product::where('name', 'Coca-Cola Grande')->first();
        if ($cocaColaGrande) {
            $combo->items()->create([
                'product_id' => $cocaColaGrande->id,
                'variant_id' => null,
                'quantity' => 2,
                'sort_order' => 3,
            ]);
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
        // Combo Desayuno: Desayuno (primera variante) + Bebida personal + Muffin
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Desayuno',
            'description' => 'Desayuno a elección + Bebida personal + Muffin o Galleta',
            'precio_pickup_capital' => 42.00,
            'precio_domicilio_capital' => 48.00,
            'precio_pickup_interior' => 44.00,
            'precio_domicilio_interior' => 50.00,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Desayuno con Tocino y Huevo (primera variante)
        $desayuno = Product::where('name', 'Desayuno con Tocino y Huevo')->first();
        if ($desayuno) {
            $firstVariant = $this->getVariantByIndex($desayuno, 0);
            if ($firstVariant) {
                $combo->items()->create([
                    'product_id' => $desayuno->id,
                    'variant_id' => $firstVariant->id,
                    'quantity' => 1,
                    'sort_order' => 1,
                ]);
            }
        }

        // Bebida personal
        $cocaColaPersonal = Product::where('name', 'Coca-Cola Personal')->first();
        if ($cocaColaPersonal) {
            $combo->items()->create([
                'product_id' => $cocaColaPersonal->id,
                'variant_id' => null,
                'quantity' => 1,
                'sort_order' => 2,
            ]);
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
        // Combo Económico: Sub (primera variante) económico + Bebida personal
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Económico',
            'description' => 'Sub seleccionado + Bebida personal',
            'precio_pickup_capital' => 38.00,
            'precio_domicilio_capital' => 43.00,
            'precio_pickup_interior' => 40.00,
            'precio_domicilio_interior' => 45.00,
            'is_active' => true,
            'sort_order' => 5,
        ]);

        // Sub económico: Jamón (primera variante)
        $jamon = Product::where('name', 'Jamón')->first();
        if ($jamon) {
            $firstVariant = $this->getVariantByIndex($jamon, 0);
            if ($firstVariant) {
                $combo->items()->create([
                    'product_id' => $jamon->id,
                    'variant_id' => $firstVariant->id,
                    'quantity' => 1,
                    'sort_order' => 1,
                ]);
            }
        }

        // Bebida personal
        $cocaColaPersonal = Product::where('name', 'Coca-Cola Personal')->first();
        if ($cocaColaPersonal) {
            $combo->items()->create([
                'product_id' => $cocaColaPersonal->id,
                'variant_id' => null,
                'quantity' => 1,
                'sort_order' => 2,
            ]);
        }

        $this->command->line('      ✓ Combo Económico (Q38 pickup / Q43 delivery)');
    }
}
