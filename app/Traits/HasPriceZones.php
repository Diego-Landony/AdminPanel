<?php

namespace App\Traits;

/**
 * Trait para manejar lógica de precios por zona y tipo de servicio.
 *
 * Centraliza la lógica de obtener el campo de precio correcto
 * según la zona (capital/interior) y tipo de servicio (pickup/delivery).
 */
trait HasPriceZones
{
    /**
     * Obtiene el nombre del campo de precio según zona y tipo de servicio.
     *
     * @param  string  $zone  'capital' o 'interior'
     * @param  string  $serviceType  'pickup' o 'delivery'
     * @return string Nombre del campo de precio
     */
    protected function getPriceField(string $zone, string $serviceType): string
    {
        return match ([$zone, $serviceType]) {
            ['capital', 'pickup'] => 'precio_pickup_capital',
            ['capital', 'delivery'] => 'precio_domicilio_capital',
            ['interior', 'pickup'] => 'precio_pickup_interior',
            ['interior', 'delivery'] => 'precio_domicilio_interior',
            default => 'precio_pickup_capital',
        };
    }

    /**
     * Obtiene el nombre del campo de precio especial Sub del Día.
     */
    protected function getDailySpecialPriceField(string $zone, string $serviceType): string
    {
        return 'daily_special_'.$this->getPriceField($zone, $serviceType);
    }

    /**
     * Obtiene todas las combinaciones de campos de precio disponibles.
     *
     * @return array<string, array<string, string>>
     */
    protected function getAllPriceFields(): array
    {
        return [
            'capital' => [
                'pickup' => 'precio_pickup_capital',
                'delivery' => 'precio_domicilio_capital',
            ],
            'interior' => [
                'pickup' => 'precio_pickup_interior',
                'delivery' => 'precio_domicilio_interior',
            ],
        ];
    }
}
