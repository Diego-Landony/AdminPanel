<?php

namespace App\Models;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\TracksUserStatus;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable implements ActivityLoggable, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasApiTokens, HasFactory, LogsActivity, Notifiable, SoftDeletes, TracksUserStatus;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'terms_accepted_at',
        'password',
        'google_id',
        'apple_id',
        'oauth_provider',
        'subway_card',
        'birth_date',
        'gender',
        'phone',
        'last_login_at',
        'last_activity_at',
        'last_purchase_at',
        'points',
        'points_updated_at',
        'points_last_activity_at',
        'customer_type_id',
        'email_offers_enabled',
        'push_notifications_enabled',
        'downgrade_warning_sent_at',
        'points_expiration_warning_sent_at',
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
            'terms_accepted_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'last_purchase_at' => 'datetime',
            'points' => 'integer',
            'points_updated_at' => 'datetime',
            'points_last_activity_at' => 'datetime',
            'email_offers_enabled' => 'boolean',
            'push_notifications_enabled' => 'boolean',
            'downgrade_warning_sent_at' => 'datetime',
            'points_expiration_warning_sent_at' => 'datetime',
        ];
    }

    /**
     * Attributes that should be appended to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['status', 'is_online', 'full_name'];

    /**
     * Get the customer's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Bootstrap model events.
     */
    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (is_null($customer->subway_card) || $customer->subway_card === '') {
                $customer->subway_card = static::generateUniqueSubwayCard();
            }
        });
    }

    /**
     * Generate unique 11-digit subway card starting with 8.
     * Format: 8XXXXXXXXXX (11 digits total)
     */
    protected static function generateUniqueSubwayCard(): string
    {
        $maxAttempts = 100;
        $attempts = 0;

        do {
            $randomDigits = '';
            for ($i = 0; $i < 10; $i++) {
                $randomDigits .= random_int(0, 9);
            }
            $subwayCard = '8'.$randomDigits;
            $attempts++;

            if ($attempts >= $maxAttempts) {
                throw new \RuntimeException('Unable to generate unique subway card after '.$maxAttempts.' attempts');
            }
        } while (static::where('subway_card', $subwayCard)->exists());

        return $subwayCard;
    }

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
     * Relación con los favoritos del cliente
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Relación con las transacciones de puntos del cliente
     */
    public function pointsTransactions(): HasMany
    {
        return $this->hasMany(CustomerPointsTransaction::class);
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

    /**
     * Enforce token limit by deleting oldest tokens if limit is exceeded
     */
    public function enforceTokenLimit(int $limit = 5): void
    {
        $tokenCount = $this->tokens()->count();

        if ($tokenCount >= $limit) {
            $tokensToDelete = $tokenCount - $limit + 1;

            $oldestTokens = $this->tokens()
                ->orderBy('last_used_at', 'asc')
                ->orderBy('created_at', 'asc')
                ->limit($tokensToDelete)
                ->get();

            foreach ($oldestTokens as $token) {
                CustomerDevice::where('sanctum_token_id', $token->id)
                    ->update(['is_active' => false]);

                $token->delete();
            }
        }
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    /**
     * Campo usado para identificar el modelo en los logs de actividad
     */
    public function getActivityLabelField(): string
    {
        return 'email';
    }

    /**
     * Nombre del modelo para los logs de actividad
     */
    public static function getActivityModelName(): string
    {
        return 'Cliente';
    }
}
