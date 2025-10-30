<?php

namespace Database\Seeders;

use App\Models\Menu\Product;
use App\Models\Menu\Promotion;
use Illuminate\Database\Seeder;

class DailySpecialPromotionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏷️  Creando promociones Sub del Día...');

        // Obtener algunos productos con sus variantes y categoría
        $products = Product::with(['variants' => function ($query) {
            $query->orderBy('sort_order');
        }, 'category'])->limit(5)->get();

        if ($products->isEmpty()) {
            $this->command->warn('⚠️  No hay productos disponibles. Ejecuta primero los seeders de productos.');

            return;
        }

        // 1. Sub del Día - Lunes a Viernes (días laborales)
        $product1 = $products->first();
        $variant1 = $product1->variants->first();

        if ($variant1) {
            $promotion1 = Promotion::create([
                'name' => 'Sub del Día - Jamón Italiano L-V',
                'description' => 'Promoción especial de sub italiano en días laborales',
                'type' => 'daily_special',
                'is_active' => true,
            ]);

            $promotion1->items()->create([
                'product_id' => $product1->id,
                'variant_id' => $variant1->id,
                'category_id' => $product1->category_id,  // ✅ REQUERIDO
                'special_price_capital' => 75.00,
                'special_price_interior' => 80.00,
                'service_type' => 'both',
                'validity_type' => 'weekdays',
                'weekdays' => [1, 2, 3, 4, 5],
            ]);

            $this->command->info("✅ Sub del Día creado: {$promotion1->name} ({$product1->name} - {$variant1->name})");
        }

        // 2. Sub del Día - Fin de semana
        if ($products->count() > 1) {
            $product2 = $products->skip(1)->first();
            $variant2 = $product2->variants->first();

            if ($variant2) {
                $promotion2 = Promotion::create([
                    'name' => 'Sub del Día - Especial Fin de Semana',
                    'description' => 'Precio especial para sábado y domingo',
                    'type' => 'daily_special',
                    'is_active' => true,
                ]);

                $promotion2->items()->create([
                    'product_id' => $product2->id,
                    'variant_id' => $variant2->id,
                    'category_id' => $product2->category_id,  // ✅ REQUERIDO
                    'special_price_capital' => 85.00,
                    'special_price_interior' => 90.00,
                    'service_type' => 'both',
                    'validity_type' => 'weekdays',
                    'weekdays' => [6, 7],
                ]);

                $this->command->info("✅ Sub del Día creado: {$promotion2->name} ({$product2->name} - {$variant2->name})");
            }
        }

        // 3. Sub del Día - Solo Delivery, Miércoles
        if ($products->count() > 2) {
            $product3 = $products->skip(2)->first();
            $variant3 = $product3->variants->first();

            if ($variant3) {
                $promotion3 = Promotion::create([
                    'name' => 'Miércoles de Sub - Solo Delivery',
                    'description' => 'Oferta especial solo para pedidos a domicilio',
                    'type' => 'daily_special',
                    'is_active' => true,
                ]);

                $promotion3->items()->create([
                    'product_id' => $product3->id,
                    'variant_id' => $variant3->id,
                    'category_id' => $product3->category_id,  // ✅ REQUERIDO
                    'special_price_capital' => 70.00,
                    'special_price_interior' => 75.00,
                    'service_type' => 'delivery_only',
                    'validity_type' => 'weekdays',
                    'weekdays' => [3],
                ]);

                $this->command->info("✅ Sub del Día creado: {$promotion3->name} ({$product3->name} - {$variant3->name})");
            }
        }

        // 4. Sub del Día - Permanente (todos los días)
        if ($products->count() > 3) {
            $product4 = $products->skip(3)->first();
            $variant4 = $product4->variants->first();

            if ($variant4) {
                $promotion4 = Promotion::create([
                    'name' => 'Sub Económico - Todos los días',
                    'description' => 'Precio especial permanente',
                    'type' => 'daily_special',
                    'is_active' => true,
                ]);

                $promotion4->items()->create([
                    'product_id' => $product4->id,
                    'variant_id' => $variant4->id,
                    'category_id' => $product4->category_id,  // ✅ REQUERIDO
                    'special_price_capital' => 65.00,
                    'special_price_interior' => 70.00,
                    'service_type' => 'both',
                    'validity_type' => 'permanent',
                ]);

                $this->command->info("✅ Sub del Día creado: {$promotion4->name} ({$product4->name} - {$variant4->name})");
            }
        }

        // 5. Sub del Día - Solo Pickup, Viernes
        if ($products->count() > 4) {
            $product5 = $products->skip(4)->first();
            $variant5 = $product5->variants->first();

            if ($variant5) {
                $promotion5 = Promotion::create([
                    'name' => 'Viernes de Sub - Solo Pickup',
                    'description' => 'Oferta especial solo para recoger en tienda',
                    'type' => 'daily_special',
                    'is_active' => true,
                ]);

                $promotion5->items()->create([
                    'product_id' => $product5->id,
                    'variant_id' => $variant5->id,
                    'category_id' => $product5->category_id,  // ✅ REQUERIDO
                    'special_price_capital' => 68.00,
                    'special_price_interior' => 73.00,
                    'service_type' => 'pickup_only',
                    'validity_type' => 'weekdays',
                    'weekdays' => [5],
                ]);

                $this->command->info("✅ Sub del Día creado: {$promotion5->name} ({$product5->name} - {$variant5->name})");
            }
        }

        $this->command->info('✨ Promociones Sub del Día creadas exitosamente!');
    }
}
