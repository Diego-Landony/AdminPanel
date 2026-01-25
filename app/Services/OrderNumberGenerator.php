<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;

/**
 * Generador de Números de Orden
 *
 * Genera números únicos de orden en formato: XXX-YYMMDD-NNNNN
 * Donde:
 * - XXX: Código de franquicia del restaurante (default: "SUB")
 * - YYMMDD: Fecha (año corto + mes + día)
 * - NNNNN: Secuencial diario (5 dígitos, reinicia cada día)
 *
 * Ejemplo: SUB-260122-00001
 *
 * Nota: Las órdenes legacy importadas mantienen su número original.
 */
class OrderNumberGenerator
{
    private const DEFAULT_FRANCHISE = 'SUB';

    /**
     * Genera un número de orden único para el día actual
     *
     * @param  Restaurant|int|null  $restaurant  Restaurante o su ID para obtener código de franquicia
     * @return string Número de orden en formato XXX-YYMMDD-NNNNN
     */
    public function generate(Restaurant|int|null $restaurant = null): string
    {
        $date = now();
        $dateCode = $date->format('ymd'); // 260122

        // Obtener código de franquicia
        $franchiseCode = $this->getFranchiseCode($restaurant);

        // Obtener secuencial del día de forma atómica
        $sequence = $this->getNextSequence($dateCode, $franchiseCode);

        return sprintf('%s-%s-%05d', $franchiseCode, $dateCode, $sequence);
    }

    /**
     * Obtiene el código de franquicia del restaurante
     */
    private function getFranchiseCode(Restaurant|int|null $restaurant): string
    {
        if ($restaurant === null) {
            return self::DEFAULT_FRANCHISE;
        }

        if (is_int($restaurant)) {
            $restaurant = Restaurant::find($restaurant);
        }

        if (! $restaurant) {
            return self::DEFAULT_FRANCHISE;
        }

        // Usar franchise_number si existe, sino default
        $code = $restaurant->franchise_number;

        if (empty($code)) {
            return self::DEFAULT_FRANCHISE;
        }

        // Asegurar que sea uppercase y máximo 5 caracteres
        return strtoupper(substr($code, 0, 5));
    }

    /**
     * Obtiene el siguiente número de secuencia para el día
     * Usa bloqueo para evitar condiciones de carrera
     */
    private function getNextSequence(string $dateCode, string $franchiseCode): int
    {
        return DB::transaction(function () use ($dateCode, $franchiseCode) {
            // Buscar la última orden del día con el mismo formato
            // El patrón es: XXX-YYMMDD-NNNNN
            $pattern = "{$franchiseCode}-{$dateCode}-%";

            $lastOrder = Order::withTrashed()
                ->where('order_number', 'LIKE', $pattern)
                ->lockForUpdate()
                ->orderByRaw('CAST(SUBSTRING_INDEX(order_number, "-", -1) AS UNSIGNED) DESC')
                ->first();

            if (! $lastOrder) {
                return 1;
            }

            // Extraer el secuencial (último segmento después del último guión)
            $parts = explode('-', $lastOrder->order_number);
            $lastSequence = (int) end($parts);

            return $lastSequence + 1;
        });
    }
}
