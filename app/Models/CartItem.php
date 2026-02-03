<?php

namespace App\Models;

use App\Models\Menu\Combo;
use App\Models\Menu\Product;
use App\Models\Menu\ProductVariant;
use App\Models\Menu\Promotion;
use App\Models\Menu\Section;
use App\Models\Menu\SectionOption;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'combo_id',
        'combinado_id',
        'quantity',
        'unit_price',
        'subtotal',
        'selected_options',
        'combo_selections',
        'combinado_selections',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cart_id' => 'integer',
            'product_id' => 'integer',
            'variant_id' => 'integer',
            'combo_id' => 'integer',
            'combinado_id' => 'integer',
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'selected_options' => 'array',
            'combo_selections' => 'array',
            'combinado_selections' => 'array',
        ];
    }

    /**
     * Relación: Un item pertenece a un carrito
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Relación: Un item puede ser un producto
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: Un item puede ser una variante
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Relación: Un item puede ser un combo
     */
    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    /**
     * Relación: Un item puede ser un combinado (bundle_special promotion)
     */
    public function combinado(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'combinado_id');
    }

    /**
     * Verifica si el item es un producto
     */
    public function isProduct(): bool
    {
        return $this->product_id !== null && $this->combo_id === null && $this->combinado_id === null;
    }

    /**
     * Verifica si el item es un combo
     */
    public function isCombo(): bool
    {
        return $this->combo_id !== null;
    }

    /**
     * Verifica si el item es un combinado (bundle_special)
     */
    public function isCombinado(): bool
    {
        return $this->combinado_id !== null;
    }

    /**
     * Calcula el total de opciones con bundle pricing.
     * Agrupa por sección y aplica descuentos de bundle si corresponde.
     * Para combos, incluye los extras de todas las selecciones.
     *
     * @return array{total: float, savings: float}
     */
    public function getOptionsTotalWithBundle(): array
    {
        // Recolectar todas las opciones: de selected_options y de combo_selections
        $allOptions = $this->collectAllSelectedOptions();

        if (empty($allOptions)) {
            return ['total' => 0.0, 'savings' => 0.0];
        }

        // Agrupar opciones por section_id
        $optionsBySectionId = collect($allOptions)->groupBy('section_id');

        // Pre-cargar todas las secciones para evitar N+1 queries
        $sectionIds = $optionsBySectionId->keys()->filter()->toArray();
        $sections = Section::whereIn('id', $sectionIds)->get()->keyBy('id');

        $total = 0.0;
        $savings = 0.0;

        foreach ($optionsBySectionId as $sectionId => $options) {
            // NO usar unique() - pasar todos los IDs incluyendo duplicados para combos
            $optionIds = $options->pluck('option_id')->filter()->values()->toArray();

            if (empty($optionIds)) {
                continue;
            }

            $section = $sections->get($sectionId);
            if (! $section) {
                // Si la sección no existe, calcular suma simple con duplicados
                $uniqueIds = array_unique($optionIds);
                $optionCounts = array_count_values($optionIds);
                $sectionOptions = SectionOption::whereIn('id', $uniqueIds)->get()->keyBy('id');
                foreach ($optionCounts as $optionId => $count) {
                    $opt = $sectionOptions->get($optionId);
                    if ($opt) {
                        $total += $opt->getPriceModifier() * $count;
                    }
                }

                continue;
            }

            // Usar el método de bundle pricing de la sección (ahora acepta duplicados)
            $result = $section->calculateOptionsPrice($optionIds);
            $total += $result['total'];
            $savings += $result['savings'];
        }

        return [
            'total' => round($total, 2),
            'savings' => round($savings, 2),
        ];
    }

    /**
     * Calcula el total de las opciones seleccionadas (extras).
     * Usa bundle pricing si está habilitado en la sección.
     */
    public function getOptionsTotal(): float
    {
        return $this->getOptionsTotalWithBundle()['total'];
    }

    /**
     * Obtener el ahorro por bundle de las opciones seleccionadas.
     */
    public function getBundleSavings(): float
    {
        return $this->getOptionsTotalWithBundle()['savings'];
    }

    /**
     * Calcula el total de la línea (subtotal + opciones)
     */
    public function getLineTotal(): float
    {
        return (float) $this->subtotal + $this->getOptionsTotal();
    }

    /**
     * Obtiene el nombre del item
     */
    public function getName(): string
    {
        if ($this->isCombo()) {
            return $this->combo?->name ?? 'Combo';
        }

        if ($this->isCombinado()) {
            return $this->combinado?->name ?? 'Combinado';
        }

        if ($this->variant) {
            return "{$this->product?->name} - {$this->variant->name}";
        }

        return $this->product?->name ?? 'Producto';
    }

    /**
     * Recolecta todas las opciones seleccionadas de productos, combos y combinados.
     * Para productos: usa selected_options directamente.
     * Para combos/combinados: extrae selected_options de cada selección.
     *
     * @return array<int, array{section_id: int, option_id: int}>
     */
    protected function collectAllSelectedOptions(): array
    {
        $allOptions = [];

        // Opciones directas del producto (si existen)
        if ($this->selected_options && is_array($this->selected_options)) {
            $allOptions = array_merge($allOptions, $this->selected_options);
        }

        // Opciones de combo_selections (para combos)
        if ($this->combo_selections && is_array($this->combo_selections)) {
            foreach ($this->combo_selections as $comboSelection) {
                $selections = $comboSelection['selections'] ?? [];
                foreach ($selections as $selection) {
                    $selectedOptions = $selection['selected_options'] ?? [];
                    if (is_array($selectedOptions)) {
                        $allOptions = array_merge($allOptions, $selectedOptions);
                    }
                }
            }
        }

        // Opciones de combinado_selections (para combinados - bundle_special)
        if ($this->combinado_selections && is_array($this->combinado_selections)) {
            foreach ($this->combinado_selections as $combinadoSelection) {
                $selections = $combinadoSelection['selections'] ?? [];
                foreach ($selections as $selection) {
                    $selectedOptions = $selection['selected_options'] ?? [];
                    if (is_array($selectedOptions)) {
                        $allOptions = array_merge($allOptions, $selectedOptions);
                    }
                }
            }
        }

        return $allOptions;
    }
}
