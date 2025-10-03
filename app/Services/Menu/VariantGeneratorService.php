<?php

namespace App\Services\Menu;

use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use Illuminate\Support\Str;

class VariantGeneratorService
{
    /**
     * Genera automáticamente variantes para un producto basándose en las
     * definiciones de variantes de sus categorías asignadas.
     *
     * @param  Product  $product  Producto para el cual generar variantes
     * @param  bool  $skipExisting  Si es true, no regenera variantes existentes
     * @return int Número de variantes creadas
     */
    public function generateVariantsForProduct(Product $product, bool $skipExisting = true): int
    {
        $createdCount = 0;

        // Obtener todas las categorías del producto que usen variantes
        $categoriesWithVariants = $product->categories()
            ->where('uses_variants', true)
            ->whereNotNull('variant_definitions')
            ->get();

        // Si el producto no tiene categorías con variantes, no hacer nada
        if ($categoriesWithVariants->isEmpty()) {
            return 0;
        }

        // Recolectar todas las variantes únicas de todas las categorías
        $variantDefinitions = $categoriesWithVariants
            ->pluck('variant_definitions')
            ->flatten()
            ->unique()
            ->values();

        foreach ($variantDefinitions as $index => $variantName) {
            // Si skip existing, verificar si ya existe
            if ($skipExisting) {
                $exists = $product->variants()
                    ->where('size', $variantName)
                    ->exists();

                if ($exists) {
                    continue;
                }
            }

            // Generar SKU único
            $sku = $this->generateSKU($product, $variantName);

            // Crear variante
            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $sku,
                'name' => $product->name.' - '.$variantName,
                'size' => $variantName,
                'precio_pickup_capital' => 0,
                'precio_domicilio_capital' => 0,
                'precio_pickup_interior' => 0,
                'precio_domicilio_interior' => 0,
                'is_active' => true,
                'sort_order' => $index,
            ]);

            $createdCount++;
        }

        return $createdCount;
    }

    /**
     * Genera variantes para todos los productos de una categoría
     *
     * @param  Category  $category  Categoría con productos
     * @return array ['total_products' => int, 'variants_created' => int]
     */
    public function generateVariantsForCategory(Category $category): array
    {
        if (! $category->uses_variants || empty($category->variant_definitions)) {
            return ['total_products' => 0, 'variants_created' => 0];
        }

        $totalProducts = 0;
        $totalVariants = 0;

        foreach ($category->products as $product) {
            $variantsCreated = $this->generateVariantsForProduct($product);
            if ($variantsCreated > 0) {
                $totalProducts++;
                $totalVariants += $variantsCreated;
            }
        }

        return [
            'total_products' => $totalProducts,
            'variants_created' => $totalVariants,
        ];
    }

    /**
     * Genera un SKU único para una variante
     */
    protected function generateSKU(Product $product, string $variantName): string
    {
        // Generar prefijo del producto (primeras 3 letras)
        $productPrefix = strtoupper(Str::limit(Str::slug($product->name, ''), 3, ''));

        // Generar sufijo de la variante
        $variantSuffix = strtoupper(Str::limit(Str::slug($variantName, ''), 3, ''));

        // SKU base
        $baseSKU = "{$productPrefix}-{$product->id}-{$variantSuffix}";

        // Verificar si existe, si existe agregar número
        $counter = 1;
        $sku = $baseSKU;

        while (ProductVariant::where('sku', $sku)->exists()) {
            $sku = "{$baseSKU}-{$counter}";
            $counter++;
        }

        return $sku;
    }
}
