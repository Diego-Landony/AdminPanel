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
use App\Traits\HasPriceZones;
use Illuminate\Support\Facades\DB;

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
    use HasPriceZones;

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
     * Si ya existe un item idéntico (mismo producto/combo, variante y opciones),
     * se incrementa la cantidad en lugar de crear un nuevo item.
     *
     * @param  array  $data  Debe contener: product_id OR combo_id, variant_id (opcional),
     *                       quantity, selected_options (opcional), combo_selections (opcional), notes (opcional)
     */
    public function addItem(Cart $cart, array $data): CartItem
    {
        $quantity = $data['quantity'] ?? 1;
        $unitPrice = 0;
        $selectedOptions = $data['selected_options'] ?? null;
        $comboSelections = $data['combo_selections'] ?? null;
        $notes = $data['notes'] ?? null;

        $itemData = [
            'cart_id' => $cart->id,
            'quantity' => $quantity,
            'selected_options' => $selectedOptions,
            'combo_selections' => $comboSelections,
            'notes' => $notes,
        ];

        if (isset($data['combo_id'])) {
            $combo = Combo::findOrFail($data['combo_id']);

            if (! $combo->is_active) {
                throw new \InvalidArgumentException('El combo no está disponible');
            }

            $unitPrice = $this->getPriceForCombo($combo, $cart->zone, $cart->service_type);

            $itemData['combo_id'] = $combo->id;

            // Buscar item idéntico existente
            $existingItem = $this->findIdenticalItem($cart, null, null, $combo->id, $selectedOptions, $comboSelections);
        } elseif (isset($data['product_id'])) {
            $product = Product::with('category')->findOrFail($data['product_id']);

            if (! $product->is_active) {
                throw new \InvalidArgumentException('El producto no está disponible');
            }

            $variantId = $data['variant_id'] ?? null;
            $unitPrice = $this->getPriceForProduct($product, $variantId, $cart->zone, $cart->service_type);

            $itemData['product_id'] = $product->id;
            $itemData['variant_id'] = $variantId;

            // Buscar item idéntico existente
            $existingItem = $this->findIdenticalItem($cart, $product->id, $variantId, null, $selectedOptions, $comboSelections);
        } else {
            throw new \InvalidArgumentException('Se requiere product_id o combo_id');
        }

        // Si existe un item idéntico, incrementar cantidad y combinar notas
        if (isset($existingItem) && $existingItem) {
            $newQuantity = $existingItem->quantity + $quantity;
            $combinedNotes = $this->combineNotes($existingItem->notes, $notes);

            $existingItem->update([
                'quantity' => $newQuantity,
                'subtotal' => $unitPrice * $newQuantity,
                'notes' => $combinedNotes,
            ]);

            return $existingItem->fresh(['product', 'variant', 'combo']);
        }

        $itemData['unit_price'] = $unitPrice;
        $itemData['subtotal'] = $unitPrice * $quantity;

        return CartItem::create($itemData);
    }

    /**
     * Busca un item idéntico en el carrito
     * Compara: product_id, variant_id, combo_id, selected_options, combo_selections
     */
    protected function findIdenticalItem(
        Cart $cart,
        ?int $productId,
        ?int $variantId,
        ?int $comboId,
        ?array $selectedOptions,
        ?array $comboSelections
    ): ?CartItem {
        $query = $cart->items()
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('combo_id', $comboId);

        // Obtener candidatos y comparar opciones en PHP
        $candidates = $query->get();

        foreach ($candidates as $item) {
            if ($this->optionsAreIdentical($item->selected_options, $selectedOptions) &&
                $this->optionsAreIdentical($item->combo_selections, $comboSelections)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Compara dos arrays de opciones para determinar si son idénticas
     */
    protected function optionsAreIdentical(?array $options1, ?array $options2): bool
    {
        // Normalizar nulls y arrays vacíos
        $opt1 = empty($options1) ? [] : $options1;
        $opt2 = empty($options2) ? [] : $options2;

        // Comparar como JSON para una comparación profunda
        return json_encode($this->sortOptionsArray($opt1)) === json_encode($this->sortOptionsArray($opt2));
    }

    /**
     * Ordena un array de opciones para comparación consistente
     */
    protected function sortOptionsArray(array $options): array
    {
        // Ordenar el array principal
        usort($options, function ($a, $b) {
            $keyA = ($a['section_id'] ?? 0).'-'.($a['option_id'] ?? 0);
            $keyB = ($b['section_id'] ?? 0).'-'.($b['option_id'] ?? 0);

            return strcmp($keyA, $keyB);
        });

        return $options;
    }

    /**
     * Combina las notas de dos items, evitando duplicados
     */
    protected function combineNotes(?string $existingNotes, ?string $newNotes): ?string
    {
        if (empty($newNotes)) {
            return $existingNotes;
        }

        if (empty($existingNotes)) {
            return $newNotes;
        }

        // Evitar duplicar la misma nota
        if (str_contains($existingNotes, $newNotes)) {
            return $existingNotes;
        }

        return trim($existingNotes).' | '.trim($newNotes);
    }

    /**
     * Actualiza un item del carrito
     *
     * @param  array  $data  Puede contener: variant_id, quantity, selected_options, combo_selections, notes
     */
    public function updateItem(CartItem $item, array $data): CartItem
    {
        $cart = $item->cart;
        $priceNeedsUpdate = false;

        // Cambio de variante (solo para productos, no combos)
        if (array_key_exists('variant_id', $data) && $item->isProduct()) {
            $newVariantId = $data['variant_id'];

            // Validar que la variante pertenece al producto
            if ($newVariantId !== null) {
                $variant = ProductVariant::where('id', $newVariantId)
                    ->where('product_id', $item->product_id)
                    ->first();

                if (! $variant) {
                    throw new \InvalidArgumentException('La variante no pertenece a este producto');
                }
            }

            $item->variant_id = $newVariantId;
            $priceNeedsUpdate = true;
        }

        // Cambio de cantidad
        if (isset($data['quantity'])) {
            $item->quantity = $data['quantity'];
            $priceNeedsUpdate = true;
        }

        // Recalcular precio si es necesario
        if ($priceNeedsUpdate) {
            if ($item->isCombo()) {
                $unitPrice = $this->getPriceForCombo($item->combo, $cart->zone, $cart->service_type);
            } else {
                $unitPrice = $this->getPriceForProduct($item->product, $item->variant_id, $cart->zone, $cart->service_type);
            }

            $item->unit_price = $unitPrice;
            $item->subtotal = $unitPrice * $item->quantity;
        }

        if (isset($data['selected_options'])) {
            $item->selected_options = $data['selected_options'];
        }

        if (isset($data['combo_selections'])) {
            $item->combo_selections = $data['combo_selections'];
        }

        if (array_key_exists('notes', $data)) {
            $item->notes = $data['notes'];
        }

        $item->save();

        return $item->fresh(['product', 'variant', 'combo']);
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
     * Usa transacción para evitar race conditions
     */
    public function updateServiceType(Cart $cart, string $serviceType, string $zone): Cart
    {
        return DB::transaction(function () use ($cart, $serviceType, $zone) {
            $cart->update([
                'service_type' => $serviceType,
                'zone' => $zone,
            ]);

            $cart = $cart->fresh(['items.product', 'items.combo', 'items.variant']);

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
        });
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
     * El subtotal incluye: precio base de productos + extras (opciones adicionales)
     * Los descuentos se aplican SOLO al precio base, NO a los extras
     *
     * @return array Array con 'subtotal', 'discounts', 'promotions_applied', 'total', 'items_count', 'item_discounts', 'delivery_fee'
     */
    public function getCartSummary(Cart $cart): array
    {
        $items = $cart->items;

        // Subtotal = suma de (precio_base * cantidad) + total_extras de cada item
        $subtotal = $items->sum(function ($item) {
            return (float) $item->subtotal + ($item->getOptionsTotal() * $item->quantity);
        });

        // Calcular descuentos detallados por item (incluye Sub del Día y otras promociones)
        // Los descuentos se calculan sobre el precio base, NO sobre extras
        $itemDiscounts = $this->promotionService->calculateItemDiscounts($cart);

        // Calcular descuento total sumando los descuentos de cada item
        $discounts = collect($itemDiscounts)->sum('discount_amount');

        // Construir lista de promociones aplicadas desde los descuentos de items
        $appliedPromotions = $this->buildAppliedPromotionsFromDiscounts($itemDiscounts);

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
     * Construye la lista de promociones aplicadas desde los descuentos de items
     * Agrupa por promoción para evitar duplicados
     */
    protected function buildAppliedPromotionsFromDiscounts(array $itemDiscounts): array
    {
        $promotionsMap = [];

        foreach ($itemDiscounts as $itemId => $discount) {
            if ($discount['discount_amount'] <= 0 || empty($discount['applied_promotion'])) {
                continue;
            }

            $promo = $discount['applied_promotion'];
            $promoKey = $promo['type'].'_'.($promo['id'] ?? 0);

            if (! isset($promotionsMap[$promoKey])) {
                $promotionsMap[$promoKey] = [
                    'promotion_id' => $promo['id'] ?? 0,
                    'promotion_name' => $promo['name'],
                    'promotion_type' => $promo['type'],
                    'discount_amount' => 0,
                    'items_affected' => [],
                ];
            }

            $promotionsMap[$promoKey]['discount_amount'] += $discount['discount_amount'];
            $promotionsMap[$promoKey]['items_affected'][] = $itemId;
        }

        // Redondear los descuentos y convertir a array indexado
        return array_values(array_map(function ($promo) {
            $promo['discount_amount'] = round($promo['discount_amount'], 2);

            return $promo;
        }, $promotionsMap));
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
