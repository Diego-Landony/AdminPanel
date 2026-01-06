<?php

namespace App\Models\Concerns;

/**
 * Trait para derivar categoría de reportería desde datos existentes.
 *
 * Categorías de reportería soportadas:
 * - 30cm: Variantes de subs 30cm
 * - 15cm: Variantes de subs 15cm
 * - wraps, ensaladas, bebidas, postres, desayunos: Por categoría de menú
 * - complementos: Extras, chips, budín, etc.
 * - combos: Todos los combos
 * - pizzas: Pizzas personales
 * - subway_series: Subway Series
 */
trait HasReportingCategory
{
    /**
     * Mapeo de nombre de categoría de menú → categoría de reportería
     */
    public static function getReportingCategoryMap(): array
    {
        return [
            'Subs' => 'subs',
            'Subway Series' => 'subway_series',
            'Wraps' => 'wraps',
            'Bebidas' => 'bebidas',
            'Ensaladas' => 'ensaladas',
            'Pizzas Personales' => 'pizzas',
            'Complementos' => 'complementos',
            'Postres' => 'postres',
            'Desayunos' => 'desayunos',
            'Combos' => 'combos',
        ];
    }

    /**
     * Lista de categorías de reportería válidas
     */
    public static function getValidReportingCategories(): array
    {
        return [
            '30cm',
            '15cm',
            'subs',
            'subway_series',
            'wraps',
            'bebidas',
            'ensaladas',
            'pizzas',
            'complementos',
            'postres',
            'desayunos',
            'combos',
        ];
    }

    /**
     * Deriva la categoría de reportería desde el nombre de categoría de menú
     */
    protected function deriveReportingCategoryFromMenuCategory(?string $categoryName): string
    {
        if (! $categoryName) {
            return 'otros';
        }

        $map = static::getReportingCategoryMap();

        return $map[$categoryName] ?? strtolower(str_replace(' ', '_', $categoryName));
    }
}
