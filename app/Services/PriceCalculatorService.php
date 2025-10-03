<?php

namespace App\Services;

use App\Models\Menu\Category;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Promotion;
use App\Models\Menu\SectionOption;
use Carbon\Carbon;

/**
 * Servicio de Cálculo de Precios con Promociones
 *
 * Determina automáticamente de dónde obtener precios:
 * - Si la categoría usa variantes → precios de ProductVariant
 * - Si la categoría NO usa variantes → precios del pivot category_product
 *
 * Considera también:
 * - Modificadores de opciones de sección
 * - Sub del Día (precio especial en días específicos)
 * - Promociones (2x1, descuentos %)
 *
 * Regla de negocio: Sub del Día NO acumula con otras promociones
 */
class PriceCalculatorService
{
    /**
     * Calcula el precio final considerando si usa variantes o no
     *
     * @param  Product  $product  El producto
     * @param  int  $categoryId  ID de la categoría desde donde se compra
     * @param  int|null  $variantId  ID de la variante (si la categoría usa variantes)
     * @param  string  $priceType  Tipo de precio ('precio_pickup_capital', 'precio_domicilio_capital', 'precio_pickup_interior', 'precio_domicilio_interior')
     * @param  int  $quantity  Cantidad
     * @param  array  $selectedOptionIds  IDs de opciones de sección
     * @param  Carbon|null  $orderTime  Momento del pedido
     */
    public function calculatePrice(
        Product $product,
        int $categoryId,
        ?int $variantId,
        string $priceType,
        int $quantity = 1,
        array $selectedOptionIds = [],
        ?Carbon $orderTime = null
    ): array {
        $orderTime = $orderTime ?? now();
        $category = Category::findOrFail($categoryId);

        // Determinar si usar variantes o pivot
        if ($category->uses_variants) {
            return $this->calculateWithVariant($product, $variantId, $priceType, $quantity, $selectedOptionIds, $orderTime);
        } else {
            return $this->calculateWithPivot($product, $categoryId, $priceType, $quantity, $selectedOptionIds, $orderTime);
        }
    }

    /**
     * Calcula precio usando ProductVariant (para categorías con variantes)
     */
    protected function calculateWithVariant(
        Product $product,
        ?int $variantId,
        string $priceType,
        int $quantity,
        array $selectedOptionIds,
        Carbon $orderTime
    ): array {
        if (! $variantId) {
            throw new \InvalidArgumentException('Se requiere variant_id para productos con variantes');
        }

        $variant = ProductVariant::findOrFail($variantId);

        // Verificar que la variante pertenece al producto
        if ($variant->product_id !== $product->id) {
            throw new \InvalidArgumentException('La variante no pertenece al producto especificado');
        }

        // 1. Verificar si es Sub del Día HOY
        $isDailySpecialToday = $this->isVariantDailySpecialToday($variant, $orderTime);

        // 2. Precio base de la variante
        $unitPrice = $isDailySpecialToday
            ? $variant->getDailySpecialPrice($priceType) ?? $variant->getPrice($priceType)
            : $variant->getPrice($priceType);

        // 3. Sumar modificadores de opciones
        $optionsModifier = $this->calculateOptionsModifier($selectedOptionIds);
        $unitPrice += $optionsModifier;

        $subtotal = $unitPrice * $quantity;
        $originalSubtotal = $subtotal;
        $appliedPromotion = null;

        // 4. Buscar promociones (solo si NO es sub del día)
        if (! $isDailySpecialToday) {
            $promotion = $this->findActivePromotionForVariant($variant, $orderTime);

            if ($promotion) {
                $appliedPromotion = $promotion;

                if ($promotion->type === 'percentage_discount') {
                    $discount = $subtotal * ($promotion->discount_value / 100);
                    $subtotal -= $discount;
                }
            }
        }

        return [
            'variant_id' => $variant->id,
            'product_id' => $product->id,
            'sku' => $variant->sku,
            'name' => $variant->name,
            'size' => $variant->size,
            'unit_price' => round($unitPrice, 2),
            'quantity' => $quantity,
            'subtotal' => round($subtotal, 2),
            'original_subtotal' => round($originalSubtotal, 2),
            'discount' => round($originalSubtotal - $subtotal, 2),
            'is_daily_special' => $isDailySpecialToday,
            'options_modifier' => round($optionsModifier, 2),
            'promotion' => $appliedPromotion ? [
                'id' => $appliedPromotion->id,
                'name' => $appliedPromotion->name,
                'type' => $appliedPromotion->type,
                'discount_value' => $appliedPromotion->discount_value,
            ] : null,
        ];
    }

    /**
     * Calcula precio usando pivot category_product (para categorías sin variantes)
     */
    protected function calculateWithPivot(
        Product $product,
        int $categoryId,
        string $priceType,
        int $quantity,
        array $selectedOptionIds,
        Carbon $orderTime
    ): array {
        // Obtener el pivot con los precios
        $pivot = $product->categories()
            ->where('category_id', $categoryId)
            ->first()
            ?->pivot;

        if (! $pivot) {
            throw new \InvalidArgumentException('El producto no está en la categoría especificada');
        }

        // Verificar que el precio existe en el pivot
        if ($pivot->{$priceType} === null) {
            throw new \InvalidArgumentException('Precio no definido para esta categoría');
        }

        // 1. Precio base del pivot
        $unitPrice = (float) $pivot->{$priceType};

        // 2. Sumar modificadores de opciones
        $optionsModifier = $this->calculateOptionsModifier($selectedOptionIds);
        $unitPrice += $optionsModifier;

        $subtotal = $unitPrice * $quantity;
        $originalSubtotal = $subtotal;
        $appliedPromotion = null;

        // 3. Buscar promociones para el producto
        $promotion = $this->findActivePromotionForProduct($product, $categoryId, $orderTime);

        if ($promotion) {
            $appliedPromotion = $promotion;

            if ($promotion->type === 'percentage_discount') {
                $discount = $subtotal * ($promotion->discount_value / 100);
                $subtotal -= $discount;
            }
        }

        return [
            'product_id' => $product->id,
            'category_id' => $categoryId,
            'name' => $product->name,
            'unit_price' => round($unitPrice, 2),
            'quantity' => $quantity,
            'subtotal' => round($subtotal, 2),
            'original_subtotal' => round($originalSubtotal, 2),
            'discount' => round($originalSubtotal - $subtotal, 2),
            'is_daily_special' => false, // Los productos sin variantes no tienen Sub del Día
            'options_modifier' => round($optionsModifier, 2),
            'promotion' => $appliedPromotion ? [
                'id' => $appliedPromotion->id,
                'name' => $appliedPromotion->name,
                'type' => $appliedPromotion->type,
                'discount_value' => $appliedPromotion->discount_value,
            ] : null,
        ];
    }

