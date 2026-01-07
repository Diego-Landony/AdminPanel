<?php

namespace App\Contracts;

/**
 * Interface para modelos que registran actividad automáticamente
 *
 * Los modelos que implementen esta interface deben definir explícitamente
 * cómo se identifican y representan en los logs de actividad.
 */
interface ActivityLoggable
{
    /**
     * Get the field name used to identify this model in activity logs.
     * Examples: 'name', 'title', 'subject', 'email'
     */
    public function getActivityLabelField(): string;

    /**
     * Get the human-readable label for this model instance.
     */
    public function getActivityLabel(): string;

    /**
     * Get fields that should be ignored when logging changes.
     *
     * @return array<int, string>
     */
    public function getActivityIgnoredFields(): array;

    /**
     * Get the display name for this model type.
     * Example: 'Usuario', 'Producto', 'Restaurante'
     */
    public static function getActivityModelName(): string;
}
