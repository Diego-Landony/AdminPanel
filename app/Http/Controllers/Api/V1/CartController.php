<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cart\AddCartItemRequest;
use App\Http\Requests\Api\V1\Cart\ApplyPromotionRequest;
use App\Http\Requests\Api\V1\Cart\SetDeliveryAddressRequest;
use App\Http\Requests\Api\V1\Cart\UpdateCartItemRequest;
use App\Http\Requests\Api\V1\Cart\UpdateCartServiceTypeRequest;
use App\Http\Resources\Api\V1\Cart\CartItemResource;
use App\Http\Resources\Api\V1\CustomerAddressResource;
use App\Models\CartItem;
use App\Models\CustomerAddress;
use App\Models\Restaurant;
use App\Services\CartService;
use App\Services\DeliveryValidationService;
use App\Services\PromotionApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected PromotionApplicationService $promotionService,
        protected DeliveryValidationService $deliveryValidation
    ) {}

    /**
     * Get current cart with items and summary.
     *
     * @OA\Get(
     *     path="/api/v1/cart",
     *     tags={"Cart"},
     *     summary="Get current cart",
     *     description="Returns the current cart with items, totals and summary.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cart retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request): JsonResponse
    {
        $customer = auth()->user();
        $cart = $this->cartService->getOrCreateCart($customer);
        $cart->load(['restaurant', 'items.product', 'items.variant', 'items.combo']);

        $summary = $this->cartService->getCartSummary($cart);
        $validation = $this->cartService->validateCart($cart);

        return response()->json([
            'data' => [
                'id' => $cart->id,
                'restaurant' => $cart->restaurant,
                'service_type' => $cart->service_type,
                'zone' => $cart->zone,
                'items' => CartItemResource::collection($cart->items),
                'summary' => [
                    'subtotal' => number_format($summary['subtotal'], 2, '.', ''),
                    'promotions_applied' => [],
                    'total_discount' => number_format($summary['discounts'], 2, '.', ''),
                    'delivery_fee' => number_format($summary['delivery_fee'], 2, '.', ''),
                    'total' => number_format($summary['total'], 2, '.', ''),
                ],
                'can_checkout' => $validation['valid'] && ! $cart->isEmpty(),
                'validation_messages' => $validation['messages'],
                'expires_at' => $cart->expires_at,
                'created_at' => $cart->created_at,
            ],
        ]);
    }

    /**
     * Add item to cart.
     *
     * @OA\Post(
     *     path="/api/v1/cart/items",
     *     tags={"Cart"},
     *     summary="Add item to cart",
     *     description="Adds a product or combo to the cart with options and quantity.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="combo_id", type="integer", example=null),
     *             @OA\Property(property="variant_id", type="integer", example=5),
     *             @OA\Property(property="quantity", type="integer", example=2),
     *             @OA\Property(property="selected_options", type="array",
     *
     *                 @OA\Items(
     *
     *                     @OA\Property(property="section_id", type="integer", example=1),
     *                     @OA\Property(property="option_id", type="integer", example=3)
     *                 )
     *             ),
     *             @OA\Property(property="combo_selections", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="notes", type="string", example="Sin cebolla")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Item added successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="item", type="object"),
     *                 @OA\Property(property="message", type="string", example="Item agregado al carrito")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $customer = auth()->user();
        $cart = $this->cartService->getOrCreateCart($customer);
        $validated = $request->validated();

        try {
            $item = $this->cartService->addItem($cart, $validated);
            $item->load(['product', 'variant', 'combo']);

            return response()->json([
                'data' => [
                    'item' => new CartItemResource($item),
                    'message' => 'Item agregado al carrito',
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update cart item quantity or options.
     *
     * @OA\Put(
     *     path="/api/v1/cart/items/{id}",
     *     tags={"Cart"},
     *     summary="Update cart item",
     *     description="Updates quantity, options, or notes for a cart item.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Cart item ID",
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
     *             @OA\Property(property="quantity", type="integer", example=3),
     *             @OA\Property(property="selected_options", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="combo_selections", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="notes", type="string", example="Extra queso")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Item updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="item", type="object"),
     *                 @OA\Property(property="message", type="string", example="Item actualizado")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Cart item not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateItem(UpdateCartItemRequest $request, int $id): JsonResponse
    {
        $customer = auth()->user();
        $cart = $this->cartService->getOrCreateCart($customer);

        $item = CartItem::where('id', $id)
            ->where('cart_id', $cart->id)
            ->first();

        if (! $item) {
            return response()->json([
                'message' => 'Item no encontrado en el carrito',
            ], 404);
        }

        $validated = $request->validated();
        $item = $this->cartService->updateItem($item, $validated);
        $item->load(['product', 'variant', 'combo']);

        return response()->json([
            'data' => [
                'item' => new CartItemResource($item),
                'message' => 'Item actualizado',
            ],
        ]);
    }

    /**
     * Remove item from cart.
     *
     * @OA\Delete(
     *     path="/api/v1/cart/items/{id}",
     *     tags={"Cart"},
     *     summary="Remove cart item",
     *     description="Removes an item from the cart.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Cart item ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Item removed successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="Item eliminado del carrito")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Cart item not found")
     * )
     */
    public function removeItem(Request $request, int $id): JsonResponse
    {
        $customer = auth()->user();
        $cart = $this->cartService->getOrCreateCart($customer);

        $item = CartItem::where('id', $id)
            ->where('cart_id', $cart->id)
            ->first();

        if (! $item) {
            return response()->json([
                'message' => 'Item no encontrado en el carrito',
            ], 404);
        }

        $this->cartService->removeItem($item);

        return response()->json([
            'data' => [
                'message' => 'Item eliminado del carrito',
            ],
        ]);
    }

    /**
     * Empty cart.
     *
     * @OA\Delete(
     *     path="/api/v1/cart",
     *     tags={"Cart"},
     *     summary="Clear cart",
     *     description="Removes all items from the cart.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cart cleared successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="Carrito vaciado")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function clear(Request $request): JsonResponse
    {
        $customer = auth()->user();
        $cart = $this->cartService->getOrCreateCart($customer);

        $this->cartService->clearCart($cart);

        return response()->json([
            'data' => [
                'message' => 'Carrito vaciado',
            ],
        ]);
    }

    /**
     * Update cart restaurant.
     *
     * @OA\Put(
     *     path="/api/v1/cart/restaurant",
     *     tags={"Cart"},
     *     summary="Update cart restaurant",
     *     description="Changes the restaurant for the cart.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="restaurant_id", type="integer", example=2)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Restaurant updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="Restaurante actualizado")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateRestaurant(Request $request): JsonResponse
    {
        $customer = auth()->user();

        $validated = $request->validate([
            'restaurant_id' => ['required', 'integer', 'exists:restaurants,id'],
        ]);

        $cart = $this->cartService->getOrCreateCart($customer);
        $restaurant = Restaurant::findOrFail($validated['restaurant_id']);

        $this->cartService->updateRestaurant($cart, $restaurant);

        return response()->json([
            'data' => [
                'message' => 'Restaurante actualizado',
            ],
        ]);
    }

    /**
     * Update cart service type and zone.
     *
     * @OA\Put(
     *     path="/api/v1/cart/service-type",
     *     tags={"Cart"},
     *     summary="Update service type",
     *     description="Changes the service type (pickup/delivery) and zone (capital/interior).",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="service_type", type="string", enum={"pickup", "delivery"}, example="delivery"),
     *             @OA\Property(property="zone", type="string", enum={"capital", "interior"}, example="capital")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service type updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateServiceType(UpdateCartServiceTypeRequest $request): JsonResponse
    {
        $customer = auth()->user();
        $validated = $request->validated();

        $cart = $this->cartService->getOrCreateCart($customer);
        $cart = $this->cartService->updateServiceType($cart, $validated['service_type'], $validated['zone']);
        $cart->load(['restaurant', 'items.product', 'items.variant', 'items.combo']);

        $summary = $this->cartService->getCartSummary($cart);

        return response()->json([
            'data' => [
                'id' => $cart->id,
                'restaurant' => $cart->restaurant,
                'service_type' => $cart->service_type,
                'zone' => $cart->zone,
                'items' => CartItemResource::collection($cart->items),
                'summary' => [
                    'subtotal' => number_format($summary['subtotal'], 2, '.', ''),
                    'total' => number_format($summary['total'], 2, '.', ''),
                ],
            ],
        ]);
    }

    /**
     * Validate cart availability.
     *
     * @OA\Post(
     *     path="/api/v1/cart/validate",
     *     tags={"Cart"},
     *     summary="Validate cart",
     *     description="Checks if all cart items are available and valid for checkout.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cart validation result",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="is_valid", type="boolean", example=true),
     *                 @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function validate(Request $request): JsonResponse
    {
        $customer = auth()->user();
        $cart = $this->cartService->getOrCreateCart($customer);
        $cart->load(['items.product', 'items.variant', 'items.combo']);

        $validation = $this->cartService->validateCart($cart);

        return response()->json([
            'data' => [
                'is_valid' => $validation['valid'],
                'errors' => $validation['messages'],
            ],
        ]);
    }

    /**
     * Apply promotion code to cart.
     *
     * @OA\Post(
     *     path="/api/v1/cart/apply-promotion",
     *     tags={"Cart"},
     *     summary="Apply promotion",
     *     description="Applies a promotion code to the cart.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="code", type="string", example="PROMO2024")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Promotion applied successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="Promoción aplicada")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Invalid or expired promotion code")
     * )
     */
    public function applyPromotion(ApplyPromotionRequest $request): JsonResponse
    {
        $customer = auth()->user();
        $validated = $request->validated();

        $cart = $this->cartService->getOrCreateCart($customer);

        try {
            $result = $this->promotionService->applyPromoCode($cart, $validated['code']);

            return response()->json([
                'data' => [
                    'applied' => $result['applied'],
                    'discount' => $result['discount'] ?? 0,
                    'message' => 'Promoción aplicada',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * PUT /api/v1/cart/delivery-address
     * Asigna dirección de entrega y auto-asigna restaurante basado en geocerca
     */
    public function setDeliveryAddress(SetDeliveryAddressRequest $request): JsonResponse
    {
        $customer = auth()->user();
        $cart = $this->cartService->getOrCreateCart($customer);

        $address = CustomerAddress::where('id', $request->delivery_address_id)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $result = $this->deliveryValidation->validateDeliveryAddress($address);

        if (! $result->isValid) {
            return response()->json([
                'message' => $result->errorMessage,
                'error_code' => 'ADDRESS_OUTSIDE_DELIVERY_ZONE',
                'data' => [
                    'nearest_pickup_locations' => $result->nearbyPickupRestaurants,
                ],
            ], 422);
        }

        $cart = $this->cartService->updateDeliveryAddress(
            $cart,
            $address,
            $result->restaurant,
            $result->zone
        );

        return response()->json([
            'data' => [
                'delivery_address' => new CustomerAddressResource($address),
                'assigned_restaurant' => [
                    'id' => $result->restaurant->id,
                    'name' => $result->restaurant->name,
                ],
                'zone' => $result->zone,
                'prices_updated' => true,
            ],
            'message' => 'Dirección de entrega asignada exitosamente',
        ]);
    }
}
