<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerPointsTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'points',
        'type',
        'reference_type',
        'reference_id',
        'description',
        'expires_at',
        'is_expired',
    ];

    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'points' => 'integer',
            'reference_id' => 'integer',
            'expires_at' => 'datetime',
            'is_expired' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
