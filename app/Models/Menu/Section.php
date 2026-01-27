<?php

namespace App\Models\Menu;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use App\Traits\InvalidatesMenuVersion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model implements ActivityLoggable
{
    use HasFactory, InvalidatesMenuVersion, LogsActivity;

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

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'allow_multiple' => 'boolean',
            'min_selections' => 'integer',
            'max_selections' => 'integer',
            'bundle_discount_enabled' => 'boolean',
            'bundle_size' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the bundle discount amount, ensuring null instead of empty string.
     */
    public function getBundleDiscountAmountAttribute($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

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
     * Acepta IDs con duplicados para contar correctamente opciones repetidas (ej: mismo extra en 2 subs de un combo).
     *
     * @param  array<int>  $selectedOptionIds  IDs de opciones, puede contener duplicados
     * @return array{total: float, savings: float, details: array<int, array{price: float, count: int, bundles: int, savings: float}>}
     */
    public function calculateOptionsPrice(array $selectedOptionIds): array
    {
        // Contar cuántas veces aparece cada opción (para manejar duplicados en combos)
        $optionCounts = array_count_values($selectedOptionIds);
        $uniqueIds = array_keys($optionCounts);

        $options = $this->options()->whereIn('id', $uniqueIds)->get()->keyBy('id');

        $nonExtrasTotal = 0.0;
        $extrasWithCounts = [];

        foreach ($optionCounts as $optionId => $count) {
            $option = $options->get($optionId);
            if (! $option) {
                continue;
            }

            if ($option->is_extra) {
                // Agregar cada ocurrencia del extra
                for ($i = 0; $i < $count; $i++) {
                    $extrasWithCounts[] = (float) $option->price_modifier;
                }
            } else {
                $nonExtrasTotal += (float) $option->price_modifier * $count;
            }
        }

        $extrasCount = count($extrasWithCounts);

        // Si bundle no está habilitado o no hay suficientes extras
        if (! $this->bundle_discount_enabled || $extrasCount < $this->bundle_size) {
            $total = array_sum($extrasWithCounts) + $nonExtrasTotal;

            return ['total' => (float) $total, 'savings' => 0.0, 'details' => []];
        }

        // Agrupar extras por precio
        $groupedByPrice = [];
        foreach ($extrasWithCounts as $price) {
            $key = (string) $price;
            $groupedByPrice[$key] = ($groupedByPrice[$key] ?? 0) + 1;
        }

        $total = 0.0;
        $savings = 0.0;
        $details = [];

        foreach ($groupedByPrice as $priceStr => $count) {
            $price = (float) $priceStr;
            $groupTotal = $count * $price;

            // Calcular bundles para este grupo de precio
            $bundles = intdiv($count, $this->bundle_size);
            $groupSavings = $bundles * (float) $this->bundle_discount_amount;

            $total += $groupTotal - $groupSavings;
            $savings += $groupSavings;

            $details[] = [
                'price' => $price,
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
