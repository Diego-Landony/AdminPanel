<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cart\AddCartItemRequest;
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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected DeliveryValidationService $deliveryValidation
    ) {}

    /**
     * Get current cart with items and summary.
     *
     * @OA\Get(
     *     path="/api/v1/cart",
     *     tags={"Cart"},
     *     summary="Get current cart",
     *     description="Returns the current cart with items, totals and summary. Each item includes discount information for displaying strikethrough prices.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cart retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="service_type", type="string", enum={"pickup", "delivery"}),
     *                 @OA\Property(property="zone", type="string", enum={"capital", "interior"}),
     *                 @OA\Property(property="items", type="array",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="type", type="string", enum={"product", "combo"}),
     *                         @OA\Property(property="quantity", type="integer"),
     *                         @OA\Property(property="unit_price", type="number", format="float"),
     *                         @OA\Property(property="subtotal", type="number", format="float", description="Precio base sin descuento"),
     *                         @OA\Property(property="discount_amount", type="number", format="float", description="Monto del descuento aplicado"),
     *                         @OA\Property(property="final_price", type="number", format="float", description="Precio final despues del descuento"),
     *                         @OA\Property(property="is_daily_special", type="boolean", description="Si aplica Sub del Dia"),
     *                         @OA\Property(property="applied_promotion", type="object", nullable=true,
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string"),
     *                             @OA\Property(property="type", type="string", enum={"two_for_one", "percentage_discount", "bundle_special"}),
     *                             @OA\Property(property="value", type="string", example="2x1")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="subtotal", type="string"),
     *                     @OA\Property(property="total_discount", type="string"),
     *                     @OA\Property(property="total", type="string"),
     *                     @OA\Property(property="promotions_applied", type="array", @OA\Items(type="object"))
     *                 ),
     *                 @OA\Property(property="can_checkout", type="boolean")
     *             )
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

        // Agregar información de descuentos a cada item
        $itemDiscounts = $summary['item_discounts'] ?? [];
        $itemsWithDiscounts = $cart->items->map(function ($item) use ($itemDiscounts) {
            $discount = $itemDiscounts[$item->id] ?? [
                'discount_amount' => 0.0,
                'original_price' => (float) $item->subtotal,
                'final_price' => (float) $item->subtotal,
                'is_daily_special' => false,
                'applied_promotion' => null,
            ];
            $item->discount_info = $discount;

            return $item;
        });

        return response()->json([
            'data' => [
                'id' => $cart->id,
                'restaurant' => $cart->restaurant,
                'service_type' => $cart->service_type,
                'zone' => $cart->zone,
                'items' => CartItemResource::collection($itemsWithDiscounts),
                'summary' => [
                    'subtotal' => number_format($summary['subtotal'], 2, '.', ''),
                    'promotions_applied' => $summary['promotions_applied'],
                    'total_discount' => number_format($summary['discounts'], 2, '.', ''),
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
     * Update cart restaurant (for pickup orders).
     *
     * @OA\Put(
     *     path="/api/v1/cart/restaurant",
     *     tags={"Cart"},
     *     summary="Select restaurant for pickup",
     *     description="Sets the restaurant for pickup orders. Automatically sets service_type to 'pickup', zone based on restaurant location, and recalculates all item prices.",
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
     *         description="Restaurant set successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="restaurant", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Subway Pradera")
     *                 ),
     *                 @OA\Property(property="service_type", type="string", example="pickup"),
     *                 @OA\Property(property="zone", type="string", enum={"capital", "interior"}, example="capital"),
     *                 @OA\Property(property="prices_updated", type="boolean", example=true)
     *             ),
     *             @OA\Property(property="message", type="string", example="Restaurante seleccionado para pickup")
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

        $cart = $this->cartService->updateRestaurant($cart, $restaurant);

        return response()->json([
            'data' => [
                'restaurant' => [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                ],
                'service_type' => $cart->service_type,
                'zone' => $cart->zone,
                'prices_updated' => true,
            ],
            'message' => 'Restaurante seleccionado para pickup',
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
     * Set delivery address for cart.
     *
     * @OA\Put(
     *     path="/api/v1/cart/delivery-address",
     *     tags={"Cart"},
     *     summary="Set delivery address",
     *     description="Assigns delivery address and automatically assigns restaurant based on geofence validation. If address is outside delivery zone, returns nearest pickup locations.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="delivery_address_id",
     *                 type="integer",
     *                 description="ID of customer's saved address",
     *                 example=1
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Delivery address set successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="delivery_address", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="street_address", type="string", example="5ta Avenida 10-50, Zona 14"),
     *                     @OA\Property(property="city", type="string", example="Guatemala")
     *                 ),
     *                 @OA\Property(property="assigned_restaurant", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Subway Pradera Concepción")
     *                 ),
     *                 @OA\Property(property="zone", type="string", enum={"capital", "interior"}, example="capital"),
     *                 @OA\Property(property="prices_updated", type="boolean", example=true)
     *             ),
     *             @OA\Property(property="message", type="string", example="Dirección de entrega asignada exitosamente")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Address outside delivery zone",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="La dirección está fuera de la zona de entrega"),
     *             @OA\Property(property="error_code", type="string", example="ADDRESS_OUTSIDE_DELIVERY_ZONE"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="nearest_pickup_locations", type="array",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="name", type="string", example="Subway Miraflores"),
     *                         @OA\Property(property="address", type="string", example="Calzada Roosevelt 22-43, Zona 11"),
     *                         @OA\Property(property="distance_km", type="number", format="float", example=2.5)
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
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
