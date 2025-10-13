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
            $variant15cm = $sub->variants->where('size', '15cm')->first();
            if ($variant15cm) {
                PromotionItem::create([
                    'promotion_id' => $promotion->id,
                    'product_id' => null,
                    'variant_id' => $variant15cm->id,
                    'category_id' => null,
                    'special_price_capital' => $variant15cm->precio_pickup_capital * 0.5,
                    'special_price_interior' => $variant15cm->precio_pickup_interior * 0.5,
                ]);
            }
        }

        $this->command->line("      ✓ {$promotion->name}");

        // 2x1 en Bebidas
        $promotion2 = Promotion::create([
            'name' => '2x1 en Bebidas Medianas',
            'description' => 'Compra una bebida mediana y lleva otra gratis',
            'type' => 'two_for_one',
            'is_active' => true,
        ]);

        $bebidas = Product::where('category_id', Category::where('name', 'Bebidas')->first()->id)
            ->with('variants')
            ->get();

        foreach ($bebidas as $bebida) {
            $variantMediano = $bebida->variants->where('size', 'mediano')->first();
            if ($variantMediano) {
                PromotionItem::create([
                    'promotion_id' => $promotion2->id,
                    'product_id' => null,
                    'variant_id' => $variantMediano->id,
                    'category_id' => null,
                    'special_price_capital' => $variantMediano->precio_pickup_capital * 0.5,
                    'special_price_interior' => $variantMediano->precio_pickup_interior * 0.5,
                ]);
            }
        }

        $this->command->line("      ✓ {$promotion2->name}");
    }

    private function createSubDelDia(): void
    {
        $this->command->line('   ⭐ Creando Sub del Día...');

        // Sub del Día real de Subway Guatemala
        // Cada día tiene un sub diferente con precio especial de 15cm
        $subsDelDia = [
            ['name' => 'Jamón', 'day' => 1, 'precio_especial' => 27], // Lunes
            ['name' => 'Italian B.M.T.', 'day' => 2, 'precio_especial' => 29], // Martes
            ['name' => 'Pechuga de Pavo', 'day' => 3, 'precio_especial' => 28], // Miércoles
            ['name' => 'Pollo Teriyaki', 'day' => 4, 'precio_especial' => 30], // Jueves
            ['name' => 'Atún', 'day' => 5, 'precio_especial' => 28], // Viernes
            ['name' => 'Subway Club', 'day' => 6, 'precio_especial' => 32], // Sábado
            ['name' => 'Subway Melt', 'day' => 0, 'precio_especial' => 33], // Domingo
        ];

        foreach ($subsDelDia as $subData) {
            $product = Product::where('name', $subData['name'])->first();
            if ($product) {
                $variant15cm = $product->variants()->where('size', '15cm')->first();
                if ($variant15cm) {
                    $variant15cm->update([
                        'is_daily_special' => true,
                        'daily_special_days' => json_encode([$subData['day']]),
                        'daily_special_precio_pickup_capital' => $subData['precio_especial'],
                        'daily_special_precio_domicilio_capital' => $subData['precio_especial'] + 5,
                        'daily_special_precio_pickup_interior' => $subData['precio_especial'] + 2,
                        'daily_special_precio_domicilio_interior' => $subData['precio_especial'] + 7,
                    ]);

                    $dayName = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][$subData['day']];
                    $this->command->line("      ✓ {$product->name} - {$dayName} (Q{$subData['precio_especial']})");
                }
            }
        }
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
        ]);

        $this->command->line("      ✓ {$promotion2->name}");
    }
}
