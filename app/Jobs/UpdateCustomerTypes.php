<?php

namespace App\Jobs;

use App\Events\CustomerTypeDowngraded;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Notifications\CustomerTypeDowngradedNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job para actualizar tipos de cliente basado en puntos ganados
 * en los últimos 6 meses (ventana móvil).
 *
 * Lógica:
 * - Suma solo los puntos positivos (earned) de los últimos 6 meses
 * - Asigna el tipo de cliente más alto que califique según los puntos
 * - Si no califica para ningún tipo, asigna el tipo default (Regular)
 * - Esto permite tanto upgrades como downgrades automáticos
 */
class UpdateCustomerTypes implements ShouldQueue
{
    use Queueable;

    /**
     * Número de meses para la ventana de cálculo
     */
    protected int $windowMonths = 6;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $windowMonths = null)
    {
        if ($windowMonths !== null) {
            $this->windowMonths = $windowMonths;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startDate = Carbon::now()->subMonths($this->windowMonths)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        Log::info('UpdateCustomerTypes: Iniciando actualización de tipos de cliente', [
            'window_start' => $startDate->toDateTimeString(),
            'window_end' => $endDate->toDateTimeString(),
        ]);

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
        // Similar al sistema legacy pero adaptado a la nueva estructura
        $updated = $this->updateCustomerTypesInBatches($startDate, $endDate, $customerTypes, $defaultType);

        Log::info('UpdateCustomerTypes: Actualización completada', [
            'customers_updated' => $updated,
        ]);
    }

    /**
     * Actualiza los tipos de cliente en lotes para mejor rendimiento
     */
    protected function updateCustomerTypesInBatches(
        Carbon $startDate,
        Carbon $endDate,
        $customerTypes,
        CustomerType $defaultType
    ): int {
        $updated = 0;

        // Obtener suma de puntos ganados por cliente en la ventana de tiempo
        $customerPoints = DB::table('customer_points_transactions')
            ->select('customer_id', DB::raw('SUM(points) as total_points'))
            ->where('points', '>', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        // Indexar tipos por ID para búsqueda rápida
        $typesById = $customerTypes->keyBy('id');
        $typesById[$defaultType->id] = $defaultType;

        // Procesar clientes en lotes
        Customer::query()
            ->select(['id', 'customer_type_id', 'first_name', 'email', 'email_offers_enabled'])
            ->chunk(500, function ($customers) use ($customerPoints, $customerTypes, $defaultType, $typesById, &$updated) {
                foreach ($customers as $customer) {
                    $earnedPoints = $customerPoints->get($customer->id)?->total_points ?? 0;

                    // Encontrar el tipo apropiado (el más alto que califique)
                    $newTypeId = $defaultType->id;
                    foreach ($customerTypes as $type) {
                        if ($earnedPoints >= $type->points_required) {
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

                        // Detectar si es un downgrade (nuevo tipo tiene menos puntos requeridos)
                        if ($previousType && $newType && $newType->points_required < $previousType->points_required) {
                            // Cargar el cliente completo para la notificación
                            $fullCustomer = Customer::find($customer->id);

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
                    }
                }
            });

        return $updated;
    }
}
