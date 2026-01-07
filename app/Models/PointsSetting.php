<?php

namespace App\Models;

use App\Contracts\ActivityLoggable;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class PointsSetting extends Model implements ActivityLoggable
{
    use LogsActivity;

    protected $fillable = [
        'quetzales_per_point',
        'expiration_method',
        'expiration_months',
        'rounding_threshold',
    ];

    protected function casts(): array
    {
        return [
            'quetzales_per_point' => 'integer',
            'expiration_months' => 'integer',
            'rounding_threshold' => 'decimal:2',
        ];
    }

    /**
     * Get the current settings (singleton pattern - only one row)
     */
    public static function get(): self
    {
        return static::first() ?? static::getDefault();
    }

    /**
     * Get or create default settings
     */
    public static function getOrCreate(): self
    {
        return static::first() ?? static::create([
            'quetzales_per_point' => 10,
            'expiration_method' => 'total',
            'expiration_months' => 6,
            'rounding_threshold' => 0.80,
        ]);
    }

    /**
     * Get default instance without persisting
     */
    public static function getDefault(): self
    {
        $instance = new static;
        $instance->quetzales_per_point = 10;
        $instance->expiration_method = 'total';
        $instance->expiration_months = 6;
        $instance->rounding_threshold = 0.80;

        return $instance;
    }

    /**
     * Check if using FIFO expiration method
     */
    public function usesFifoExpiration(): bool
    {
        return $this->expiration_method === 'fifo';
    }

    /**
     * Check if using total expiration method
     */
    public function usesTotalExpiration(): bool
    {
        return $this->expiration_method === 'total';
    }

    /**
     * Campo usado para identificar el modelo en los logs de actividad
     */
    public function getActivityLabelField(): string
    {
        return 'id';
    }

    /**
     * Nombre del modelo para los logs de actividad
     */
    public static function getActivityModelName(): string
    {
        return 'Configuracion de puntos';
    }
}