    /**
     * Verifica si una variante es Sub del Día en la fecha dada
     */
    protected function isVariantDailySpecialToday(ProductVariant $variant, Carbon $date): bool
    {
        if (! $variant->is_daily_special || empty($variant->daily_special_days)) {
            return false;
        }

        $dayOfWeek = $date->dayOfWeek; // 0=Domingo, 1=Lunes, ..., 6=Sábado

        return in_array($dayOfWeek, $variant->daily_special_days);
    }

    /**
     * Calcula el modificador total de las opciones seleccionadas
     * Solo suma el price_modifier de las opciones marcadas como is_extra
     */
    protected function calculateOptionsModifier(array $optionIds): float
    {
        if (empty($optionIds)) {
            return 0;
        }

        $options = SectionOption::whereIn('id', $optionIds)->get();

        return $options->sum(fn ($option) => $option->getPriceModifier());
    }

    /**
     * Busca promoción activa para una variante
     */
    protected function findActivePromotionForVariant(ProductVariant $variant, Carbon $datetime): ?Promotion
    {
        $dayOfWeek = $datetime->dayOfWeek;
        $currentDate = $datetime->toDateString();
        $currentTime = $datetime->format('H:i:s');

        return Promotion::where('is_active', true)
            ->where(function ($q) use ($variant) {
                // Aplica a la variante específica
                $q->whereHas('items', fn ($q2) => $q2->where('variant_id', $variant->id))
                    // O al producto padre
                    ->orWhereHas('items', fn ($q2) => $q2->where('product_id', $variant->product_id))
                    // O a las categorías del producto
                    ->orWhereHas('items', function ($q2) use ($variant) {
                        $categoryIds = $variant->product->categories()->pluck('categories.id');
                        $q2->whereIn('category_id', $categoryIds);
                    });
            })
            // Día de la semana
            ->where(function ($q) use ($dayOfWeek) {
                $q->whereNull('active_days')
                    ->orWhereJsonContains('active_days', $dayOfWeek);
            })
            // Vigencia de fechas
            ->where(function ($q) use ($currentDate) {
                $q->where('is_permanent', true)
                    ->orWhere(function ($q2) use ($currentDate) {
                        $q2->whereDate('valid_from', '<=', $currentDate)
                            ->whereDate('valid_until', '>=', $currentDate);
                    });
            })
            // Restricción de horas
            ->where(function ($q) use ($currentTime) {
                $q->where('has_time_restriction', false)
                    ->orWhere(function ($q2) use ($currentTime) {
                        $q2->whereTime('time_from', '<=', $currentTime)
                            ->whereTime('time_until', '>=', $currentTime);
                    });
            })
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Busca promoción activa para un producto (sin variantes)
     */
    protected function findActivePromotionForProduct(Product $product, int $categoryId, Carbon $datetime): ?Promotion
    {
        $dayOfWeek = $datetime->dayOfWeek;
        $currentDate = $datetime->toDateString();
        $currentTime = $datetime->format('H:i:s');

        return Promotion::where('is_active', true)
            ->where(function ($q) use ($product, $categoryId) {
                // Aplica al producto específico
                $q->whereHas('items', fn ($q2) => $q2->where('product_id', $product->id))
                    // O a la categoría
                    ->orWhereHas('items', fn ($q2) => $q2->where('category_id', $categoryId));
            })
            // Día de la semana
            ->where(function ($q) use ($dayOfWeek) {
                $q->whereNull('active_days')
                    ->orWhereJsonContains('active_days', $dayOfWeek);
            })
            // Vigencia de fechas
            ->where(function ($q) use ($currentDate) {
                $q->where('is_permanent', true)
                    ->orWhere(function ($q2) use ($currentDate) {
                        $q2->whereDate('valid_from', '<=', $currentDate)
                            ->whereDate('valid_until', '>=', $currentDate);
                    });
            })
            // Restricción de horas
            ->where(function ($q) use ($currentTime) {
                $q->where('has_time_restriction', false)
                    ->orWhere(function ($q2) use ($currentTime) {
                        $q2->whereTime('time_from', '<=', $currentTime)
                            ->whereTime('time_until', '>=', $currentTime);
                    });
            })
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Calcula el total del carrito con todas las promociones aplicadas
     */
    public function calculateCartTotal(array $cartItems): array
    {
        $subtotal = collect($cartItems)->sum('original_subtotal');
        $totalDiscount = collect($cartItems)->sum('discount');
        $total = collect($cartItems)->sum('subtotal');

        return [
            'items' => $cartItems,
            'subtotal' => round($subtotal, 2),
            'total_discount' => round($totalDiscount, 2),
            'total' => round($total, 2),
            'items_count' => count($cartItems),
        ];
    }
}
