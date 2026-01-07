<?php

namespace App\Support;

/**
 * Toggle class for controlling activity logging behavior.
 * Allows disabling logging during seeders, imports, or batch operations.
 */
class ActivityLogging
{
    protected static bool $enabled = true;

    /**
     * En producción: true (async con cola)
     * En desarrollo: false (sync inmediato)
     */
    protected static bool $async = true;

    /**
     * Disable activity logging.
     */
    public static function disable(): void
    {
        static::$enabled = false;
    }

    /**
     * Enable activity logging.
     */
    public static function enable(): void
    {
        static::$enabled = true;
    }

    /**
     * Check if activity logging is enabled.
     */
    public static function isEnabled(): bool
    {
        return static::$enabled;
    }

    /**
     * Enable synchronous logging (for tests).
     */
    public static function enableSync(): void
    {
        static::$async = false;
    }

    /**
     * Enable asynchronous logging (default).
     */
    public static function enableAsync(): void
    {
        static::$async = true;
    }

    /**
     * Check if logging should be async.
     */
    public static function isAsync(): bool
    {
        return static::$async;
    }

    /**
     * Execute callback with logging disabled.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function withoutLogging(callable $callback): mixed
    {
        static::disable();

        try {
            return $callback();
        } finally {
            static::enable();
        }
    }

    /**
     * Execute callback with sync logging (useful for tests).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function sync(callable $callback): mixed
    {
        static::enableSync();

        try {
            return $callback();
        } finally {
            static::enableAsync();
        }
    }

    /**
     * Reset to default state (enabled + async).
     */
    public static function reset(): void
    {
        static::$enabled = true;
        static::$async = true;
    }
}
