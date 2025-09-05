<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'client_type',
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
}
