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
     * Redondeo: Si la parte decimal es >= 0.7, se redondea hacia arriba
     *
     * @param  float  $total  Total de la orden en quetzales
     * @param  Customer|null  $customer  Cliente para aplicar multiplicador de tipo
     * @return int Cantidad de puntos a ganar
     */
    public function calculatePointsToEarn(float $total, ?Customer $customer = null): int
    {
        // Calcular puntos base con redondeo 0.7
        $basePoints = $this->roundWithThreshold($total / 10);

        // Aplicar multiplicador de tipo de cliente si existe
        $multiplier = $this->getMultiplier($customer);
        if ($multiplier > 1.0) {
            $finalPoints = $basePoints * $multiplier;
            return $this->roundWithThreshold($finalPoints);
        }

        return $basePoints;
    }

    /**
     * Redondea un número con umbral de 0.7
     * Si la parte decimal es >= 0.7, redondea hacia arriba
     */
    private function roundWithThreshold(float $value): int
    {
        $intPart = (int) floor($value);
        // Usar round para evitar problemas de precisión de punto flotante
        $decimalPart = round($value - $intPart, 2);

        if ($decimalPart >= 0.7) {
            return $intPart + 1;
        }

        return $intPart;
    }

    /**
     * Obtiene el multiplicador del tipo de cliente
     */
    private function getMultiplier(?Customer $customer): float
    {
        if (!$customer || !$customer->customerType) {
            return 1.0;
        }

        $multiplier = $customer->customerType->multiplier;
        return $multiplier > 0 ? $multiplier : 1.0;
    }

    /**
     * Acredita puntos al cliente al completar una orden
     *
     * @param  Customer  $customer  Cliente que recibirá los puntos
     * @param  Order  $order  Orden completada
     */
    public function creditPoints(Customer $customer, Order $order): void
    {
        $pointsToCredit = $this->calculatePointsToEarn($order->total, $customer);

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
