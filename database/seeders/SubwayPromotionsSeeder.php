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

        // Limpiar datos existentes (respetando foreign keys)
        PromotionItem::query()->delete();
        Promotion::query()->delete();

        $this->create2x1Promotions();
        $this->createSubDelDia();
        $this->createDiscountPromotions();

        $this->command->info('   ✅ Promociones creadas exitosamente');
    }

    private function create2x1Promotions(): void
    {
        $this->command->line('   🎁 Creando promociones 2x1...');

        // 2x1 en Subs seleccionados
        $promotion = Promotion::create([
            'name' => '2x1 en Subs Clásicos',
            'description' => 'Compra un Sub clásico 15cm y lleva otro de igual o menor valor gratis',
            'type' => 'two_for_one',
            'is_active' => true,
        ]);

        $subs = Product::whereIn('name', [
            'Italian B.M.T.',
            'Pechuga de Pavo',
            'Jamón',
            'Atún',
        ])->with('variants')->get();

        foreach ($subs as $sub) {
            // Usar la primera variante (ordenada por sort_order) en lugar de buscar hardcoded '15cm'
            $firstVariant = $sub->variants->sortBy('sort_order')->first();
            if ($firstVariant) {
                PromotionItem::create([
                    'promotion_id' => $promotion->id,
                    'product_id' => $sub->id,
                    'variant_id' => $firstVariant->id,
                    'category_id' => null,
                    'special_price_capital' => $firstVariant->precio_pickup_capital * 0.5,
                    'special_price_interior' => $firstVariant->precio_pickup_interior * 0.5,
                ]);
            }
        }

        $this->command->line("      ✓ {$promotion->name}");

        // 2x1 en Bebidas Medianas
        $promotion2 = Promotion::create([
            'name' => '2x1 en Bebidas Medianas',
            'description' => 'Compra una bebida mediana y lleva otra gratis',
            'type' => 'two_for_one',
            'is_active' => true,
        ]);

        // Bebidas medianas (ahora son productos individuales sin variantes)
        $bebidasMedianas = Product::where('category_id', Category::where('name', 'Bebidas')->first()->id)
            ->where('name', 'LIKE', '%Mediano%')
            ->get();

        foreach ($bebidasMedianas as $bebida) {
            PromotionItem::create([
                'promotion_id' => $promotion2->id,
                'product_id' => $bebida->id,
                'variant_id' => null,
                'category_id' => null,
                'special_price_capital' => $bebida->precio_pickup_capital * 0.5,
                'special_price_interior' => $bebida->precio_pickup_interior * 0.5,
            ]);
        }

        $this->command->line("      ✓ {$promotion2->name}");
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
            $product = Product::where('name', $subData['name'])->first();
            if ($product) {
                // Usar la primera variante (ordenada por sort_order) en lugar de buscar hardcoded '15cm'
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

                    // Crear item en la promoción
                    $promotion->items()->create([
                        'product_id' => $product->id,
                        'variant_id' => $firstVariant->id,
                        'category_id' => null,
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

        $this->command->line("      ✅ Promoción 'Sub del Día' creada con 8 items");
    }

    private function createDiscountPromotions(): void
    {
        $this->command->line('   💰 Creando promociones de descuento...');

        // Descuento en Desayunos
        $promotion = Promotion::create([
            'name' => '15% de Descuento en Desayunos',
            'description' => 'Todos los desayunos con 15% de descuento de 6am a 11am',
            'type' => 'percentage_discount',
            'is_active' => true,
        ]);

        $categoryDesayunos = Category::where('name', 'Desayunos')->first();
        PromotionItem::create([
            'promotion_id' => $promotion->id,
            'product_id' => null,
            'variant_id' => null,
            'category_id' => $categoryDesayunos->id,
            'discount_percentage' => 15.00,
            'validity_type' => 'time_range',
            'time_from' => '06:00:00',
            'time_until' => '11:00:00',
        ]);

        $this->command->line("      ✓ {$promotion->name}");

        // Descuento en Ensaladas
        $promotion2 = Promotion::create([
            'name' => '20% de Descuento en Ensaladas',
            'description' => 'Todas las ensaladas con 20% de descuento',
            'type' => 'percentage_discount',
            'is_active' => true,
        ]);

        $categoryEnsaladas = Category::where('name', 'Ensaladas')->first();
        PromotionItem::create([
            'promotion_id' => $promotion2->id,
            'product_id' => null,
            'variant_id' => null,
            'category_id' => $categoryEnsaladas->id,
            'discount_percentage' => 20.00,
            'validity_type' => 'permanent',
        ]);

        $this->command->line("      ✓ {$promotion2->name}");
    }
}
