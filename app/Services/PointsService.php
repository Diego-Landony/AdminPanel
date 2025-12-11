<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Order;

/**
 * Servicio de Gestión de Puntos
 *
 * Maneja todas las operaciones relacionadas con el sistema de puntos:
 * - Cálculo de puntos a ganar
 * - Acreditación de puntos al completar órdenes
 * - Canje de puntos por descuentos
 * - Actualización automática de tipo de cliente
 */
class PointsService
{
    /**
     * Calcula los puntos a ganar basado en el total de la orden
     * Regla: 1 punto por cada Q10 gastados
     *
     * @param  float  $total  Total de la orden en quetzales
     * @return int Cantidad de puntos a ganar
     */
    public function calculatePointsToEarn(float $total): int
    {
        return (int) floor($total / 10);
    }

    /**
     * Acredita puntos al cliente al completar una orden
     *
     * @param  Customer  $customer  Cliente que recibirá los puntos
     * @param  Order  $order  Orden completada
     */
    public function creditPoints(Customer $customer, Order $order): void
    {
        $pointsToCredit = $this->calculatePointsToEarn($order->total);

        if ($pointsToCredit > 0) {
            $customer->points += $pointsToCredit;
            $customer->points_updated_at = now();
            $customer->save();

            $this->checkAndApplyUpgrade($customer);
        }
    }

    /**
     * Canjea puntos del cliente por un descuento
     * Regla: 1 punto = Q0.10 de descuento
     *
     * @param  Customer  $customer  Cliente que canjeará los puntos
     * @param  int  $points  Cantidad de puntos a canjear
     * @return float Monto del descuento en quetzales
     *
     * @throws \InvalidArgumentException Si el cliente no tiene suficientes puntos
     */
    public function redeemPoints(Customer $customer, int $points): float
    {
        if ($points <= 0) {
            throw new \InvalidArgumentException('La cantidad de puntos debe ser mayor a cero');
        }

        if ($customer->points < $points) {
            throw new \InvalidArgumentException('El cliente no tiene suficientes puntos');
        }

        $discount = $points * 0.10;

        $customer->points -= $points;
        $customer->points_updated_at = now();
        $customer->save();

        $this->checkAndApplyUpgrade($customer);

        return $discount;
    }

    /**
     * Verifica si el cliente califica para un upgrade de tipo
     * y lo aplica automáticamente si corresponde
     *
     * @param  Customer  $customer  Cliente a verificar
     */
    private function checkAndApplyUpgrade(Customer $customer): void
    {
        $newType = CustomerType::getTypeForPoints($customer->points);

        if ($newType && $customer->customer_type_id !== $newType->id) {
            $customer->customer_type_id = $newType->id;
            $customer->save();
        }
    }
}
