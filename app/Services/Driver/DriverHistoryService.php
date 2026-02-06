<?php

namespace App\Services\Driver;

use App\Models\Driver;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DriverHistoryService
{
    /**
     * Maximum items per page for pagination.
     */
    private const MAX_PER_PAGE = 50;

    /**
     * Default items per page for pagination.
     */
    private const DEFAULT_PER_PAGE = 15;

    /**
     * Obtiene el historial de entregas del driver paginado.
     *
     * @param  Driver  $driver  El driver del cual obtener el historial
     * @param  array{
     *     from?: string,
     *     to?: string,
     *     per_page?: int
     * }  $filters  Filtros opcionales para el historial
     * @return LengthAwarePaginator<Order>
     */
    public function getHistory(Driver $driver, array $filters = []): LengthAwarePaginator
    {
        $query = Order::query()
            ->assignedToDriver($driver->id)
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->with(['customer', 'restaurant', 'items', 'deliveryAddress']);

        // Aplicar filtro de fecha inicial
        if (isset($filters['from']) && $this->isValidDate($filters['from'])) {
            $query->where('delivered_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        // Aplicar filtro de fecha final
        if (isset($filters['to']) && $this->isValidDate($filters['to'])) {
            $query->where('delivered_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        // Ordenar por fecha de entrega descendente
        $query->orderByDesc('delivered_at');

        // Calcular items por página
        $perPage = $this->calculatePerPage($filters['per_page'] ?? null);

        return $query->paginate($perPage);
    }

    /**
     * Obtiene el detalle de una entrega específica del historial.
     *
     * Verifica que la orden pertenezca al driver y esté en estado
     * delivered o completed antes de retornarla.
     *
     * @param  Driver  $driver  El driver propietario del historial
     * @param  Order  $order  La orden a obtener
     * @return Order|null La orden con relaciones cargadas o null si no corresponde
     */
    public function getDeliveryDetail(Driver $driver, Order $order): ?Order
    {
        // Verificar que la orden pertenece al driver
        if ($order->driver_id !== $driver->id) {
            return null;
        }

        // Verificar que está en estado delivered o completed
        if (! in_array($order->status, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])) {
            return null;
        }

        // Retornar con relaciones cargadas
        return $order->load([
            'customer',
            'restaurant',
            'items.product',
            'deliveryAddress',
            'statusHistory',
        ]);
    }

    /**
     * Valida si un string es una fecha válida.
     *
     * @param  string  $date  La fecha a validar
     */
    private function isValidDate(string $date): bool
    {
        try {
            Carbon::parse($date);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Calcula el número de items por página respetando límites.
     *
     * @param  int|null  $requested  El número solicitado por el cliente
     */
    private function calculatePerPage(?int $requested): int
    {
        if ($requested === null || $requested < 1) {
            return self::DEFAULT_PER_PAGE;
        }

        return min($requested, self::MAX_PER_PAGE);
    }
}
