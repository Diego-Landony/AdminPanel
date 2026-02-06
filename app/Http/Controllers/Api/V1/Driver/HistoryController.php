<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Driver\DriverHistoryDetailResource;
use App\Http\Resources\Api\V1\Driver\DriverHistoryResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    /**
     * List delivery history for the authenticated driver.
     *
     * GET /api/v1/driver/history
     *
     * Query params:
     * - from: date (optional) - Start date filter (Y-m-d)
     * - to: date (optional) - End date filter (Y-m-d)
     * - per_page: int (optional, default 15) - Items per page
     * - page: int (optional, default 1) - Page number
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $driver = auth('driver')->user();
        $perPage = $request->integer('per_page', 15);

        $query = Order::query()
            ->completedByDriver($driver->id)
            ->with(['customer', 'restaurant', 'items']);

        // Apply date filters
        if ($request->filled('from')) {
            $query->whereDate('delivered_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('delivered_at', '<=', $request->input('to'));
        }

        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => DriverHistoryResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
            'message' => 'Historial de entregas obtenido correctamente.',
        ]);
    }

    /**
     * Show details of a past delivery.
     *
     * GET /api/v1/driver/history/{order}
     */
    public function show(Order $order): JsonResponse
    {
        $driver = auth('driver')->user();

        // Verify the order belongs to this driver
        if ($order->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes acceso a esta orden.',
                'error_code' => 'ORDER_NOT_ASSIGNED',
            ], 403);
        }

        $order->load(['customer', 'restaurant', 'items', 'deliveryAddress']);

        return response()->json([
            'success' => true,
            'data' => DriverHistoryDetailResource::make($order),
            'message' => 'Detalle de entrega obtenido correctamente.',
        ]);
    }
}
