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
     * Basado en productos REALES existentes en la base de datos
     */
    public function run(): void
    {
        $this->command->info('🎁 Creando combos reales de Subway Guatemala...');

        // Limpiar datos existentes
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
     * Obtiene la primera variante de un producto (ordenado por sort_order)
     * Normalmente la más pequeña: 15cm para subs, tamaño personal, etc.
     */
    private function getFirstVariant(Product $product)
    {
        return $product->variants()->orderBy('sort_order')->first();
    }

    /**
     * Obtiene la segunda variante de un producto (ordenado por sort_order)
     * Normalmente la más grande: 30cm para subs, etc.
     */
    private function getSecondVariant(Product $product)
    {
        return $product->variants()->orderBy('sort_order')->skip(1)->first();
    }

    private function createComboPersonal(Category $comboCategory): void
    {
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Personal',
            'description' => 'Elige tu Sub 15cm + Bebida + Complemento a elección',
            'precio_pickup_capital' => 48.00,
            'precio_domicilio_capital' => 55.00,
            'precio_pickup_interior' => 50.00,
            'precio_domicilio_interior' => 57.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // GRUPO DE ELECCIÓN: Elige tu Sub 15cm
        $subChoiceGroup = $combo->items()->create([
            'is_choice_group' => true,
            'choice_label' => 'Elige tu Sub 15cm',
            'product_id' => null,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 1,
        ]);

        // Opciones de subs populares en 15cm
        $subsOptions = ['Italian B.M.T.', 'Pollo Teriyaki', 'Pechuga de Pavo', 'Atún', 'Jamón'];
        $sortOrder = 1;
        foreach ($subsOptions as $subName) {
            $product = Product::where('name', $subName)->first();
            if ($product) {
                $variant = $this->getFirstVariant($product);
                if ($variant) {
                    $subChoiceGroup->options()->create([
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        // GRUPO DE ELECCIÓN: Elige tu Bebida (sin variantes)
        $bebidaChoiceGroup = $combo->items()->create([
            'is_choice_group' => true,
            'choice_label' => 'Elige tu Bebida',
            'product_id' => null,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 2,
        ]);

        // Opciones de bebidas (sin variantes - productos simples)
        $bebidasOptions = ['Gaseosa Lata', 'Pepsi lata', 'Seven up', 'Agua Pura', 'Jugo Petit'];
        $sortOrder = 1;
        foreach ($bebidasOptions as $bebidaName) {
            $product = Product::where('name', $bebidaName)->first();
            if ($product) {
                $bebidaChoiceGroup->options()->create([
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        // GRUPO DE ELECCIÓN: Papas o Galleta
        $complementoChoiceGroup = $combo->items()->create([
            'is_choice_group' => true,
            'choice_label' => 'Papas o Galleta',
            'product_id' => null,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 3,
        ]);

        // Opciones de complementos
        $complementosOptions = ['Papas Lays', 'Doritos', 'Cookie con Chispas de Chocolate', 'Cookie de Avena con Pasas'];
        $sortOrder = 1;
        foreach ($complementosOptions as $complementoName) {
            $product = Product::where('name', $complementoName)->first();
            if ($product) {
                $complementoChoiceGroup->options()->create([
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        $this->command->line('      ✓ Combo Personal (Q48 pickup / Q55 delivery)');
    }

    private function createComboDoble(Category $comboCategory): void
    {
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Doble',
            'description' => '2 Subs 15cm a elección + 2 Bebidas a elección',
            'precio_pickup_capital' => 75.00,
            'precio_domicilio_capital' => 85.00,
            'precio_pickup_interior' => 78.00,
            'precio_domicilio_interior' => 88.00,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // GRUPO DE ELECCIÓN 1: Primer Sub 15cm
        $sub1ChoiceGroup = $combo->items()->create([
            'is_choice_group' => true,
            'choice_label' => 'Elige tu Primer Sub 15cm',
            'product_id' => null,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 1,
        ]);

        $subsOptions = ['Italian B.M.T.', 'Pollo Teriyaki', 'Pechuga de Pavo', 'Subway Club', 'Atún', 'Jamón'];
        $sortOrder = 1;
        foreach ($subsOptions as $subName) {
            $product = Product::where('name', $subName)->first();
            if ($product) {
                $variant = $this->getFirstVariant($product);
                if ($variant) {
                    $sub1ChoiceGroup->options()->create([
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        // GRUPO DE ELECCIÓN 2: Segundo Sub 15cm
        $sub2ChoiceGroup = $combo->items()->create([
            'is_choice_group' => true,
            'choice_label' => 'Elige tu Segundo Sub 15cm',
            'product_id' => null,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 2,
        ]);

        $sortOrder = 1;
        foreach ($subsOptions as $subName) {
            $product = Product::where('name', $subName)->first();
            if ($product) {
                $variant = $this->getFirstVariant($product);
                if ($variant) {
                    $sub2ChoiceGroup->options()->create([
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        // ITEM FIJO: 2 Bebidas (Gaseosa Lata como predeterminado)
        $gaseosa = Product::where('name', 'Gaseosa Lata')->first();
        if ($gaseosa) {
            $combo->items()->create([
                'is_choice_group' => false,
                'product_id' => $gaseosa->id,
                'variant_id' => null,
                'quantity' => 2,
                'sort_order' => 3,
            ]);
        }

        $this->command->line('      ✓ Combo Doble (Q75 pickup / Q85 delivery)');
    }

    private function createComboFamiliar(Category $comboCategory): void
    {
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Familiar',
            'description' => '2 Subs 30cm a elección + 2 Bebidas + 2 Papas',
            'precio_pickup_capital' => 145.00,
            'precio_domicilio_capital' => 160.00,
            'precio_pickup_interior' => 150.00,
            'precio_domicilio_interior' => 165.00,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // GRUPO DE ELECCIÓN 1: Primer Sub 30cm
        $sub1ChoiceGroup = $combo->items()->create([
            'is_choice_group' => true,
            'choice_label' => 'Elige tu Primer Sub 30cm',
            'product_id' => null,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 1,
        ]);

        $subsOptions = ['Italian B.M.T.', 'Subway Club', 'Steak & Cheese', 'Pollo Teriyaki', 'Pechuga de Pavo'];
        $sortOrder = 1;
        foreach ($subsOptions as $subName) {
            $product = Product::where('name', $subName)->first();
            if ($product) {
                $variant = $this->getSecondVariant($product);
                if ($variant) {
                    $sub1ChoiceGroup->options()->create([
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        // GRUPO DE ELECCIÓN 2: Segundo Sub 30cm
        $sub2ChoiceGroup = $combo->items()->create([
            'is_choice_group' => true,
            'choice_label' => 'Elige tu Segundo Sub 30cm',
            'product_id' => null,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 2,
        ]);

        $sortOrder = 1;
        foreach ($subsOptions as $subName) {
            $product = Product::where('name', $subName)->first();
            if ($product) {
                $variant = $this->getSecondVariant($product);
                if ($variant) {
                    $sub2ChoiceGroup->options()->create([
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        // ITEM FIJO: 2 Bebidas
        $gaseosa = Product::where('name', 'Gaseosa Lata')->first();
        if ($gaseosa) {
            $combo->items()->create([
                'is_choice_group' => false,
                'product_id' => $gaseosa->id,
                'variant_id' => null,
                'quantity' => 2,
                'sort_order' => 3,
            ]);
        }

        // ITEM FIJO: 2 Papas
        $papas = Product::where('name', 'Papas Lays')->first();
        if ($papas) {
            $combo->items()->create([
                'is_choice_group' => false,
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
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Desayuno',
            'description' => 'Desayuno 15cm a elección + Bebida + Postre',
            'precio_pickup_capital' => 42.00,
            'precio_domicilio_capital' => 48.00,
            'precio_pickup_interior' => 44.00,
            'precio_domicilio_interior' => 50.00,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // GRUPO DE ELECCIÓN: Elige tu Desayuno 15cm
        $desayunoChoiceGroup = $combo->items()->create([
            'is_choice_group' => true,
            'choice_label' => 'Elige tu Desayuno 15cm',
            'product_id' => null,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 1,
        ]);

        $desayunosOptions = ['Desayuno con Tocino y Huevo', 'Desayuno con Jamón y Huevo', 'Desayuno Steak y Huevo'];
        $sortOrder = 1;
        foreach ($desayunosOptions as $desayunoName) {
            $product = Product::where('name', $desayunoName)->first();
            if ($product) {
                $variant = $this->getFirstVariant($product);
                if ($variant) {
                    $desayunoChoiceGroup->options()->create([
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        // ITEM FIJO: Jugo Petit
        $jugo = Product::where('name', 'Jugo Petit')->first();
        if ($jugo) {
            $combo->items()->create([
                'is_choice_group' => false,
                'product_id' => $jugo->id,
                'variant_id' => null,
                'quantity' => 1,
                'sort_order' => 2,
            ]);
        }

        // GRUPO DE ELECCIÓN: Muffin o Galleta
        $postreChoiceGroup = $combo->items()->create([
            'is_choice_group' => true,
            'choice_label' => 'Muffin o Galleta',
            'product_id' => null,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 3,
        ]);

        $postresOptions = ['Muffin de Arándanos', 'Cookie con Chispas de Chocolate', 'Cookie de Avena con Pasas'];
        $sortOrder = 1;
        foreach ($postresOptions as $postreName) {
            $product = Product::where('name', $postreName)->first();
            if ($product) {
                $postreChoiceGroup->options()->create([
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        $this->command->line('      ✓ Combo Desayuno (Q42 pickup / Q48 delivery)');
    }

    private function createComboEconomico(Category $comboCategory): void
    {
        $combo = Combo::create([
            'category_id' => $comboCategory->id,
            'name' => 'Combo Económico',
            'description' => 'Sub 15cm a elección + Bebida',
            'precio_pickup_capital' => 38.00,
            'precio_domicilio_capital' => 43.00,
            'precio_pickup_interior' => 40.00,
            'precio_domicilio_interior' => 45.00,
            'is_active' => true,
            'sort_order' => 5,
        ]);

        // GRUPO DE ELECCIÓN: Elige tu Sub 15cm
        $subChoiceGroup = $combo->items()->create([
            'is_choice_group' => true,
            'choice_label' => 'Elige tu Sub 15cm',
            'product_id' => null,
            'variant_id' => null,
            'quantity' => 1,
            'sort_order' => 1,
        ]);

        $subsOptions = ['Jamón', 'Pechuga de Pollo', 'Veggie Delite', 'Atún'];
        $sortOrder = 1;
        foreach ($subsOptions as $subName) {
            $product = Product::where('name', $subName)->first();
            if ($product) {
                $variant = $this->getFirstVariant($product);
                if ($variant) {
                    $subChoiceGroup->options()->create([
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        // ITEM FIJO: Gaseosa Lata
        $gaseosa = Product::where('name', 'Gaseosa Lata')->first();
        if ($gaseosa) {
            $combo->items()->create([
                'is_choice_group' => false,
                'product_id' => $gaseosa->id,
                'variant_id' => null,
                'quantity' => 1,
                'sort_order' => 2,
            ]);
        }

        $this->command->line('      ✓ Combo Económico (Q38 pickup / Q43 delivery)');
    }
}
