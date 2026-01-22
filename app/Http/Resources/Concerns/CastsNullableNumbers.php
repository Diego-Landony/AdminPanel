<?php

namespace App\Http\Resources\Concerns;

/**
 * Trait for handling nullable numeric values in API Resources.
 *
 * Solves the problem where Laravel's decimal cast returns "" (empty string)
 * instead of null for NULL database values.
 */
trait CastsNullableNumbers
{
    /**
     * Convert a value to float or null.
     * Handles empty strings that come from decimal casts.
     */
    protected function toFloatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    /**
     * Convert a value to int or null.
     * Handles empty strings that come from integer casts.
     */
    protected function toIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * Build an array of nullable float prices for zone pricing.
     *
     * @param  object  $source  The model or object containing price fields
     * @param  string  $prefix  The prefix for price fields (e.g., 'special_price_', 'special_bundle_price_')
     * @return array<string, float|null>
     */
    protected function buildZonePrices(object $source, string $prefix = ''): array
    {
        return [
            'pickup_capital' => $this->toFloatOrNull($source->{$prefix.'pickup_capital'}),
            'delivery_capital' => $this->toFloatOrNull($source->{$prefix.'delivery_capital'}),
            'pickup_interior' => $this->toFloatOrNull($source->{$prefix.'pickup_interior'}),
            'delivery_interior' => $this->toFloatOrNull($source->{$prefix.'delivery_interior'}),
        ];
    }

    /**
     * Build an array of nullable float special prices for promotions.
     *
     * @param  object  $source  The model containing special_price fields
     * @return array<string, float|null>
     */
    protected function buildSpecialPrices(object $source): array
    {
        return $this->buildZonePrices($source, 'special_price_');
    }

    /**
     * Build an array of nullable float bundle prices for bundle specials.
     *
     * @param  object  $source  The model containing special_bundle_price fields
     * @return array<string, float|null>
     */
    protected function buildBundlePrices(object $source): array
    {
        return $this->buildZonePrices($source, 'special_bundle_price_');
    }
}
