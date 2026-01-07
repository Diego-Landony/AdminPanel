<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Restaurant;

/**
 * Servicio de Gestión de Carrito
 *
 * Maneja todas las operaciones relacionadas con el carrito de compras:
 * - Creación y recuperación de carritos
 * - Gestión de items (agregar, actualizar, eliminar)
 * - Cálculo de precios
 * - Validación de disponibilidad
 * - Resumen de carrito
 * - Aplicación automática de promociones
 */
class CartService
{
    public function __construct(
        protected PromotionApplicationService $promotionService
    ) {}

    /**
     * Obtiene el carrito activo del cliente o crea uno nuevo
     */
    public function getOrCreateCart(Customer $customer): Cart
    {
        $cart = Cart::where('customer_id', $customer->id)
            ->active()
            ->notExpired()
            ->first();

        if (! $cart) {
            $cart = Cart::create([
                'customer_id' => $customer->id,
                'service_type' => 'pickup',
                'zone' => 'capital',
                'status' => 'active',
                'expires_at' => now()->addDays(7),
            ]);
        }

        return $cart;
    }

    /**
     * Agrega un item al carrito
     *
     * @param  array  $data  Debe contener: product_id OR combo_id, variant_id (opcional),
     *                       quantity, selected_options (opcional), combo_selections (opcional), notes (opcional)
     */
    public function addItem(Cart $cart, array $data): CartItem
    {
        $quantity = $data['quantity'] ?? 1;
        $unitPrice = 0;
        $itemData = [
            'cart_id' => $cart->id,
            'quantity' => $quantity,
            'selected_options' => $data['selected_options'] ?? null,
            'combo_selections' => $data['combo_selections'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        if (isset($data['combo_id'])) {
            $combo = Combo::findOrFail($data['combo_id']);

            if (! $combo->is_active) {
                throw new \InvalidArgumentException('El combo no está disponible');
            }

            $unitPrice = $this->getPriceForCombo($combo, $cart->zone, $cart->service_type);

            $itemData['combo_id'] = $combo->id;
        } elseif (isset($data['product_id'])) {
            $product = Product::with('category')->findOrFail($data['product_id']);

            if (! $product->is_active) {
                throw new \InvalidArgumentException('El producto no está disponible');
            }

            $variantId = $data['variant_id'] ?? null;
            $unitPrice = $this->getPriceForProduct($product, $variantId, $cart->zone, $cart->service_type);

            $itemData['product_id'] = $product->id;
            $itemData['variant_id'] = $variantId;
        } else {
            throw new \InvalidArgumentException('Se requiere product_id o combo_id');
        }

        $itemData['unit_price'] = $unitPrice;
        $itemData['subtotal'] = $unitPrice * $quantity;

        return CartItem::create($itemData);
    }

    /**
     * Actualiza un item del carrito
     *
     * @param  array  $data  Puede contener: quantity, selected_options, notes
     */
    public function updateItem(CartItem $item, array $data): CartItem
    {
        $cart = $item->cart;

        if (isset($data['quantity'])) {
            $quantity = $data['quantity'];

            if ($item->isCombo()) {
                $unitPrice = $this->getPriceForCombo($item->combo, $cart->zone, $cart->service_type);
            } else {
                $unitPrice = $this->getPriceForProduct($item->product, $item->variant_id, $cart->zone, $cart->service_type);
            }

            $item->quantity = $quantity;
            $item->unit_price = $unitPrice;
            $item->subtotal = $unitPrice * $quantity;
        }

        if (isset($data['selected_options'])) {
            $item->selected_options = $data['selected_options'];
        }

        if (isset($data['notes'])) {
            $item->notes = $data['notes'];
        }

        $item->save();

        return $item->fresh();
    }

    /**
     * Elimina un item del carrito
     */
    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    /**
     * Limpia todos los items del carrito
     */
    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
    }

    /**
     * Actualiza el restaurante del carrito (para pickup)
     * Automáticamente establece service_type como 'pickup' y zone según restaurant.price_location
     */
    public function updateRestaurant(Cart $cart, Restaurant $restaurant): Cart
    {
        $oldZone = $cart->zone;
        $oldServiceType = $cart->service_type;
        $newZone = $restaurant->price_location ?? 'capital';

        $cart->update([
            'restaurant_id' => $restaurant->id,
            'service_type' => 'pickup',
            'zone' => $newZone,
            'delivery_address_id' => null, // Limpiar dirección de delivery al cambiar a pickup
        ]);

        // Recalcular precios si cambió zona o tipo de servicio
        if ($oldZone !== $newZone || $oldServiceType !== 'pickup') {
            $cart = $cart->fresh(['items.product', 'items.variant', 'items.combo']);
            foreach ($cart->items as $item) {
                if ($item->isCombo()) {
                    $unitPrice = $this->getPriceForCombo($item->combo, $newZone, 'pickup');
                } else {
                    $unitPrice = $this->getPriceForProduct($item->product, $item->variant_id, $newZone, 'pickup');
                }

                $item->update([
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $item->quantity,
                ]);
            }
        }

        return $cart->fresh(['items', 'restaurant']);
    }

    /**
     * Actualiza el tipo de servicio y zona del carrito
     * Recalcula los precios de todos los items
     */
    public function updateServiceType(Cart $cart, string $serviceType, string $zone): Cart
    {
        $cart->update([
            'service_type' => $serviceType,
            'zone' => $zone,
        ]);

        $cart = $cart->fresh();

        foreach ($cart->items as $item) {
            if ($item->isCombo()) {
                $unitPrice = $this->getPriceForCombo($item->combo, $zone, $serviceType);
            } else {
                $unitPrice = $this->getPriceForProduct($item->product, $item->variant_id, $zone, $serviceType);
            }

            $item->update([
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice * $item->quantity,
            ]);
        }

        return $cart->fresh();
    }

    /**
     * Valida el carrito y verifica la disponibilidad de todos los items
     *
     * @return array Array con 'valid' (bool) y 'messages' (array)
     */
    public function validateCart(Cart $cart): array
    {
        $messages = [];
        $valid = true;

        if ($cart->items()->count() === 0) {
            $messages[] = 'El carrito está vacío';
            $valid = false;
        }

        foreach ($cart->items as $item) {
            if ($item->isCombo()) {
                $combo = $item->combo;

                if (! $combo) {
                    $messages[] = "El combo del item #{$item->id} ya no existe";
                    $valid = false;

                    continue;
                }

                if (! $combo->is_active) {
                    $messages[] = "El combo '{$combo->name}' ya no está disponible";
                    $valid = false;
                }

                if (! $combo->isAvailable()) {
                    $messages[] = "El combo '{$combo->name}' tiene items no disponibles";
                    $valid = false;
                }
            } else {
                $product = $item->product;

                if (! $product) {
                    $messages[] = "El producto del item #{$item->id} ya no existe";
                    $valid = false;

                    continue;
                }

                if (! $product->is_active) {
                    $messages[] = "El producto '{$product->name}' ya no está disponible";
                    $valid = false;
                }

                if ($item->variant_id) {
                    $variant = $item->variant;

                    if (! $variant) {
                        $messages[] = "La variante del producto '{$product->name}' ya no existe";
                        $valid = false;
                    } elseif (! $variant->is_active) {
                        $messages[] = "La variante '{$variant->name}' del producto '{$product->name}' ya no está disponible";
                        $valid = false;
                    }
                }
            }
        }

        return [
            'valid' => $valid,
            'messages' => $messages,
        ];
    }

    /**
     * Obtiene el resumen del carrito con subtotal, descuentos, promociones y total
     *
     * @return array Array con 'subtotal', 'discounts', 'promotions_applied', 'total', 'items_count', 'item_discounts', 'delivery_fee'
     */
    public function getCartSummary(Cart $cart): array
    {
        $items = $cart->items;
        $subtotal = $items->sum('subtotal');

        // Aplicar promociones automaticamente
        $appliedPromotions = $this->promotionService->applyPromotions($cart);
        $discounts = collect($appliedPromotions)->sum('discount_amount');

        // Calcular descuentos detallados por item
        $itemDiscounts = $this->promotionService->calculateItemDiscounts($cart);

        $total = $subtotal - $discounts;

        return [
            'subtotal' => round($subtotal, 2),
            'discounts' => round($discounts, 2),
            'promotions_applied' => $appliedPromotions,
            'delivery_fee' => 0, // Subway maneja delivery internamente, sin cargo adicional
            'total' => round(max(0, $total), 2),
            'items_count' => $items->count(),
            'item_discounts' => $itemDiscounts,
        ];
    }

    /**
     * Obtiene el precio del producto según zona y tipo de servicio
     */
    private function getPriceForProduct(Product $product, ?int $variantId, string $zone, string $serviceType): float
    {
        $priceField = $this->getPriceField($zone, $serviceType);

        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant && $variant->{$priceField} !== null) {
                return (float) $variant->{$priceField};
            }
        }

