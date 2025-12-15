<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Delivery\AddressOutsideDeliveryZoneException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\CancelOrderRequest;
use App\Http\Requests\Api\V1\Order\CreateOrderRequest;
use App\Http\Requests\Api\V1\Order\StoreOrderReviewRequest;
use App\Http\Resources\Api\V1\Order\OrderResource;
use App\Http\Resources\Api\V1\Order\OrderReviewResource;
use App\Http\Resources\Api\V1\Order\OrderStatusResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderReview;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

/**
 * Order Management Controller
 *
 * This controller handles order creation, tracking, and management.
 *
 * **Order Status Flow:**
 * - **Pickup orders:** pending → confirmed → preparing → ready → completed
 * - **Delivery orders:** pending → confirmed → preparing → ready → out_for_delivery → delivered → completed
 * - **Cancellation:** Orders can be cancelled from pending or confirmed status
 * - **Available statuses:** pending, confirmed, preparing, ready, out_for_delivery, delivered, completed, cancelled
 *
 * **Important Notes:**
 * - scheduled_pickup_time must be at least 30 minutes from current time for pickup orders
 * - Orders can only be reviewed after completion (completed or delivered status)
 * - Points are earned upon order completion
 * - Reorder creates a new cart with the same items from a previous order
 */
class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    /**
     * Create order from cart
     *
     * @OA\Post(
     *     path="/api/v1/orders",
     *     tags={"Orders"},
     *     summary="Create order from cart",
     *     description="Creates a new order from the customer's active cart. Important validations: restaurant_id is required for pickup orders, delivery_address_id is required for delivery orders, scheduled_pickup_time must be at least 30 minutes from now for pickup orders, cart must not be empty and all items must be valid.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"restaurant_id", "service_type", "payment_method"},
     *
     *             @OA\Property(
     *                 property="restaurant_id",
     *                 type="integer",
     *                 description="Restaurant ID (required for pickup)",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="service_type",
     *                 type="string",
     *                 enum={"pickup", "delivery"},
     *                 description="Service type: pickup or delivery",
     *                 example="pickup"
     *             ),
     *             @OA\Property(
     *                 property="delivery_address_id",
     *                 type="integer",
     *                 description="Customer address ID (required if service_type=delivery)",
     *                 example=1,
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="scheduled_pickup_time",
     *                 type="string",
     *                 format="date-time",
     *                 description="Scheduled pickup time (optional, must be >= 30 min from now for pickup)",
     *                 example="2025-12-15T15:30:00Z",
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="payment_method",
     *                 type="string",
     *                 enum={"cash", "card", "online"},
     *                 description="Payment method",
     *                 example="cash"
     *             ),
     *             @OA\Property(
     *                 property="nit_id",
     *                 type="integer",
     *                 description="Customer NIT ID for invoice (optional)",
     *                 example=1,
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="notes",
     *                 type="string",
     *                 description="Order notes (max 500 chars)",
     *                 example="Sin cebolla, extra tomate",
     *                 nullable=true
     *             ),
     *             @OA\Property(
     *                 property="points_to_redeem",
     *                 type="integer",
     *                 description="Loyalty points to redeem (optional)",
     *                 example=100,
     *                 nullable=true
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Order details",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-20251215-0001"),
     *                 @OA\Property(property="status", type="string", enum={"pending","confirmed","preparing","ready","out_for_delivery","delivered","completed","cancelled"}, example="pending"),
     *                 @OA\Property(property="service_type", type="string", enum={"pickup","delivery"}, example="pickup"),
     *                 @OA\Property(property="zone", type="string", enum={"capital","interior"}, example="capital"),
     *                 @OA\Property(property="restaurant", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Subway Pradera Concepción"),
     *                     @OA\Property(property="address", type="string", example="Pradera Concepción, Zona 14")
     *                 ),
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="subtotal", type="number", format="float", example=125.00),
     *                     @OA\Property(property="discount_total", type="number", format="float", example=0.00),
     *                     @OA\Property(property="delivery_fee", type="number", format="float", example=15.00),
     *                     @OA\Property(property="tax", type="number", format="float", example=0.00),
     *                     @OA\Property(property="total", type="number", format="float", example=140.00)
     *                 ),
     *                 @OA\Property(property="payment", type="object",
     *                     @OA\Property(property="method", type="string", example="cash"),
     *                     @OA\Property(property="status", type="string", example="pending")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Orden creada exitosamente")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="La hora de recogida debe ser al menos 30 minutos desde ahora."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="scheduled_pickup_time", type="array",
     *
     *                     @OA\Items(type="string", example="La hora de recogida debe ser al menos 30 minutos desde ahora.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $customer = auth()->user();
        $cart = Cart::where('customer_id', $customer->id)
            ->active()
            ->notExpired()
            ->firstOrFail();

        try {
            $order = $this->orderService->createFromCart($cart, $request->validated());

            return response()->json([
                'data' => new OrderResource($order->load(['items', 'promotions', 'restaurant'])),
                'message' => 'Orden creada exitosamente',
            ], 201);
        } catch (AddressOutsideDeliveryZoneException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'ADDRESS_OUTSIDE_DELIVERY_ZONE',
                'data' => [
                    'latitude' => $e->lat,
                    'longitude' => $e->lng,
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get customer's order history
     *
     * @OA\Get(
     *     path="/api/v1/orders",
     *     tags={"Orders"},
     *     summary="Get order history",
     *     description="Returns paginated list of customer's orders.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Orders retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        $customer = auth()->user();
        $perPage = request()->input('per_page', 15);
        $status = request()->input('status');

        $query = Order::query()
            ->where('customer_id', $customer->id)
            ->with(['restaurant', 'items'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->paginate($perPage);

        return response()->json([
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Get active orders
     *
     * @OA\Get(
     *     path="/api/v1/orders/active",
     *     tags={"Orders"},
     *     summary="Get active orders",
     *     description="Returns customer's active orders (not completed or cancelled).",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Active orders retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function active(): JsonResponse
    {
        $customer = auth()->user();

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->active()
            ->with(['restaurant', 'items'])
            ->latest()
            ->get();

        return response()->json([
            'data' => OrderResource::collection($orders),
        ]);
    }

    /**
     * Get order details
     *
     * @OA\Get(
     *     path="/api/v1/orders/{order}",
     *     tags={"Orders"},
     *     summary="Get order details",
     *     description="Returns detailed information about a specific order.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Order retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Order does not belong to customer"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function show(Order $order): JsonResponse
    {
        $customer = auth()->user();

        if ($order->customer_id !== $customer->id) {
            return response()->json([
                'message' => 'No tienes acceso a esta orden',
            ], 403);
        }

        $order->load(['restaurant', 'items', 'promotions']);

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Track order status
     *
     * @OA\Get(
     *     path="/api/v1/orders/{order}/track",
     *     tags={"Orders"},
     *     summary="Track order",
     *     description="Returns current order status and complete status history with timestamps. Status Flow - Pickup: pending to confirmed to preparing to ready to completed. Delivery: pending to confirmed to preparing to ready to out_for_delivery to delivered to completed.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Order tracking retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order_number", type="string", example="ORD-20251215-0001"),
     *                 @OA\Property(property="current_status", type="string", enum={"pending","confirmed","preparing","ready","out_for_delivery","delivered","completed","cancelled"}, example="preparing"),
     *                 @OA\Property(property="restaurant", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Subway Pradera Concepción")
     *                 ),
     *                 @OA\Property(property="service_type", type="string", enum={"pickup","delivery"}, example="pickup"),
     *                 @OA\Property(property="estimated_ready_at", type="string", format="date-time", nullable=true, example="2025-12-15T15:30:00Z"),
     *                 @OA\Property(property="ready_at", type="string", format="date-time", nullable=true, example="2025-12-15T15:25:00Z"),
     *                 @OA\Property(property="delivered_at", type="string", format="date-time", nullable=true, example=null),
     *                 @OA\Property(property="status_history", type="array",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="status", type="string", example="preparing"),
     *                         @OA\Property(property="previous_status", type="string", example="confirmed"),
     *                         @OA\Property(property="changed_by", type="string", example="restaurant"),
     *                         @OA\Property(property="notes", type="string", nullable=true, example=null),
     *                         @OA\Property(property="timestamp", type="string", format="date-time", example="2025-12-15T15:10:00Z")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Order does not belong to customer"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function track(Order $order): JsonResponse
    {
        $customer = auth()->user();

        if ($order->customer_id !== $customer->id) {
            return response()->json([
                'message' => 'No tienes acceso a esta orden',
            ], 403);
        }

        $order->load(['restaurant', 'statusHistory']);

        return response()->json([
            'data' => [
                'order_number' => $order->order_number,
                'current_status' => $order->status,
                'restaurant' => [
                    'id' => $order->restaurant->id,
                    'name' => $order->restaurant->name,
                ],
                'service_type' => $order->service_type,
                'estimated_ready_at' => $order->estimated_ready_at?->toIso8601String(),
                'ready_at' => $order->ready_at?->toIso8601String(),
                'delivered_at' => $order->delivered_at?->toIso8601String(),
                'status_history' => OrderStatusResource::collection($order->statusHistory),
            ],
        ]);
    }

    /**
     * Cancel order
     *
     * @OA\Post(
     *     path="/api/v1/orders/{order}/cancel",
     *     tags={"Orders"},
     *     summary="Cancel order",
     *     description="Cancels an order if allowed by its current status.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="reason", type="string", example="Cambié de opinión")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Order cancelled successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Orden cancelada exitosamente")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Order does not belong to customer"),
     *     @OA\Response(response=422, description="Cannot cancel order in current status")
     * )
     */
    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        $customer = auth()->user();

        if ($order->customer_id !== $customer->id) {
            return response()->json([
                'message' => 'No tienes acceso a esta orden',
            ], 403);
        }

        try {
            $order = $this->orderService->cancel($order, $request->validated()['reason']);

            return response()->json([
                'data' => new OrderResource($order->load(['restaurant', 'items'])),
                'message' => 'Orden cancelada exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reorder
     *
     * @OA\Post(
     *     path="/api/v1/orders/{order}/reorder",
     *     tags={"Orders"},
     *     summary="Reorder",
     *     description="Creates a new cart with the same items from a previous order.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cart created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Carrito creado exitosamente")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Order does not belong to customer"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function reorder(Order $order): JsonResponse
    {
        $customer = auth()->user();

        if ($order->customer_id !== $customer->id) {
            return response()->json([
                'message' => 'No tienes acceso a esta orden',
            ], 403);
        }

        try {
            $cart = $this->orderService->reorder($order, $customer);

            return response()->json([
                'data' => [
                    'cart_id' => $cart->id,
                    'items_count' => $cart->items()->count(),
                ],
                'message' => 'Carrito creado exitosamente con los items de la orden',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create a review for an order
     *
     * @OA\Post(
     *     path="/api/v1/orders/{order}/review",
     *     tags={"Orders"},
     *     summary="Create order review",
     *     description="Creates a review for a completed order. Only allowed for completed/delivered orders.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="overall_rating", type="integer", minimum=1, maximum=5, example=5),
     *             @OA\Property(property="quality_rating", type="integer", minimum=1, maximum=5, example=4),
     *             @OA\Property(property="speed_rating", type="integer", minimum=1, maximum=5, example=5),
     *             @OA\Property(property="service_rating", type="integer", minimum=1, maximum=5, example=4),
     *             @OA\Property(property="comment", type="string", example="Muy buena comida!")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Review created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Gracias por tu calificación")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Order does not belong to customer"),
     *     @OA\Response(response=422, description="Validation error or order cannot be reviewed")
     * )
     */
    public function review(StoreOrderReviewRequest $request, Order $order): JsonResponse
    {
        $customer = auth()->user();

        if ($order->customer_id !== $customer->id) {
            return response()->json([
                'message' => 'No tienes acceso a esta orden',
            ], 403);
        }

        if (! in_array($order->status, ['completed', 'delivered'])) {
            return response()->json([
                'message' => 'Solo puedes calificar órdenes completadas',
            ], 422);
        }

        if ($order->review()->exists()) {
            return response()->json([
                'message' => 'Ya calificaste esta orden',
            ], 422);
        }

        $review = OrderReview::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'restaurant_id' => $order->restaurant_id,
            ...$request->validated(),
        ]);

        return response()->json([
            'data' => new OrderReviewResource($review),
            'message' => 'Gracias por tu calificación',
        ], 201);
    }
}
