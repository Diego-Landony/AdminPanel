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
use App\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected DeliveryValidationService $deliveryValidation,
        protected PointsService $pointsService
    ) {}

    /**
     * Get current cart with items and summary.
     *
     * @OA\Get(
     *     path="/api/v1/cart",
     *     tags={"Cart"},
     *     summary="Get current cart",
     *     description="Returns the current cart with items, totals and summary. Each item includes discount information for displaying strikethrough prices. Promotion stacking rules: 2x1 always uses NORMAL price (not Sub del Día price). For odd quantities, leftover items use Sub del Día price. Example: 3 items = 2 in 2x1 (normal price) + 1 Sub del Día. The applied_promotion.value will show '2x1 + Sub del Día' when both apply.",
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
     *                 @OA\Property(property="restaurant_id", type="integer", nullable=true, example=2, description="ID del restaurante para mapeo directo"),
     *                 @OA\Property(property="restaurant", type="object", nullable=true, description="Restaurante asignado (pickup o delivery)",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Subway Pradera"),
     *                     @OA\Property(property="address", type="string", example="6ta Avenida 5-10, Zona 9"),
     *                     @OA\Property(property="price_location", type="string", enum={"capital", "interior"})
     *                 ),
     *                 @OA\Property(property="delivery_address_id", type="integer", nullable=true, example=1, description="ID de la direccion de entrega para mapeo directo"),
     *                 @OA\Property(property="delivery_address", type="object", nullable=true, description="Direccion de entrega (solo para delivery)",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="label", type="string", example="Casa"),
     *                     @OA\Property(property="address_line", type="string", example="5ta Avenida 10-50, Zona 14, Guatemala"),
     *                     @OA\Property(property="zone", type="string", enum={"capital", "interior"}, example="capital")
     *                 ),
     *                 @OA\Property(property="service_type", type="string", enum={"pickup", "delivery"}),
     *                 @OA\Property(property="zone", type="string", enum={"capital", "interior"}, description="Determinada automaticamente del restaurante o direccion"),
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
     *                         @OA\Property(property="applied_promotion", type="object", nullable=true, description="Promocion aplicada a este item (null si no tiene)",
     *                             @OA\Property(property="id", type="integer", example=2, description="ID de la promocion"),
     *                             @OA\Property(property="name", type="string", example="2x1 en Bebidas", description="Nombre de la promocion"),
     *                             @OA\Property(property="name_display", type="string", example="2x1 en Bebidas 2x1", description="Nombre formateado para mostrar en UI"),
     *                             @OA\Property(property="type", type="string", enum={"two_for_one", "percentage_discount", "daily_special", "bundle_special"}, example="two_for_one"),
     *                             @OA\Property(property="value", type="string", example="2x1", description="Valor del descuento: '2x1', '2x1 + Sub del Día', '15%', 'Q85.00'")
     *                         ),
     *                         @OA\Property(property="selected_options", type="array", description="Opciones seleccionadas del producto con nombres",
     *
     *                             @OA\Items(type="object",
     *
     *                                 @OA\Property(property="section_id", type="integer", example=1),
     *                                 @OA\Property(property="section_name", type="string", example="Pan", description="Nombre de la seccion"),
     *                                 @OA\Property(property="option_id", type="integer", example=2),
     *                                 @OA\Property(property="option_name", type="string", example="Pan integral", description="Nombre de la opcion"),
     *                                 @OA\Property(property="price", type="number", format="float", example=0)
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="subtotal", type="string", example="93.00", description="Suma de todos los items sin descuentos"),
     *                     @OA\Property(property="discount_total", type="string", example="20.00", description="Total de descuentos aplicados"),
     *                     @OA\Property(property="total", type="string", example="73.00", description="Total a pagar (subtotal - descuentos)"),
     *                     @OA\Property(property="promotions_applied", type="array", description="Lista de promociones aplicadas agrupadas",
     *
     *                         @OA\Items(type="object",
     *
     *                             @OA\Property(property="promotion_id", type="integer", example=2, description="ID de la promocion"),
     *                             @OA\Property(property="promotion_name", type="string", example="2x1 en Bebidas", description="Nombre de la promocion"),
     *                             @OA\Property(property="promotion_type", type="string", enum={"two_for_one", "percentage_discount", "daily_special", "bundle_special"}, example="two_for_one"),
     *                             @OA\Property(property="discount_amount", type="number", format="float", example=20.00, description="Monto total descontado por esta promocion"),
     *                             @OA\Property(property="items_affected", type="array", description="IDs de los items del carrito afectados",
     *
     *                                 @OA\Items(type="integer", example=101)
     *                             )
     *                         )
     *                     ),
     *
     *                     @OA\Property(property="points_to_earn", type="integer", description="Puntos que el cliente ganara al completar esta orden")
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
        $cart->load(['restaurant', 'deliveryAddress', 'items.product.category', 'items.variant', 'items.combo', 'items.cart']);

        $summary = $this->cartService->getCartSummary($cart);
        $validation = $this->cartService->validateCart($cart);

        // Calcular puntos a ganar
        $pointsToEarn = $this->pointsService->calculatePointsToEarn($summary['total'], $customer);

        // Validar monto mínimo del restaurante
        $minimumOrderAmount = (float) ($cart->restaurant?->minimum_order_amount ?? 0);
        $meetsMinimumOrder = $minimumOrderAmount <= 0 || $summary['total'] >= $minimumOrderAmount;

        if (! $meetsMinimumOrder && $cart->restaurant) {
            $validation['messages'][] = sprintf(
                'El monto mínimo de pedido para %s es Q%.2f. Faltan Q%.2f.',
                $cart->restaurant->name,
                $minimumOrderAmount,
                $minimumOrderAmount - $summary['total']
            );
            $validation['valid'] = false;
        }

        // Obtener información de disponibilidad del restaurante
        $restaurantAvailability = null;
        $canAcceptOrders = true;
        if ($cart->restaurant) {
            $serviceType = $cart->service_type ?? 'pickup';
            $restaurantAvailability = $cart->restaurant->getAvailabilityInfo($serviceType);
            $canAcceptOrders = $restaurantAvailability['can_accept_orders'];

            if (! $canAcceptOrders) {
                $validation['messages'][] = $restaurantAvailability['message'] ?? 'El restaurante no puede aceptar pedidos en este momento.';
                $validation['valid'] = false;
            }
        }

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
                'restaurant_id' => $cart->restaurant_id,
                'restaurant' => $cart->restaurant ? [
                    'id' => $cart->restaurant->id,
                    'name' => $cart->restaurant->name,
                    'address' => $cart->restaurant->address,
                    'price_location' => $cart->restaurant->price_location,
                    'minimum_order_amount' => (float) $cart->restaurant->minimum_order_amount,
                    'estimated_delivery_time' => $cart->restaurant->estimated_delivery_time,
                    'estimated_pickup_time' => $cart->restaurant->estimated_pickup_time ?? 15,
                ] : null,
                'delivery_address_id' => $cart->delivery_address_id,
                'delivery_address' => $cart->deliveryAddress ? [
                    'id' => $cart->deliveryAddress->id,
                    'label' => $cart->deliveryAddress->label,
                    'address_line' => $cart->deliveryAddress->address_line,
                    'zone' => $cart->deliveryAddress->zone,
                ] : null,
                'service_type' => $cart->service_type,
                'zone' => $cart->zone,
                'items' => CartItemResource::collection($itemsWithDiscounts),
                'summary' => [
                    'subtotal' => number_format($summary['subtotal'], 2, '.', ''),
                    'promotions_applied' => $summary['promotions_applied'],
                    'discount_total' => number_format($summary['discounts'], 2, '.', ''),
                    'total' => number_format($summary['total'], 2, '.', ''),
                    'points_to_earn' => $pointsToEarn,
                ],
                'can_checkout' => $validation['valid'] && ! $cart->isEmpty() && $meetsMinimumOrder && $canAcceptOrders,
                'meets_minimum_order' => $meetsMinimumOrder,
                'minimum_order_amount' => $minimumOrderAmount,
                'restaurant_availability' => $restaurantAvailability,
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
     *     description="Adds a product or combo to the cart with options and quantity. If an identical item already exists (same product/combo, variant, and options), the quantity is incremented instead of creating a duplicate. Notes are combined with ' | ' separator.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             oneOf={
     *
     *                 @OA\Schema(
     *                     title="Agregar Producto",
     *                     required={"product_id", "quantity"},
     *
     *                     @OA\Property(property="product_id", type="integer", example=1, description="ID del producto"),
     *                     @OA\Property(property="variant_id", type="integer", example=5, nullable=true, description="ID de la variante (requerido si el producto tiene variantes)"),
     *                     @OA\Property(property="quantity", type="integer", example=2, minimum=1, maximum=10),
     *                     @OA\Property(property="selected_options", type="array", description="Opciones seleccionadas del producto (vegetales, salsas, etc.)",
     *
     *                         @OA\Items(type="object",
     *
     *                             @OA\Property(property="section_id", type="integer", example=1),
     *                             @OA\Property(property="option_id", type="integer", example=3)
     *                         )
     *                     ),
     *                     @OA\Property(property="notes", type="string", example="Sin cebolla", maxLength=500)
     *                 ),
     *
     *                 @OA\Schema(
     *                     title="Agregar Combo",
     *                     required={"combo_id", "quantity"},
     *
     *                     @OA\Property(property="combo_id", type="integer", example=1, description="ID del combo"),
     *                     @OA\Property(property="quantity", type="integer", example=1, minimum=1, maximum=10),
     *                     @OA\Property(property="combo_selections", type="array", description="Selecciones para grupos de eleccion del combo",
     *
     *                         @OA\Items(type="object",
     *
     *                             @OA\Property(property="combo_item_id", type="integer", example=5, description="ID del combo_item que es choice_group"),
     *                             @OA\Property(property="selections", type="array",
     *
     *                                 @OA\Items(type="object",
     *
     *                                     @OA\Property(property="option_id", type="integer", example=10, description="ID de la opcion seleccionada (combo_item_option)"),
     *                                     @OA\Property(property="selected_options", type="array", nullable=true, description="Opciones del producto (vegetales, etc.)",
     *
     *                                         @OA\Items(type="object",
     *
     *                                             @OA\Property(property="section_id", type="integer"),
     *                                             @OA\Property(property="option_id", type="integer")
     *                                         )
     *                                     )
     *                                 )
     *                             )
     *                         )
     *                     ),
     *                     @OA\Property(property="notes", type="string", example="Sin cebolla en el sub", maxLength=500)
     *                 )
     *             }
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
     *                 @OA\Property(property="item", type="object",
     *                     @OA\Property(property="id", type="integer", example=19),
     *                     @OA\Property(property="type", type="string", enum={"product", "combo"}, example="product"),
     *                     @OA\Property(property="product", type="object", nullable=true,
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Italian B.M.T."),
     *                         @OA\Property(property="category_name", type="string", nullable=true, example="Subs", description="Nombre de la categoria del producto"),
     *                         @OA\Property(property="image_url", type="string", example="/storage/menu/products/example.webp"),
     *                         @OA\Property(property="variant", type="object", nullable=true,
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="15cm")
     *                         )
     *                     ),
     *                     @OA\Property(property="combo", type="object", nullable=true,
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Combo Duo"),
     *                         @OA\Property(property="image_url", type="string", example="/storage/menu/combos/example.webp")
     *                     ),
     *                     @OA\Property(property="quantity", type="integer", example=1),
     *                     @OA\Property(property="unit_price", type="number", format="float", example=40),
     *                     @OA\Property(property="subtotal", type="number", format="float", example=40),
     *                     @OA\Property(property="discount_amount", type="number", format="float", example=0),
     *                     @OA\Property(property="final_price", type="number", format="float", example=40),
     *                     @OA\Property(property="is_daily_special", type="boolean", example=false),
     *                     @OA\Property(property="applied_promotion", type="object", nullable=true),
     *                     @OA\Property(property="selected_options", type="array",
     *
     *                         @OA\Items(type="object",
     *
     *                             @OA\Property(property="section_id", type="integer"),
     *                             @OA\Property(property="option_id", type="integer"),
     *                             @OA\Property(property="name", type="string", nullable=true),
     *                             @OA\Property(property="price", type="number", format="float")
     *                         )
     *                     ),
     *                     @OA\Property(property="combo_selections", type="array", nullable=true, @OA\Items(type="object")),
     *                     @OA\Property(property="options_total", type="number", format="float", example=0),
     *                     @OA\Property(property="line_total", type="number", format="float", example=40),
     *                     @OA\Property(property="notes", type="string", nullable=true)
     *                 ),
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
            $item->load(['product.category', 'variant', 'combo', 'cart']);

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
        $item->load(['product.category', 'variant', 'combo', 'cart']);

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
     * Update cart service type.
     *
     * @OA\Put(
     *     path="/api/v1/cart/service-type",
     *     tags={"Cart"},
     *     summary="Update service type",
     *     description="Changes the service type (pickup/delivery). Zone is determined automatically: for pickup uses restaurant.price_location, for delivery uses address validation. Requires restaurant to be set for pickup, or delivery address to be set for delivery.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="service_type", type="string", enum={"pickup", "delivery"}, example="pickup", description="Tipo de servicio. Para pickup requiere restaurante seleccionado, para delivery requiere dirección de entrega.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Service type updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="restaurant", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 ),
     *                 @OA\Property(property="service_type", type="string", enum={"pickup", "delivery"}),
     *                 @OA\Property(property="zone", type="string", enum={"capital", "interior"}, description="Determinada automaticamente"),
     *                 @OA\Property(property="items", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="subtotal", type="string"),
     *                     @OA\Property(property="total", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or missing requirements",
     *
     *         @OA\JsonContent(
     *             oneOf={
     *
     *                 @OA\Schema(
     *                     title="Restaurant Required",
     *
     *                     @OA\Property(property="message", type="string", example="Debe seleccionar un restaurante primero para pickup."),
     *                     @OA\Property(property="error_code", type="string", example="RESTAURANT_REQUIRED")
     *                 ),
     *
     *                 @OA\Schema(
     *                     title="Delivery Address Required",
     *
     *                     @OA\Property(property="message", type="string", example="Debe seleccionar una dirección de entrega primero para delivery."),
     *                     @OA\Property(property="error_code", type="string", example="DELIVERY_ADDRESS_REQUIRED")
     *                 ),
     *
     *                 @OA\Schema(
     *                     title="Address Outside Delivery Zone",
     *
     *                     @OA\Property(property="message", type="string", example="La dirección está fuera de la zona de entrega"),
     *                     @OA\Property(property="error_code", type="string", example="ADDRESS_OUTSIDE_DELIVERY_ZONE"),
     *                     @OA\Property(property="data", type="object",
     *                         @OA\Property(property="nearest_pickup_locations", type="array", @OA\Items(type="object"))
     *                     )
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function updateServiceType(UpdateCartServiceTypeRequest $request): JsonResponse
    {
        $customer = auth()->user();
        $validated = $request->validated();
        $serviceType = $validated['service_type'];

        $cart = $this->cartService->getOrCreateCart($customer);

        // Determinar zona automáticamente basado en el tipo de servicio
        if ($serviceType === 'pickup') {
            // Para pickup: zona del restaurante seleccionado
            if (! $cart->restaurant_id) {
                return response()->json([
                    'message' => 'Debe seleccionar un restaurante primero para pickup.',
                    'error_code' => 'RESTAURANT_REQUIRED',
                ], 422);
            }

            $cart->load('restaurant');
            $zone = $cart->restaurant->price_location ?? 'capital';
        } else {
            // Para delivery: zona de la dirección de entrega
            if (! $cart->delivery_address_id) {
                return response()->json([
                    'message' => 'Debe seleccionar una dirección de entrega primero para delivery.',
                    'error_code' => 'DELIVERY_ADDRESS_REQUIRED',
                ], 422);
            }

            $cart->load('deliveryAddress');

            // Re-validar la dirección para obtener zona y restaurante asignado
            $result = $this->deliveryValidation->validateDeliveryAddress($cart->deliveryAddress);

            if (! $result->isValid) {
                return response()->json([
                    'message' => $result->errorMessage,
                    'error_code' => 'ADDRESS_OUTSIDE_DELIVERY_ZONE',
                    'data' => [
                        'nearest_pickup_locations' => $result->nearbyPickupRestaurants,
                    ],
                ], 422);
            }

            $zone = $result->zone;

            // Actualizar el restaurante asignado para delivery
            $cart->update(['restaurant_id' => $result->restaurant->id]);
        }

        $cart = $this->cartService->updateServiceType($cart, $serviceType, $zone);
        $cart->load(['restaurant', 'items.product.category', 'items.variant', 'items.combo', 'items.cart']);

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
        $cart->load(['items.product.category', 'items.variant', 'items.combo']);

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
