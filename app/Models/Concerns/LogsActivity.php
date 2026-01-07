<?php

namespace App\Models\Concerns;

use App\Contracts\ActivityLoggable;
use App\Observers\ActivityObserver;

/**
 * Trait para habilitar logging automático de actividades en modelos
 *
 * Los modelos que usen este trait DEBEN implementar la interface ActivityLoggable:
 *
 * Uso:
 *   class Product extends Model implements ActivityLoggable {
 *       use LogsActivity;
 *
 *       public function getActivityLabelField(): string {
 *           return 'name';
 *       }
 *
 *       public static function getActivityModelName(): string {
 *           return 'Producto';
 *       }
 *   }
 */
trait LogsActivity
{
    /**
     * Boot del trait - registra el observer automáticamente
     */
    protected static function bootLogsActivity(): void
    {
        static::observe(ActivityObserver::class);
    }

    /**
     * Configuración de actividad para este modelo
     *
     * @return array{label_field: string, model_name: string, ignored_fields: array<int, string>}
     */
    public function getActivityConfig(): array
    {
        return [
            'label_field' => $this->getActivityLabelField(),
            'model_name' => static::getActivityModelName(),
            'ignored_fields' => $this->getActivityIgnoredFields(),
        ];
    }

    /**
     * Obtiene el valor del label para este modelo
     */
    public function getActivityLabel(): string
    {
        $field = $this->getActivityLabelField();

        if (! property_exists($this, 'attributes') || ! array_key_exists($field, $this->attributes)) {
            return (string) ($this->id ?? 'Sin identificador');
        }

        return (string) ($this->$field ?? $this->id ?? 'Sin identificador');
    }

    /**
     * Campos que deben ser ignorados en el logging
     * Override en tu modelo para agregar más campos específicos
     *
     * @return array<int, string>
     */
    public function getActivityIgnoredFields(): array
    {
        return [
            'updated_at',
            'last_activity_at',
            'last_login_at',
            'remember_token',
            'password',
        ];
    }

    /**
     * Nombre por defecto del modelo para activity logs
     * Override OBLIGATORIO en cada modelo que implemente ActivityLoggable
     */
    public static function getActivityModelName(): string
    {
        return class_basename(static::class);
    }
}
