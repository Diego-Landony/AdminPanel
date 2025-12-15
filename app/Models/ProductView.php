<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductView extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'viewable_type',
        'viewable_id',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function recordView(Customer $customer, Model $viewable): void
    {
        static::create([
            'customer_id' => $customer->id,
            'viewable_type' => get_class($viewable),
            'viewable_id' => $viewable->id,
            'viewed_at' => now(),
        ]);

        static::pruneOldViews($customer);
    }

    protected static function pruneOldViews(Customer $customer): void
    {
        $keepCount = 50;
        $viewCount = static::where('customer_id', $customer->id)->count();

        if ($viewCount > $keepCount) {
            $oldestViews = static::where('customer_id', $customer->id)
                ->orderBy('viewed_at', 'asc')
                ->limit($viewCount - $keepCount)
                ->pluck('id');

            static::whereIn('id', $oldestViews)->delete();
        }
    }
}
