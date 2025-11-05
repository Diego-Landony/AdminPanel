<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\TracksUserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, LogsActivity, Notifiable, SoftDeletes, TracksUserStatus;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'subway_card',
        'birth_date',
        'gender',
        'phone',
        'last_login_at',
        'last_activity_at',
        'last_purchase_at',
        'points',
        'points_updated_at',
        'timezone',
        'customer_type_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'last_purchase_at' => 'datetime',
            'points' => 'integer',
            'points_updated_at' => 'datetime',
        ];
    }

    /**
     * Attributes that should be appended to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['status', 'is_online'];

    /**
     * Relación con el tipo de cliente
     */
    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }

    /**
     * Relación con las direcciones del cliente
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Obtiene la dirección predeterminada del cliente
     */
    public function defaultAddress(): ?CustomerAddress
    {
        return $this->addresses()->where('is_default', true)->first();
    }

    /**
     * Relación con los dispositivos del cliente
     */
    public function devices(): HasMany
    {
        return $this->hasMany(CustomerDevice::class);
    }

    /**
     * Obtiene solo los dispositivos activos del cliente (usados en los últimos 30 días)
     */
    public function activeDevices(): HasMany
    {
        return $this->devices()->active();
    }

    /**
     * Relación con los NITs del cliente
     */
    public function nits(): HasMany
    {
        return $this->hasMany(CustomerNit::class);
    }

    /**
     * Obtiene el NIT predeterminado del cliente
     */
    public function defaultNit(): ?CustomerNit
    {
        return $this->nits()->where('is_default', true)->first();
    }

    /**
     * Actualiza automáticamente el tipo de cliente basado en los puntos
     */
    public function updateCustomerType(): void
    {
        $newType = CustomerType::getTypeForPoints($this->points ?? 0);

        if ($newType && $this->customer_type_id !== $newType->id) {
            $this->customer_type_id = $newType->id;
            $this->saveQuietly();
        }
    }

    /**
     * Scope para filtrar por tipo de cliente
     */
    public function scopeOfType($query, $typeId)
    {
        return $query->where('customer_type_id', $typeId);
    }
}
