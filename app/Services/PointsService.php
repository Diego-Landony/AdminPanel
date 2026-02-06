<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPointsTransaction;
use App\Models\CustomerType;
use App\Models\Order;
use App\Models\PointsSetting;
use App\Services\Wallet\AppleWalletService;
use App\Services\Wallet\GoogleWalletService;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de Gestión de Puntos
 *
 * Maneja todas las operaciones relacionadas con el sistema de puntos:
 * - Cálculo de puntos a ganar
 * - Acreditación de puntos al completar órdenes
 * - Actualización automática de tipo de cliente
 */
class PointsService
{
    /**
     * Calcula los puntos a ganar basado en el total de la orden
     * Regla: 1 punto por cada X quetzales gastados (configurable)
     * Redondeo: Si la parte decimal es >= umbral configurado, se redondea hacia arriba
     *
     * @param  float  $total  Total de la orden en quetzales
     * @param  Customer|null  $customer  Cliente para aplicar multiplicador de tipo
     * @return int Cantidad de puntos a ganar
     */
    public function calculatePointsToEarn(float $total, ?Customer $customer = null): int
    {
        $settings = PointsSetting::get();

        // Calcular puntos base con redondeo configurable
        $basePoints = $this->roundWithThreshold($total / $settings->quetzales_per_point);

        // Aplicar multiplicador de tipo de cliente si existe
        $multiplier = $this->getMultiplier($customer);
        if ($multiplier > 1.0) {
            $finalPoints = $basePoints * $multiplier;

            return $this->roundWithThreshold($finalPoints);
        }

        return $basePoints;
    }

    /**
     * Redondea un número con umbral configurable
     * Si la parte decimal es >= umbral, redondea hacia arriba
     * NOTA: Solo redondea si ya tienes al menos 1 punto base
     * NOTA: Si umbral es 0, nunca redondea (siempre floor)
     * Ejemplo: Si quetzales_per_point=10, Q7 da 0 puntos, Q17 da 2 puntos (con umbral 0.7)
     */
    private function roundWithThreshold(float $value): int
    {
        $settings = PointsSetting::get();
        $intPart = (int) floor($value);

        // Si umbral es 0, nunca redondear
        if ($settings->rounding_threshold <= 0) {
            return $intPart;
        }

        // Usar round para evitar problemas de precisión de punto flotante
        $decimalPart = round($value - $intPart, 2);

        // Solo aplicar redondeo si ya tienes al menos 1 punto base
        // Esto evita que gastos menores al minimo den puntos por redondeo
        if ($intPart >= 1 && $decimalPart >= $settings->rounding_threshold) {
            return $intPart + 1;
        }

        return $intPart;
    }

    /**
     * Obtiene el multiplicador del tipo de cliente
     */
    private function getMultiplier(?Customer $customer): float
    {
        if (! $customer || ! $customer->customerType) {
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

        if ($pointsToCredit <= 0) {
            return;
        }

        DB::transaction(function () use ($customer, $order, $pointsToCredit) {
            $settings = PointsSetting::get();

            CustomerPointsTransaction::create([
                'customer_id' => $customer->id,
                'points' => $pointsToCredit,
                'type' => 'earned',
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'description' => "Puntos ganados en orden #{$order->order_number}",
                'expires_at' => now()->addMonths($settings->expiration_months),
            ]);

            $customer->points += $pointsToCredit;
            $customer->points_updated_at = now();
            $customer->points_last_activity_at = now();
            $customer->save();

            $this->checkAndApplyUpgrade($customer);
        });

        // Actualizar el pase de Google Wallet con los nuevos puntos
        $this->updateWalletPass($customer);
    }

    /**
     * Actualiza los pases de wallet del cliente (Google Wallet y Apple Wallet).
     * Se ejecuta de forma segura sin interrumpir el flujo principal.
     */
    private function updateWalletPass(Customer $customer): void
    {
        // Actualizar Google Wallet
        try {
            app(GoogleWalletService::class)->updateCustomerPass($customer);
        } catch (\Exception $e) {
            \Log::warning('Failed to update Google Wallet pass: '.$e->getMessage());
        }

        // Actualizar Apple Wallet (enviar push notifications)
        try {
            app(AppleWalletService::class)->updateCustomerPass($customer);
        } catch (\Exception $e) {
            \Log::warning('Failed to update Apple Wallet pass: '.$e->getMessage());
        }
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
