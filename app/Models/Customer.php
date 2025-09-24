<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'subway_card',
        'birth_date',
        'gender',
        'phone',
        'address',
        'location',
        'nit',
        'fcm_token',
        'last_login_at',
        'last_activity_at',
        'last_purchase_at',
        'puntos',
        'puntos_updated_at',
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
            'puntos' => 'integer',
            'puntos_updated_at' => 'datetime',
        ];
    }

    /**
     * Attributes that should be appended to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['status', 'is_online'];

    /**
     * Actualiza el timestamp del último acceso del cliente
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Actualiza el timestamp de la última actividad del cliente
     */
    public function updateLastActivity(): void
    {
        $this->last_activity_at = now();
        $this->saveQuietly();
    }

    /**
     * Relación con el tipo de cliente
     */
    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }

    /**
     * Actualiza automáticamente el tipo de cliente basado en los puntos
     */
    public function updateCustomerType(): void
    {
        $newType = CustomerType::getTypeForPoints($this->puntos ?? 0);

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

    /**
     * Determina si el cliente está en línea basado en su última actividad
     * En línea: Última actividad dentro de los últimos 5 minutos
     */
    public function isOnline(): bool
    {
        return $this->last_activity_at &&
               $this->last_activity_at->diffInMinutes(now()) < 5;
    }

    /**
     * Accessor para el atributo is_online
     */
    public function getIsOnlineAttribute(): bool
    {
        return $this->isOnline();
    }

    /**
     * Obtiene el estado del cliente basado en su última actividad
     * Estados: 'never', 'online', 'recent', 'offline'
     */
    public function getStatusAttribute(): string
    {
        if (! $this->last_activity_at) {
            return 'never';
        }

        $minutes = $this->last_activity_at->diffInMinutes(now());

        return match (true) {
            $minutes < 5 => 'online',
            $minutes < 15 => 'recent',
            default => 'offline'
        };
    }

    /**
     * Scope para filtrar clientes en línea
     */
    public function scopeOnline($query)
    {
        return $query->where('last_activity_at', '>=', now()->subMinutes(5));
    }

    /**
     * Scope para filtrar clientes por estado
     */
    public function scopeWithStatus($query, string $status)
    {
        return match ($status) {
            'never' => $query->whereNull('last_activity_at'),
            'online' => $query->where('last_activity_at', '>=', now()->subMinutes(5)),
            'recent' => $query->whereBetween('last_activity_at', [now()->subMinutes(15), now()->subMinutes(5)]),
            'offline' => $query->where('last_activity_at', '<', now()->subMinutes(15))
                ->whereNotNull('last_activity_at'),
            default => $query
        };
    }
}
