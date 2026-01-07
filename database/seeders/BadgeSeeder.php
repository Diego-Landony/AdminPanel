<?php

namespace Database\Seeders;

use App\Models\Menu\BadgeType;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductBadge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏷️  Creando sistema de badges...');

        // Limpiar datos existentes
        ProductBadge::query()->delete();
        BadgeType::query()->delete();

        $this->createBadgeTypes();
        $this->assignBadgesToProducts();
        $this->assignBadgesToCombos();

        $this->command->info('   ✅ Badges creados y asignados exitosamente');
    }

    private function createBadgeTypes(): void
    {
        $this->command->line('   📌 Creando tipos de badges...');

        $badgeTypes = [
            [
                'name' => 'Nuevo',
                'color' => '#22c55e',
                'text_color' => '#ffffff',
                'sort_order' => 1,
            ],
            [
                'name' => 'Popular',
                'color' => '#f97316',
                'text_color' => '#ffffff',
                'sort_order' => 2,
            ],
            [
                'name' => 'Más Vendido',
                'color' => '#ef4444',
                'text_color' => '#ffffff',
                'sort_order' => 3,
            ],
            [
                'name' => 'Recomendado',
                'color' => '#3b82f6',
                'text_color' => '#ffffff',
                'sort_order' => 4,
            ],
            [
                'name' => 'Edición Limitada',
                'color' => '#8b5cf6',
                'text_color' => '#ffffff',
                'sort_order' => 5,
            ],
            [
                'name' => 'Sin Gluten',
                'color' => '#14b8a6',
                'text_color' => '#ffffff',
                'sort_order' => 6,
            ],
            [
                'name' => 'Vegetariano',
                'color' => '#10b981',
                'text_color' => '#ffffff',
                'sort_order' => 7,
            ],
        ];

        foreach ($badgeTypes as $badge) {
            BadgeType::create([
                'name' => $badge['name'],
                'color' => $badge['color'],
                'text_color' => $badge['text_color'],
                'is_active' => true,
                'sort_order' => $badge['sort_order'],
            ]);

            $this->command->line("      ✓ {$badge['name']} ({$badge['color']})");
        }
    }

    private function assignBadgesToProducts(): void
    {
        $this->command->line('   🥪 Asignando badges a productos...');

        // Obtener badge types
        $nuevo = BadgeType::where('name', 'Nuevo')->first();
        $popular = BadgeType::where('name', 'Popular')->first();
        $masVendido = BadgeType::where('name', 'Más Vendido')->first();
        $recomendado = BadgeType::where('name', 'Recomendado')->first();
        $edicionLimitada = BadgeType::where('name', 'Edición Limitada')->first();
        $vegetariano = BadgeType::where('name', 'Vegetariano')->first();

        // Italian B.M.T. - Más vendido (permanente)
        $this->assignBadge('Italian B.M.T.', $masVendido, 'permanent');

        // Pollo Teriyaki - Popular
        $this->assignBadge('Pollo Teriyaki', $popular, 'permanent');

        // Subway Series - Nuevos
        $this->assignBadge('DON B.M.T.', $nuevo, 'permanent');
        $this->assignBadge('CAPITÁN YAKI', $nuevo, 'permanent');
        $this->assignBadge('EL JEFE', $nuevo, 'permanent');
        $this->assignBadge('EL JEFE', $recomendado, 'permanent');

        // Steak & Cheese - Recomendado
        $this->assignBadge('Steak & Cheese', $recomendado, 'permanent');

        // Veggie Delite - Vegetariano
        $this->assignBadge('Veggie Delite', $vegetariano, 'permanent');
        $this->assignBadge('Ensalada Veggie', $vegetariano, 'permanent');
        $this->assignBadge('Wrap Veggie', $vegetariano, 'permanent');
        $this->assignBadge('Pizza Personal Vegetariana', $vegetariano, 'permanent');

        // Subway Melt - Popular
        $this->assignBadge('Subway Melt', $popular, 'permanent');

        // Wrap de Pollo Teriyaki - Nuevo
        $this->assignBadge('Wrap de Pollo Teriyaki', $nuevo, 'permanent');

        // Desayuno chilero way - Edición Limitada (solo fines de semana)
        $this->assignBadge('Desayuno chilero way', $edicionLimitada, 'weekdays', null, null, [6, 7]); // Sábado y Domingo

        // Pizza Personal Suprema - Recomendado
        $this->assignBadge('Pizza Personal Suprema', $recomendado, 'permanent');

        // Muffin de Arándanos - Nuevo
        $this->assignBadge('Muffin de Arándanos', $nuevo, 'permanent');

        // Galleta con chocolate - Más vendido (postres)
        $this->assignBadge('galleta con chocolate', $masVendido, 'permanent');
    }

    private function assignBadgesToCombos(): void
    {
        $this->command->line('   🍱 Asignando badges a combos...');

        $popular = BadgeType::where('name', 'Popular')->first();
        $recomendado = BadgeType::where('name', 'Recomendado')->first();
        $masVendido = BadgeType::where('name', 'Más Vendido')->first();

        // Asignar a los primeros combos que existan
        $combos = Combo::where('is_active', true)->take(3)->get();

        if ($combos->count() >= 1) {
            $combos[0]->addBadge($masVendido->id, 'permanent');
            $this->command->line("      ✓ {$combos[0]->name} -> Más Vendido");
        }

        if ($combos->count() >= 2) {
            $combos[1]->addBadge($popular->id, 'permanent');
            $this->command->line("      ✓ {$combos[1]->name} -> Popular");
        }

        if ($combos->count() >= 3) {
            $combos[2]->addBadge($recomendado->id, 'permanent');
            $this->command->line("      ✓ {$combos[2]->name} -> Recomendado");
        }
    }

    private function assignBadge(
        string $productName,
        ?BadgeType $badgeType,
        string $validityType = 'permanent',
        ?string $validFrom = null,
        ?string $validUntil = null,
        ?array $weekdays = null
    ): void {
        if (! $badgeType) {
            return;
        }

        $product = Product::where('name', $productName)->first();

        if (! $product) {
            $this->command->warn("      ⚠ Producto no encontrado: {$productName}");

            return;
        }

        $product->addBadge($badgeType->id, $validityType, $validFrom, $validUntil, $weekdays);
        $this->command->line("      ✓ {$productName} -> {$badgeType->name}");
    }
}
