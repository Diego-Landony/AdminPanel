<?php

namespace App\Models;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerNit extends Model implements ActivityLoggable
{
    /** @use HasFactory<\Database\Factories\CustomerNitFactory> */
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'nit',
        'nit_type',
        'nit_name',
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
        return 'nit_name';
    }

    /**
     * Get the human-readable model name for activity logs.
     */
    public static function getActivityModelName(): string
    {
        return 'NIT de cliente';
    }

    /**
     * Marca este NIT como predeterminado y desmarca los demás
     */
    public function markAsDefault(): void
    {
        // Desmarcar todos los demás NITs del cliente
        self::where('customer_id', $this->customer_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Marcar este como predeterminado
        $this->is_default = true;
        $this->save();
    }
}