        return (float) ($product->{$priceField} ?? 0);
    }

    /**
     * Obtiene el precio del combo según zona y tipo de servicio
     */
    private function getPriceForCombo(Combo $combo, string $zone, string $serviceType): float
    {
        $priceField = $this->getPriceField($zone, $serviceType);

        return (float) ($combo->{$priceField} ?? 0);
    }

    /**
     * Obtiene el nombre del campo de precio según zona y tipo de servicio
     */
    private function getPriceField(string $zone, string $serviceType): string
    {
        return match ([$zone, $serviceType]) {
            ['capital', 'pickup'] => 'precio_pickup_capital',
            ['capital', 'delivery'] => 'precio_domicilio_capital',
            ['interior', 'pickup'] => 'precio_pickup_interior',
            ['interior', 'delivery'] => 'precio_domicilio_interior',
            default => 'precio_pickup_capital',
        };
    }

    /**
     * Actualiza la dirección de entrega del carrito y asigna el restaurante
     */
    public function updateDeliveryAddress(
        Cart $cart,
        CustomerAddress $address,
        Restaurant $restaurant,
        string $zone
    ): Cart {
        $oldZone = $cart->zone;

        $cart->update([
            'delivery_address_id' => $address->id,
            'restaurant_id' => $restaurant->id,
            'zone' => $zone,
            'service_type' => 'delivery',
        ]);

        if ($oldZone !== $zone) {
            $this->recalculatePricesForZone($cart, $zone);
        }

        return $cart->fresh(['items', 'restaurant', 'deliveryAddress']);
    }

    /**
     * Recalcula los precios de los items del carrito según la nueva zona
     */
    private function recalculatePricesForZone(Cart $cart, string $zone): void
    {
        $serviceType = $cart->service_type ?? 'delivery';
        $priceField = "precio_{$serviceType}_{$zone}";

        foreach ($cart->items as $item) {
            if ($item->product_id && $item->variant_id) {
                $variant = $item->variant;
                if ($variant && isset($variant->$priceField)) {
                    $item->update([
                        'unit_price' => $variant->$priceField,
                        'subtotal' => $variant->$priceField * $item->quantity,
                    ]);
                }
            } elseif ($item->combo_id) {
                $combo = $item->combo;
                if ($combo && isset($combo->$priceField)) {
                    $item->update([
                        'unit_price' => $combo->$priceField,
                        'subtotal' => $combo->$priceField * $item->quantity,
                    ]);
                }
            }
        }
    }
}
