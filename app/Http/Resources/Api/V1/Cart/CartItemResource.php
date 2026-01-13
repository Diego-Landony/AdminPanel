<?php

namespace App\Http\Resources\Api\V1\Cart;

use App\Traits\FormatsSelectedOptions;
use App\Traits\HasPriceZones;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    use FormatsSelectedOptions, HasPriceZones;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isProduct = $this->product_id !== null;
        $isCombo = $this->combo_id !== null;

        // Calcular extras una sola vez
        $optionsTotal = method_exists($this->resource, 'getOptionsTotal')
            ? (float) $this->getOptionsTotal()
            : 0.0;
        $optionsTotalWithQuantity = $optionsTotal * $this->quantity;

        // Obtener precio correcto según zona/servicio del carrito
        $correctUnitPrice = $this->getCorrectUnitPrice();

        // subtotal usa el precio correcto de zona/servicio + extras
        $subtotalWithExtras = ($correctUnitPrice * $this->quantity) + $optionsTotalWithQuantity;

        // original_price viene del cálculo de descuentos o es igual al subtotal con extras
        $originalPrice = $this->discount_info['original_price'] ?? $subtotalWithExtras;

        // final_price viene del cálculo de descuentos o es igual al subtotal con extras
        $finalPrice = $this->discount_info['final_price'] ?? $subtotalWithExtras;

        return [
            'id' => $this->id,
            'type' => $isProduct ? 'product' : 'combo',
            'product' => $this->when($isProduct && $this->relationLoaded('product'), function () {
                $product = $this->product;
                $variant = $this->relationLoaded('variant') ? $this->variant : null;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image_url' => $product->getImageUrl(),
                    'variant' => $variant ? [
                        'id' => $variant->id,
                        'name' => $variant->name,
                    ] : null,
                ];
            }),
            'combo' => $this->when($isCombo && $this->relationLoaded('combo'), function () {
                $combo = $this->combo;

                return [
                    'id' => $combo->id,
                    'name' => $combo->name,
                    'image_url' => $combo->getImageUrl(),
                ];
            }),
            'quantity' => $this->quantity,
            'unit_price' => round($correctUnitPrice, 2),
            'options_total' => $optionsTotal,
            // subtotal = (base * cantidad) + (extras * cantidad) - precio completo sin descuento
            'subtotal' => round($subtotalWithExtras, 2),
            // original_price = precio según zona/servicio + extras (para mostrar tachado)
            'original_price' => round($originalPrice, 2),
            // discount_amount = descuento aplicado (solo sobre precio base, nunca sobre extras)
            'discount_amount' => (float) ($this->discount_info['discount_amount'] ?? 0.0),
            // final_price = precio con descuento aplicado + extras (lo que paga el cliente)
            'final_price' => round($finalPrice, 2),
            'is_daily_special' => $this->discount_info['is_daily_special'] ?? false,
            'applied_promotion' => $this->discount_info['applied_promotion'] ?? null,
            'selected_options' => $this->formatSelectedOptions($this->selected_options),
            'combo_selections' => $this->combo_selections,
            'notes' => $this->notes,
        ];
    }

    /**
     * Obtiene el precio unitario correcto según zona y tipo de servicio del carrito.
     * Esto asegura consistencia cuando el usuario cambia de pickup a delivery o viceversa.
     */
    protected function getCorrectUnitPrice(): float
    {
        $cart = $this->relationLoaded('cart') ? $this->cart : null;
        $zone = $cart->zone ?? 'capital';
        $serviceType = $cart->service_type ?? 'pickup';
        $priceField = $this->getPriceField($zone, $serviceType);

        // Para productos con variante
        if ($this->variant_id && $this->relationLoaded('variant') && $this->variant) {
            return (float) ($this->variant->{$priceField} ?? $this->unit_price);
        }

        // Para productos sin variante
        if ($this->product_id && $this->relationLoaded('product') && $this->product) {
            return (float) ($this->product->{$priceField} ?? $this->unit_price);
        }

        // Para combos
        if ($this->combo_id && $this->relationLoaded('combo') && $this->combo) {
            return (float) ($this->combo->{$priceField} ?? $this->unit_price);
        }

        // Fallback al precio almacenado
        return (float) $this->unit_price;
    }
}
