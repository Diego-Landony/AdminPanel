<?php

namespace App\Services;

use App\Models\Order;

/**
 * Generador de Números de Orden
 *
 * Genera números únicos de orden en formato: ORD-YYYY-NNNNNN
 * Ejemplo: ORD-2025-000001
 */
class OrderNumberGenerator
{
    /**
     * Genera un número de orden único para el año actual
     *
     * @return string Número de orden en formato ORD-YYYY-NNNNNN
     */
    public function generate(): string
    {
        $year = date('Y');
        $lastOrder = Order::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastOrder
            ? (int) substr($lastOrder->order_number, -6) + 1
            : 1;

        return sprintf('ORD-%s-%06d', $year, $sequence);
    }
}
