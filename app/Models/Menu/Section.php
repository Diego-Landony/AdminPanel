<?php

namespace App\Models\Menu;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model implements ActivityLoggable
{
    use HasFactory, LogsActivity;

    public function getActivityLabelField(): string
    {
        return 'title';
    }

    public static function getActivityModelName(): string
    {
        return 'Sección';
    }

    protected $fillable = [
        'title',
        'description',
        'is_required',
        'allow_multiple',
        'min_selections',
        'max_selections',
        'bundle_discount_enabled',
        'bundle_size',
        'bundle_discount_amount',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'allow_multiple' => 'boolean',
        'min_selections' => 'integer',
        'max_selections' => 'integer',
        'bundle_discount_enabled' => 'boolean',
        'bundle_size' => 'integer',
        'bundle_discount_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(SectionOption::class)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_sections')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    /**
     * Calcula el precio total y ahorro de las opciones seleccionadas.
     * Agrupa por precio y aplica descuento solo a extras del mismo precio.
     *
     * @param  array<int>  $selectedOptionIds
     * @return array{total: float, savings: float, details: array<int, array{price: float, count: int, bundles: int, savings: float}>}
     */
    public function calculateOptionsPrice(array $selectedOptionIds): array
    {
        $options = $this->options()->whereIn('id', $selectedOptionIds)->get();

        // Solo considerar opciones con is_extra = true para bundle
        $extras = $options->where('is_extra', true);

        // Suma de opciones sin extra (precio 0 o is_extra = false)
        $nonExtrasTotal = $options->where('is_extra', false)->sum('price_modifier');

        // Si bundle no está habilitado o no hay suficientes extras
        if (! $this->bundle_discount_enabled || $extras->count() < $this->bundle_size) {
            $total = $extras->sum('price_modifier') + $nonExtrasTotal;

            return ['total' => (float) $total, 'savings' => 0.0, 'details' => []];
        }

        // Agrupar extras por price_modifier
        $groupedByPrice = $extras->groupBy('price_modifier');

        $total = 0.0;
        $savings = 0.0;
        $details = [];

        foreach ($groupedByPrice as $price => $group) {
            $count = $group->count();
            $groupTotal = $count * (float) $price;

            // Calcular bundles para este grupo de precio
            $bundles = intdiv($count, $this->bundle_size);
            $groupSavings = $bundles * (float) $this->bundle_discount_amount;

            $total += $groupTotal - $groupSavings;
            $savings += $groupSavings;

            $details[] = [
                'price' => (float) $price,
                'count' => $count,
                'bundles' => $bundles,
                'savings' => $groupSavings,
            ];
        }

        // Agregar opciones sin extra
        $total += $nonExtrasTotal;

        return [
            'total' => round($total, 2),
            'savings' => round($savings, 2),
            'details' => $details,
        ];
    }
}
