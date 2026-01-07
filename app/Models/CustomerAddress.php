<?php

namespace App\Models;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model implements ActivityLoggable
{
    /** @use HasFactory<\Database\Factories\CustomerAddressFactory> */
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'label',
        'address_line',
        'latitude',
        'longitude',
        'delivery_notes',
        'zone',
        'is_default',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Relación con el cliente
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the field to use as the activity label.
     */
    public function getActivityLabelField(): string
    {
        return 'label';
    }

    /**
     * Get the human-readable model name for activity logs.
     */
    public static function getActivityModelName(): string
    {
        return 'Dirección de cliente';
    }

    /**
     * Marca esta dirección como predeterminada y desmarca las demás
     */
    public function markAsDefault(): void
    {
        // Desmarcar todas las demás direcciones del cliente
        self::where('customer_id', $this->customer_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Marcar esta como predeterminada
        $this->is_default = true;
        $this->save();
    }
}
