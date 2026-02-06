<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Exceptions\DeliveryLocationException;
use App\Exceptions\DriverHasActiveOrderException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\AcceptOrderRequest;
use App\Http\Requests\Api\V1\Driver\DeliverOrderRequest;
use App\Http\Resources\Api\V1\Driver\DriverOrderDetailResource;
use App\Http\Resources\Api\V1\Driver\DriverOrderResource;
use App\Models\Order;
use App\Services\Driver\DriverOrderService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(
        protected DriverOrderService $orderService
    ) {}

    /**
     * List pending orders assigned to the driver.
     *
     * GET /api/v1/driver/orders/pending
     */
    public function pending(): JsonResponse
    {
        $driver = auth('driver')->user();
        $orders = $this->orderService->getPendingOrders($driver);

        return response()->json([
            'success' => true,
            'data' => DriverOrderResource::collection($orders),
            'message' => 'Órdenes pendientes obtenidas correctamente.',
        ]);
    }

    /**
     * Get the active order for the driver.
     *
     * GET /api/v1/driver/orders/active
     */
    public function active(): JsonResponse
    {
        $driver = auth('driver')->user();
        $order = $this->orderService->getActiveOrder($driver);

        return response()->json([
            'success' => true,
            'data' => $order ? DriverOrderDetailResource::make($order) : null,
            'message' => $order
                ? 'Orden activa obtenida correctamente.'
                : 'No tienes ninguna orden activa.',
        ]);
    }

    /**
     * Show order details.
     *
     * GET /api/v1/driver/orders/{order}
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['customer', 'restaurant', 'items', 'deliveryAddress']);

        return response()->json([
            'success' => true,
            'data' => DriverOrderDetailResource::make($order),
            'message' => 'Detalles de la orden obtenidos correctamente.',
        ]);
    }

    /**
     * Accept an assigned order.
     *
     * POST /api/v1/driver/orders/{order}/accept
     */
    public function accept(AcceptOrderRequest $request, Order $order): JsonResponse
    {
        $driver = auth('driver')->user();

        // Update driver location before accepting
        $driver->updateLocation(
            $request->validated('latitude'),
            $request->validated('longitude')
        );

        try {
            $order = $this->orderService->acceptOrder($driver, $order);

            return response()->json([
                'success' => true,
                'data' => DriverOrderDetailResource::make($order),
                'message' => 'Orden aceptada. Ve al restaurante a recogerla.',
            ]);
        } catch (DriverHasActiveOrderException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'DRIVER_HAS_ACTIVE_ORDER',
                'active_order_id' => $e->getActiveOrder()->id,
            ], 409);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'INVALID_ORDER_STATE',
            ], 422);
        }
    }

    /**
     * Complete a delivery.
     *
     * POST /api/v1/driver/orders/{order}/deliver
     */
    public function deliver(DeliverOrderRequest $request, Order $order): JsonResponse
    {
        $driver = auth('driver')->user();

        try {
            $order = $this->orderService->completeDelivery(
                $driver,
                $order,
                $request->validated('latitude'),
                $request->validated('longitude'),
                $request->validated('notes')
            );

            return response()->json([
                'success' => true,
                'data' => DriverOrderDetailResource::make($order),
                'message' => 'Entrega completada exitosamente.',
            ]);
        } catch (DeliveryLocationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'OUT_OF_DELIVERY_RANGE',
                'details' => [
                    'current_distance' => $e->getCurrentDistance(),
                    'max_distance' => $e->getMaxDistance(),
                ],
            ], 422);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'INVALID_ORDER_STATE',
            ], 422);
        }
    }
}
