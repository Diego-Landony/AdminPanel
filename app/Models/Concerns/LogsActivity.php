<?php

namespace App\Models\Concerns;

use App\Observers\ActivityObserver;

/**
 * Trait para habilitar logging automático de actividades en modelos
 *
 * Uso:
 *   class Product extends Model {
 *       use LogsActivity;
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
     * Override este método en tu modelo para personalizar
     */
    public function getActivityConfig(): array
    {
        return [
            'label_field' => $this->getActivityLabelField(),
            'model_name' => class_basename($this),
            'ignored_fields' => $this->getActivityIgnoredFields(),
        ];
    }

    /**
     * Campo usado para identificar el modelo en los logs
     * Override en tu modelo si necesitas otro campo
     */
    protected function getActivityLabelField(): string
    {
        // Intenta común campos, en orden de preferencia
        if (isset($this->attributes['name'])) {
            return 'name';
        }

        if (isset($this->attributes['title'])) {
            return 'title';
        }

        if (isset($this->attributes['email'])) {
            return 'email';
        }

        return 'id';
    }

    /**
     * Campos que deben ser ignorados en el logging
     * Override en tu modelo para agregar más
     */
    protected function getActivityIgnoredFields(): array
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
     * Obtiene el valor del label para este modelo
     */
    public function getActivityLabel(): string
    {
        $field = $this->getActivityLabelField();

        return $this->$field ?? $this->id ?? 'Sin identificador';
    }
}
