<?php

namespace App\Jobs;

use App\Events\CustomerTypeDowngraded;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Notifications\CustomerTypeDowngradedNotification;
use App\Notifications\CustomerTypeUpgradedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job para actualizar tipos de cliente basado en puntos totales acumulados.
 *
 * Lógica:
 * - Usa el saldo total de puntos del cliente (campo points)
 * - Asigna el tipo de cliente más alto que califique según los puntos
 * - Si no califica para ningún tipo, asigna el tipo default (Regular)
 * - Esto permite tanto upgrades como downgrades automáticos
 */
class UpdateCustomerTypes implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('UpdateCustomerTypes: Iniciando actualización de tipos de cliente basado en puntos totales');

        // Obtener el tipo default (Regular - el de menor puntos_required)
        $defaultType = CustomerType::getDefault();

        if (! $defaultType) {
            Log::error('UpdateCustomerTypes: No se encontró tipo de cliente default');

            return;
        }

        // Obtener todos los tipos activos ordenados por puntos (desc)
        $customerTypes = CustomerType::active()
            ->orderBy('points_required', 'desc')
            ->get();

        // Actualizar tipos de cliente usando una consulta eficiente
        $updated = $this->updateCustomerTypesInBatches($customerTypes, $defaultType);

        Log::info('UpdateCustomerTypes: Actualización completada', [
            'customers_updated' => $updated,
        ]);
    }

    /**
     * Actualiza los tipos de cliente en lotes para mejor rendimiento
     */
    protected function updateCustomerTypesInBatches(
        $customerTypes,
        CustomerType $defaultType
    ): int {
        $updated = 0;

        // Indexar tipos por ID para búsqueda rápida
        $typesById = $customerTypes->keyBy('id');
        $typesById[$defaultType->id] = $defaultType;

        // Procesar clientes en lotes
        Customer::query()
            ->select(['id', 'customer_type_id', 'first_name', 'email', 'email_offers_enabled', 'points'])
            ->chunk(500, function ($customers) use ($customerTypes, $defaultType, $typesById, &$updated) {
                foreach ($customers as $customer) {
                    // Usar los puntos totales acumulados del cliente
                    $totalPoints = $customer->points ?? 0;

                    // Encontrar el tipo apropiado (el más alto que califique)
                    $newTypeId = $defaultType->id;
                    foreach ($customerTypes as $type) {
                        if ($totalPoints >= $type->points_required) {
                            $newTypeId = $type->id;
                            break; // Ya encontramos el tipo más alto que califica
                        }
                    }

                    // Solo actualizar si el tipo cambió
                    if ($customer->customer_type_id !== $newTypeId) {
                        $previousTypeId = $customer->customer_type_id;
                        $previousType = $typesById->get($previousTypeId);
                        $newType = $typesById->get($newTypeId);

                        Customer::where('id', $customer->id)
                            ->update(['customer_type_id' => $newTypeId]);
                        $updated++;

                        // Cargar el cliente completo para la notificación
                        $fullCustomer = Customer::find($customer->id);

                        // Detectar si es un downgrade (nuevo tipo tiene menos puntos requeridos)
                        if ($previousType && $newType && $newType->points_required < $previousType->points_required) {
                            // Disparar evento de downgrade
                            event(new CustomerTypeDowngraded($fullCustomer, $previousType, $newType));

                            // Enviar notificación al cliente
                            $fullCustomer->notify(new CustomerTypeDowngradedNotification($previousType, $newType));

                            Log::info('CustomerTypeDowngraded: Cliente degradado', [
                                'customer_id' => $customer->id,
                                'from' => $previousType->name,
                                'to' => $newType->name,
                            ]);
                        }

                        // Detectar si es un upgrade (nuevo tipo tiene más puntos requeridos)
                        if ($previousType && $newType && $newType->points_required > $previousType->points_required) {
                            // Enviar notificación al cliente
                            $fullCustomer->notify(new CustomerTypeUpgradedNotification($previousType, $newType));

                            Log::info('CustomerTypeUpgraded: Cliente ascendido', [
                                'customer_id' => $customer->id,
                                'from' => $previousType->name,
                                'to' => $newType->name,
                            ]);
                        }
                    }
                }
            });

        return $updated;
    }
}
