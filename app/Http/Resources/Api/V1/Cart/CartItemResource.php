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
        $priceInfo = $this->getCorrectUnitPriceWithValidation();
        $correctUnitPrice = $priceInfo['price'];
        $priceValid = $priceInfo['valid'];
        $priceWarning = $priceInfo['warning'];

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
                    'category_name' => $product->category?->name,
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
            // Validación de precio - permite al frontend mostrar advertencias
            'price_valid' => $priceValid,
            'price_warning' => $this->when(! $priceValid, $priceWarning),
        ];
    }

    /**
     * Obtiene el precio unitario correcto según zona y tipo de servicio del carrito.
     * Retorna información de validación para que el frontend pueda mostrar advertencias.
     *
     * @return array{price: float, valid: bool, warning: string|null}
     */
    protected function getCorrectUnitPriceWithValidation(): array
    {
        $cart = $this->relationLoaded('cart') ? $this->cart : null;
        $zone = $cart->zone ?? 'capital';
        $serviceType = $cart->service_type ?? 'pickup';
        $priceField = $this->getPriceField($zone, $serviceType);

        $zoneName = $zone === 'capital' ? 'Capital' : 'Interior';
        $serviceName = $serviceType === 'pickup' ? 'pickup' : 'delivery';

        // Para productos con variante
        if ($this->variant_id && $this->relationLoaded('variant') && $this->variant) {
            $price = $this->variant->{$priceField};
            if ($price === null || $price <= 0) {
                return [
                    'price' => (float) $this->unit_price,
                    'valid' => false,
                    'warning' => "Esta variante no tiene precio configurado para {$serviceName} en {$zoneName}",
                ];
            }

            return ['price' => (float) $price, 'valid' => true, 'warning' => null];
        }

        // Para productos sin variante
        if ($this->product_id && $this->relationLoaded('product') && $this->product) {
            $price = $this->product->{$priceField};
            if ($price === null || $price <= 0) {
                return [
                    'price' => (float) $this->unit_price,
                    'valid' => false,
                    'warning' => "Este producto no tiene precio configurado para {$serviceName} en {$zoneName}",
                ];
            }

            return ['price' => (float) $price, 'valid' => true, 'warning' => null];
        }

        // Para combos
        if ($this->combo_id && $this->relationLoaded('combo') && $this->combo) {
            $price = $this->combo->{$priceField};
            if ($price === null || $price <= 0) {
                return [
                    'price' => (float) $this->unit_price,
                    'valid' => false,
                    'warning' => "Este combo no tiene precio configurado para {$serviceName} en {$zoneName}",
                ];
            }

            return ['price' => (float) $price, 'valid' => true, 'warning' => null];
        }

        // Fallback al precio almacenado
        return [
            'price' => (float) $this->unit_price,
            'valid' => $this->unit_price > 0,
            'warning' => $this->unit_price <= 0 ? 'Este item no tiene precio válido' : null,
        ];
    }
}
