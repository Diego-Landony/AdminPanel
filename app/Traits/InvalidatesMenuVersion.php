<?php

namespace App\Traits;

use App\Services\MenuVersionService;

/**
 * Trait para invalidar automáticamente la versión del menú
 * cuando un modelo que afecta el menú es creado, actualizado o eliminado.
 *
 * Uso: Agregar `use InvalidatesMenuVersion;` en cualquier modelo
 * que forme parte del menú (Product, Category, Combo, etc.)
 */
trait InvalidatesMenuVersion
{
    /**
     * Boot del trait - registra los eventos del modelo.
     */
    public static function bootInvalidatesMenuVersion(): void
    {
        // Invalidar cuando se crea un registro
        static::created(function () {
            app(MenuVersionService::class)->invalidate();
        });

        // Invalidar cuando se actualiza un registro
        static::updated(function () {
            app(MenuVersionService::class)->invalidate();
        });

        // Invalidar cuando se elimina un registro
        static::deleted(function () {
            app(MenuVersionService::class)->invalidate();
        });
    }
}
