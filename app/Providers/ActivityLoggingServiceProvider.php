<?php

namespace App\Providers;

use App\Support\ActivityLogging;
use Illuminate\Support\ServiceProvider;

class ActivityLoggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // En producción usar modo async (cola)
        // En desarrollo usar sync (inmediato)
        if (app()->isProduction()) {
            ActivityLogging::enableAsync();
        } else {
            ActivityLogging::enableSync();
        }
    }
}
