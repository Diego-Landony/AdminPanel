<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'restaurant_id',
        'overall_rating',
        'quality_rating',
        'speed_rating',
        'service_rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'overall_rating' => 'integer',
            'quality_rating' => 'integer',
            'speed_rating' => 'integer',
            'service_rating' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
