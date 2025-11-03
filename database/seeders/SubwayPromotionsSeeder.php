<?php

namespace Database\Seeders;

use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\Promotion;
use App\Models\Menu\PromotionItem;
use Illuminate\Database\Seeder;

class SubwayPromotionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎉 Creando promociones de Subway...');

        // Limpiar datos existentes
        PromotionItem::query()->delete();
        Promotion::query()->delete();

        $this->create2x1Promotions();
        $this->createSubDelDia();
        $this->createDiscountPromotions();
        $this->createMixedDiscountPromotion();
        $this->createBundlePromotion();

        $this->command->info('   ✅ Promociones creadas exitosamente');
    }

    private function create2x1Promotions(): void
    {
        $this->command->line('   🎁 Creando promociones 2x1...');

        // 2x1 en Subs de 15cm - Simula selección de categoría + UNA variante + múltiples productos
        // IMPORTANTE: El frontend envía UN item lógico (category + variant + products)
        // y lo expande a múltiples promotion_items, todos con la MISMA variante
        $categorySubs = Category::where('name', 'Subs')->first();
        if ($categorySubs) {
            $promotion = Promotion::create([
                'name' => '2x1 en Subs Clásicos 15cm',
                'description' => 'Compra un Sub clásico de 15cm y lleva otro de igual o menor valor gratis',
                'type' => 'two_for_one',
                'is_active' => true,
            ]);

            // Configuración global (aplica a todos los items de esta promoción)
            $globalConfig = [
                'service_type' => 'both',
                'validity_type' => 'permanent',
            ];

            // Obtener productos de Subs con variantes
            $subsProducts = Product::where('category_id', $categorySubs->id)
                ->with(['variants' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order');
                }])
                ->get();

            // Obtener UNA variante de 15cm (la primera que encontremos con size='15cm')
            $variant15cm = null;
            foreach ($subsProducts as $product) {
                if ($product->has_variants && $product->variants->isNotEmpty()) {
                    // Buscar específicamente una variante con size='15cm'
                    $variant = $product->variants->where('size', '15cm')->first();
                    if ($variant) {
                        $variant15cm = $variant;
                        break;
                    }
                }
            }

            $itemCount = 0;
            // Crear un promotion_item por cada producto, TODOS con el MISMO variant_id
            // IMPORTANTE: El frontend usa el variant_id de la PRIMERA variante encontrada
            // para TODOS los productos seleccionados (ver create.tsx línea 233)
            if ($variant15cm) {
                foreach ($subsProducts as $product) {
                    if ($product->has_variants) {
                        // Usar el variant_id de la PRIMERA variante 15cm para TODOS
                        // (simula el comportamiento del frontend)
                        PromotionItem::create([
                            'promotion_id' => $promotion->id,
                            'product_id' => $product->id,
                            'variant_id' => $variant15cm->id,  // ← El MISMO variant_id para TODOS
                            'category_id' => $product->category_id,
                            'service_type' => $globalConfig['service_type'],
                            'validity_type' => $globalConfig['validity_type'],
                        ]);
                        $itemCount++;
                    }
                }
            }

            $this->command->line("      ✓ {$promotion->name} - {$itemCount} items (todos con variante 15cm)");
        }

        // 2x1 en Bebidas (sin variantes)
        $categoryBebidas = Category::where('name', 'Bebidas')->first();
        if ($categoryBebidas) {
            $promotion2 = Promotion::create([
                'name' => '2x1 en Bebidas',
                'description' => 'Compra una bebida y lleva otra gratis',
                'type' => 'two_for_one',
                'is_active' => true,
            ]);

            // Configuración global
            $globalConfig = [
                'service_type' => 'both',
                'validity_type' => 'permanent',
            ];

            // Obtener todas las bebidas (sin variantes)
            $bebidasProducts = Product::where('category_id', $categoryBebidas->id)->get();

            $itemCount = 0;
            // Crear un promotion_item por cada bebida (sin variant_id)
            foreach ($bebidasProducts as $product) {
                PromotionItem::create([
                    'promotion_id' => $promotion2->id,
                    'product_id' => $product->id,
                    'variant_id' => null,  // Bebidas no tienen variantes
                    'category_id' => $product->category_id,
                    'service_type' => $globalConfig['service_type'],
                    'validity_type' => $globalConfig['validity_type'],
                ]);
                $itemCount++;
            }

            $this->command->line("      ✓ {$promotion2->name} - {$itemCount} items (sin variantes)");
        }
    }

    private function createSubDelDia(): void
    {
        $this->command->line('   ⭐ Creando Sub del Día...');

        // Precio especial uniforme para todos los subs del día
        $precioSubDelDia = 22;

        // Crear UNA SOLA promoción "Sub del Día"
        $promotion = Promotion::create([
            'name' => 'Sub del Día',
            'description' => 'Disfruta nuestros deliciosos Subs a precio especial según el día de la semana',
            'type' => 'daily_special',
            'is_active' => true,
        ]);

        // Items del Sub del Día (cada uno con sus días específicos)
        // IMPORTANTE: Un item por cada producto+variante combinación
        $subsDelDia = [
            ['name' => 'Pechuga de Pollo', 'days' => [1, 2, 3, 4, 5, 6, 7], 'dayName' => 'Todos los días'],
            ['name' => 'Jamón', 'days' => [1], 'dayName' => 'Lunes'],
            ['name' => 'Italian B.M.T.', 'days' => [2], 'dayName' => 'Martes'],
            ['name' => 'Pechuga de Pavo', 'days' => [3], 'dayName' => 'Miércoles'],
            ['name' => 'Pollo Teriyaki', 'days' => [4], 'dayName' => 'Jueves'],
            ['name' => 'Atún', 'days' => [5], 'dayName' => 'Viernes'],
            ['name' => 'Subway Club', 'days' => [6], 'dayName' => 'Sábado'],
            ['name' => 'Subway Melt', 'days' => [7], 'dayName' => 'Domingo'],
        ];

        foreach ($subsDelDia as $subData) {
            $product = Product::where('name', $subData['name'])->with('category')->first();
            if ($product) {
                // Usar la primera variante (ordenada por sort_order)
                $firstVariant = $product->variants()->orderBy('sort_order')->first();
                if ($firstVariant) {
                    // Actualizar información en la variante
                    $firstVariant->update([
                        'is_daily_special' => true,
                        'daily_special_days' => $subData['days'],
                        'daily_special_precio_pickup_capital' => $precioSubDelDia,
                        'daily_special_precio_domicilio_capital' => $precioSubDelDia + 5,
                        'daily_special_precio_pickup_interior' => $precioSubDelDia + 2,
                        'daily_special_precio_domicilio_interior' => $precioSubDelDia + 7,
                    ]);

                    // Crear UN ITEM por cada producto+variante
                    // IMPORTANTE: category_id es REQUERIDO (desde products.category_id)
                    $promotion->items()->create([
                        'product_id' => $product->id,
                        'variant_id' => $firstVariant->id,
                        'category_id' => $product->category_id,  // ✅ REQUERIDO
                        'special_price_capital' => $precioSubDelDia,
                        'special_price_interior' => $precioSubDelDia + 2,
                        'service_type' => 'both',
                        'validity_type' => 'weekdays',
                        'weekdays' => $subData['days'],
                    ]);

                    $this->command->line("      ✓ {$product->name} ({$firstVariant->name}) - {$subData['dayName']} (Q{$precioSubDelDia})");
                }
            }
        }

        $this->command->line("      ✅ Promoción 'Sub del Día' creada con ".count($subsDelDia).' items');
    }

    private function createDiscountPromotions(): void
    {
        $this->command->line('   💰 Creando promociones de descuento...');

        // Descuento en Desayunos 15cm - Simula selección por categoría + variante
        // SEGÚN EL PLAN: Un "item lógico" = categoría + UNA variante + múltiples productos
        $categoryDesayunos = Category::where('name', 'Desayunos')->first();
        if ($categoryDesayunos) {
            $promotion = Promotion::create([
                'name' => '15% de Descuento en Desayunos',
                'description' => 'Todos los desayunos con 15% de descuento de 6am a 11am',
                'type' => 'percentage_discount',
                'is_active' => true,
            ]);

            // Configuración GLOBAL (aplica a toda la promoción)
            $globalConfig = [
                'service_type' => 'both',
                'validity_type' => 'time_range',
                'time_from' => '06:00:00',
                'time_until' => '11:00:00',
            ];

            // Obtener todos los productos de desayunos con variantes
            $desayunosProducts = Product::where('category_id', $categoryDesayunos->id)
                ->with(['variants' => function ($query) {
                    $query->orderBy('sort_order');
                }])
                ->get();

            // Agrupar por variante (simula lo que hace el frontend)
            $productsByVariant = [];
            foreach ($desayunosProducts as $product) {
                foreach ($product->variants as $variant) {
                    if (! isset($productsByVariant[$variant->id])) {
                        $productsByVariant[$variant->id] = [
                            'variant' => $variant,
                            'products' => [],
                        ];
                    }
                    $productsByVariant[$variant->id]['products'][] = $product;
                }
            }

            // ITEM LÓGICO por cada variante
            // Simula: "Usuario selecciona Desayunos + 15cm + [todos los productos de 15cm]"
            foreach ($productsByVariant as $variantGroup) {
                $variant = $variantGroup['variant'];
                $products = $variantGroup['products'];

                // EXPANSIÓN: Crear un promotion_item por cada producto
                // (esto es lo que hace el frontend al submitear)
                foreach ($products as $product) {
                    PromotionItem::create([
                        'promotion_id' => $promotion->id,
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'category_id' => $product->category_id,
                        'discount_percentage' => 15.00,
                        'service_type' => $globalConfig['service_type'],
                        'validity_type' => $globalConfig['validity_type'],
                        'time_from' => $globalConfig['time_from'],
                        'time_until' => $globalConfig['time_until'],
                    ]);
                }

                $this->command->line("      ✓ Item: Desayunos {$variant->name} ({$variant->size}) - ".count($products).' productos');
            }

            $this->command->line("      ✅ {$promotion->name}");
        }

        // Descuento en Ensaladas - Categoría sin variantes
        $categoryEnsaladas = Category::where('name', 'Ensaladas')->first();
        if ($categoryEnsaladas) {
            $promotion2 = Promotion::create([
                'name' => '20% de Descuento en Ensaladas',
                'description' => 'Todas las ensaladas con 20% de descuento',
                'type' => 'percentage_discount',
                'is_active' => true,
            ]);

            // Configuración GLOBAL
            $globalConfig = [
                'service_type' => 'both',
                'validity_type' => 'permanent',
            ];

            // UN ITEM LÓGICO: Categoría Ensaladas (sin variante) + todos los productos
            // Simula: "Usuario selecciona Ensaladas + [todos los productos de ensaladas]"
            $ensaladasProducts = Product::where('category_id', $categoryEnsaladas->id)->get();

            // EXPANSIÓN: Crear un promotion_item por cada producto
            foreach ($ensaladasProducts as $product) {
                PromotionItem::create([
                    'promotion_id' => $promotion2->id,
                    'product_id' => $product->id,
                    'variant_id' => null,  // Categoría sin variantes
                    'category_id' => $product->category_id,
                    'discount_percentage' => 20.00,
                    'service_type' => $globalConfig['service_type'],
                    'validity_type' => $globalConfig['validity_type'],
                ]);
            }

            $this->command->line('      ✓ Item: Ensaladas (sin variantes) - '.count($ensaladasProducts).' productos');
            $this->command->line("      ✅ {$promotion2->name}");
        }
    }

    private function createMixedDiscountPromotion(): void
    {
        $this->command->line('   🎯 Creando promoción mixta de descuento...');

        // Promoción mixta: Subs 15cm + Postres
        // Simula el caso de uso del plan (líneas 30-35):
        // ITEM 1: Categoría "Subs" + Variante "15cm" → múltiples productos → 10% descuento
        // ITEM 2: Categoría "Postres" (sin variantes) → múltiples productos → 15% descuento
        // GLOBAL: Tipo de servicio y vigencia aplican a TODA la promoción

        $promotion = Promotion::create([
            'name' => 'Combo Sweet Deal - Subs y Postres',
            'description' => '10% en Subs 15cm seleccionados + 15% en Postres',
            'type' => 'percentage_discount',
            'is_active' => true,
        ]);

        // Configuración GLOBAL (aplica a todos los items)
        $globalConfig = [
            'service_type' => 'both',
            'validity_type' => 'permanent',
        ];

        // ========== ITEM 1: Subs 15cm (categoría CON variantes) ==========
        $categorySubs = Category::where('name', 'Subs')->first();
        if ($categorySubs) {
            // Obtener productos de Subs
            $subsProducts = Product::where('category_id', $categorySubs->id)
                ->with(['variants' => function ($query) {
                    $query->orderBy('sort_order');
                }])
                ->get();

            // Filtrar solo productos que tengan variante 15cm
            $subsSeleccionados = [];
            $variant15cm = null;

            foreach ($subsProducts as $product) {
                // Obtener la primera variante (15cm)
                $firstVariant = $product->variants->first();
                if ($firstVariant && $firstVariant->size === '15cm') {
                    $variant15cm = $firstVariant;
                    // Seleccionar solo algunos subs populares
                    if (in_array($product->name, ['Italian B.M.T.', 'Pollo Teriyaki', 'Pechuga de Pavo', 'Subway Club'])) {
                        $subsSeleccionados[] = $product;
                    }
                }
            }

            // EXPANSIÓN: Crear un promotion_item por cada producto seleccionado
            if ($variant15cm && count($subsSeleccionados) > 0) {
                foreach ($subsSeleccionados as $product) {
                    PromotionItem::create([
                        'promotion_id' => $promotion->id,
                        'product_id' => $product->id,
                        'variant_id' => $variant15cm->id,
                        'category_id' => $product->category_id,
                        'discount_percentage' => 10.00,
                        'service_type' => $globalConfig['service_type'],
                        'validity_type' => $globalConfig['validity_type'],
                    ]);
                }

                $this->command->line('      ✓ Item 1: Subs 15cm - '.count($subsSeleccionados).' productos (10% descuento)');
            }
        }

        // ========== ITEM 2: Postres (categoría SIN variantes) ==========
        $categoryPostres = Category::where('name', 'Postres')->first();
        if ($categoryPostres) {
            // Obtener todos los productos de Postres
            $postresProducts = Product::where('category_id', $categoryPostres->id)->get();

            // EXPANSIÓN: Crear un promotion_item por cada postre
            foreach ($postresProducts as $product) {
                PromotionItem::create([
                    'promotion_id' => $promotion->id,
                    'product_id' => $product->id,
                    'variant_id' => null,  // Sin variantes
                    'category_id' => $product->category_id,
                    'discount_percentage' => 15.00,
                    'service_type' => $globalConfig['service_type'],
                    'validity_type' => $globalConfig['validity_type'],
                ]);
            }

            $this->command->line('      ✓ Item 2: Postres (sin variantes) - '.count($postresProducts).' productos (15% descuento)');
        }

        $this->command->line("      ✅ {$promotion->name} - Promoción MIXTA creada");
    }

    private function createBundlePromotion(): void
    {
        $this->command->line('   🌟 Creando promoción Bundle Special...');

        // Promoción Bundle: Verano Chilero
        // Simula un bundle con precio especial (similar a combos pero con promoción)
        $promotion = Promotion::create([
            'name' => 'verano chilero',
            'description' => 'Elige tu ensalada favorita + Pepsi lata a precio especial',
            'type' => 'bundle_special',
            'special_bundle_price_capital' => 35.00,
            'special_bundle_price_interior' => 37.00,
            'is_active' => true,
            'sort_order' => 7,
        ]);

        // ITEM 1: GRUPO DE ELECCIÓN - Elige tu ensalada
        $ensaladaGroup = \App\Models\Menu\BundlePromotionItem::create([
            'promotion_id' => $promotion->id,
            'product_id' => null,
            'variant_id' => null,
            'is_choice_group' => true,
            'choice_label' => 'elige tu ensalada',
            'quantity' => 1,
            'sort_order' => 1,
        ]);

        // Opciones de ensaladas
        $ensaladas = ['Ensalada B.M.T', 'Ensalada de Pollo Teriyaki', 'Ensalada Veggie'];
        $sortOrder = 1;
        foreach ($ensaladas as $ensaladaName) {
            $product = Product::where('name', $ensaladaName)->first();
            if ($product) {
                \App\Models\Menu\BundlePromotionItemOption::create([
                    'bundle_item_id' => $ensaladaGroup->id,
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        // ITEM 2: FIJO - Pepsi lata
        $pepsi = Product::where('name', 'Pepsi lata')->first();
        if ($pepsi) {
            \App\Models\Menu\BundlePromotionItem::create([
                'promotion_id' => $promotion->id,
                'product_id' => $pepsi->id,
                'variant_id' => null,
                'is_choice_group' => false,
                'choice_label' => null,
                'quantity' => 1,
                'sort_order' => 2,
            ]);
        }

        $this->command->line("      ✅ {$promotion->name} - Bundle Special creado (Q35 capital / Q37 interior)");
    }
}
