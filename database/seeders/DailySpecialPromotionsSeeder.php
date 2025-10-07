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

        // Obtener algunos productos para las promociones
        $products = Product::with('variants')->limit(5)->get();

        if ($products->isEmpty()) {
            $this->command->warn('⚠️  No hay productos disponibles. Ejecuta primero los seeders de productos.');

            return;
        }

        // 1. Sub del Día - Lunes a Viernes (días laborales)
        $product1 = $products->first();
        $promotion1 = Promotion::create([
            'name' => 'Sub del Día - Jamón Italiano L-V',
            'description' => 'Promoción especial de sub italiano en días laborales',
            'type' => 'daily_special',
            'scope_type' => 'product',
            'service_type' => 'both',
            'validity_type' => 'weekdays',
            'special_price_capital' => 75.00,
            'special_price_interior' => 80.00,
            'applies_to' => 'product',
            'weekdays' => [1, 2, 3, 4, 5], // Lunes a Viernes
            'is_active' => true,
        ]);

        $promotion1->items()->create([
            'product_id' => $product1->id,
        ]);

        $this->command->info("✅ Sub del Día creado: {$promotion1->name} ({$product1->name})");

        // 2. Sub del Día - Fin de semana
        if ($products->count() > 1) {
            $product2 = $products->skip(1)->first();
            $promotion2 = Promotion::create([
                'name' => 'Sub del Día - Especial Fin de Semana',
                'description' => 'Precio especial para sábado y domingo',
                'type' => 'daily_special',
                'scope_type' => 'product',
                'service_type' => 'both',
                'validity_type' => 'weekdays',
                'special_price_capital' => 85.00,
                'special_price_interior' => 90.00,
                'applies_to' => 'product',
                'weekdays' => [6, 7], // Sábado y Domingo
                'is_active' => true,
            ]);

            $promotion2->items()->create([
                'product_id' => $product2->id,
            ]);

            $this->command->info("✅ Sub del Día creado: {$promotion2->name} ({$product2->name})");
        }

        // 3. Sub del Día - Solo Delivery, Miércoles
        if ($products->count() > 2) {
            $product3 = $products->skip(2)->first();
            $promotion3 = Promotion::create([
                'name' => 'Miércoles de Sub - Solo Delivery',
                'description' => 'Oferta especial solo para pedidos a domicilio',
                'type' => 'daily_special',
                'scope_type' => 'product',
                'service_type' => 'delivery_only',
                'validity_type' => 'weekdays',
                'special_price_capital' => 70.00,
                'special_price_interior' => 75.00,
                'applies_to' => 'product',
                'weekdays' => [3], // Miércoles
                'is_active' => true,
            ]);

            $promotion3->items()->create([
                'product_id' => $product3->id,
            ]);

            $this->command->info("✅ Sub del Día creado: {$promotion3->name} ({$product3->name})");
        }

        // 4. Sub del Día - Permanente (todos los días)
        if ($products->count() > 3) {
            $product4 = $products->skip(3)->first();
            $promotion4 = Promotion::create([
                'name' => 'Sub Económico - Todos los días',
                'description' => 'Precio especial permanente',
                'type' => 'daily_special',
                'scope_type' => 'product',
                'service_type' => 'both',
                'validity_type' => 'permanent',
                'special_price_capital' => 65.00,
                'special_price_interior' => 70.00,
                'applies_to' => 'product',
                'is_active' => true,
            ]);

            $promotion4->items()->create([
                'product_id' => $product4->id,
            ]);

            $this->command->info("✅ Sub del Día creado: {$promotion4->name} ({$product4->name})");
        }

        // 5. Sub del Día - Solo Pickup, Viernes
        if ($products->count() > 4) {
            $product5 = $products->skip(4)->first();
            $promotion5 = Promotion::create([
                'name' => 'Viernes de Sub - Solo Pickup',
                'description' => 'Oferta especial solo para recoger en tienda',
                'type' => 'daily_special',
                'scope_type' => 'product',
                'service_type' => 'pickup_only',
                'validity_type' => 'weekdays',
                'special_price_capital' => 68.00,
                'special_price_interior' => 73.00,
                'applies_to' => 'product',
                'weekdays' => [5], // Viernes
                'is_active' => true,
            ]);

            $promotion5->items()->create([
                'product_id' => $product5->id,
            ]);

            $this->command->info("✅ Sub del Día creado: {$promotion5->name} ({$product5->name})");
        }

        $this->command->info('✨ Promociones Sub del Día creadas exitosamente!');
    }
}
