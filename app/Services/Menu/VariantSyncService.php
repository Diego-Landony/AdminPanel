<?php

namespace App\Services\Menu;

use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para sincronizar variantes de categorías a productos
 *
 * Maneja la sincronización automática cuando se modifican variant_definitions en categorías:
 * - Agregar variante: crea en todos los productos (is_active=false, precios NULL)
 * - Renombrar variante: actualiza nombre en todos los productos (mantiene precios)
 * - Eliminar variante: valida que no esté en uso antes de permitir
 */
class VariantSyncService
{
    /**
     * Sincroniza los cambios en variant_definitions de una categoría a sus productos
     *
     * @return array Resumen de cambios aplicados
     */
    public function syncCategoryVariants(Category $category, array $oldDefinitions, array $newDefinitions): array
    {
        $changes = [
            'added' => [],
            'renamed' => [],
            'removed' => [],
        ];

        // Detectar variantes agregadas
        $added = array_diff($newDefinitions, $oldDefinitions);
        foreach ($added as $variantName) {
            $this->addVariantToCategory($category, $variantName);
            $changes['added'][] = $variantName;
        }

        // Detectar variantes eliminadas
        $removed = array_diff($oldDefinitions, $newDefinitions);
        foreach ($removed as $variantName) {
            $this->removeVariant($category, $variantName);
            $changes['removed'][] = $variantName;
        }

        // Detectar renombramientos (posición se mantiene pero nombre cambia)
        $renames = $this->detectRenames($oldDefinitions, $newDefinitions);
        foreach ($renames as $oldName => $newName) {
            $this->renameVariant($category, $oldName, $newName);
            $changes['renamed'][] = ['from' => $oldName, 'to' => $newName];
        }

        return $changes;
    }

    /**
     * Agrega una nueva variante a todos los productos de la categoría
     *
     * @return int Número de variantes creadas
     */
    public function addVariantToCategory(Category $category, string $variantName): int
    {
        $products = Product::where('category_id', $category->id)->get();
        $created = 0;

        foreach ($products as $product) {
            // Verificar si ya existe (por seguridad)
            $exists = ProductVariant::where('product_id', $product->id)
                ->where('name', $variantName)
                ->exists();

            if (! $exists) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => 'PROD-'.$product->id.'-'.uniqid(),
                    'name' => $variantName,
                    'size' => $variantName,
                    'precio_pickup_capital' => null,
                    'precio_domicilio_capital' => null,
                    'precio_pickup_interior' => null,
                    'precio_domicilio_interior' => null,
                    'is_active' => false,  // Inactiva por defecto
                    'sort_order' => 999,   // Al final
                ]);
                $created++;
            }
        }

        return $created;
    }

    /**
     * Renombra una variante en todos los productos de la categoría
     *
     * @return int Número de variantes renombradas
     */
    public function renameVariant(Category $category, string $oldName, string $newName): int
    {
        $updated = DB::table('product_variants')
            ->whereIn('product_id', function ($query) use ($category) {
                $query->select('id')
                    ->from('products')
                    ->where('category_id', $category->id);
            })
            ->where('name', $oldName)
            ->update([
                'name' => $newName,
                'size' => $newName,
                'updated_at' => now(),
            ]);

        return $updated;
    }

    /**
     * Elimina una variante de la categoría
     * Solo permite si NO está en uso (no existen product_variants con ese nombre)
     *
     * @throws \Exception Si la variante está en uso
     */
    public function removeVariant(Category $category, string $variantName): void
    {
        $inUse = ProductVariant::where('name', $variantName)
            ->whereHas('product', fn ($q) => $q->where('category_id', $category->id))
            ->exists();

        if ($inUse) {
            $count = ProductVariant::where('name', $variantName)
                ->whereHas('product', fn ($q) => $q->where('category_id', $category->id))
                ->count();

            throw new \Exception("No se puede eliminar '$variantName'. Está en uso en $count productos de esta categoría.");
        }

        // Si no está en uso, se puede eliminar de variant_definitions
        // No hay registros que eliminar en product_variants porque no existe
    }

    /**
     * Detecta renombramientos comparando posiciones en los arrays
     *
     * Si un elemento en la misma posición cambió de nombre, se considera renombramiento
     *
     * @return array Mapa de renombramientos ['oldName' => 'newName']
     */
    protected function detectRenames(array $oldDefinitions, array $newDefinitions): array
    {
        $renames = [];

        // Verificar cambios en la misma posición
        $minLength = min(count($oldDefinitions), count($newDefinitions));

        for ($i = 0; $i < $minLength; $i++) {
            if ($oldDefinitions[$i] !== $newDefinitions[$i]) {
                // Verificar que el nuevo nombre no existía antes y el viejo no existe después
                if (! in_array($newDefinitions[$i], $oldDefinitions) && ! in_array($oldDefinitions[$i], $newDefinitions)) {
                    $renames[$oldDefinitions[$i]] = $newDefinitions[$i];
                }
            }
        }

        return $renames;
    }

    /**
     * Verifica si una variante está en uso en algún producto
     */
    public function isVariantInUse(Category $category, string $variantName): bool
    {
        return ProductVariant::where('name', $variantName)
            ->whereHas('product', fn ($q) => $q->where('category_id', $category->id))
            ->exists();
    }

    /**
     * Obtiene el conteo de productos que usan una variante
     */
    public function getVariantUsageCount(Category $category, string $variantName): int
    {
        return ProductVariant::where('name', $variantName)
            ->whereHas('product', fn ($q) => $q->where('category_id', $category->id))
            ->count();
    }
}
