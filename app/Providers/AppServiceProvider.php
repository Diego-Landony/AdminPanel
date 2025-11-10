<?php

namespace App\Providers;

use App\Observers\PersonalAccessTokenObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Laravel\Sanctum\PersonalAccessToken;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Messaging::class, function ($app) {
            $credentialsPath = config('services.firebase.credentials');

            if (! $credentialsPath || ! file_exists($credentialsPath)) {
                throw new \RuntimeException(
                    'Firebase credentials file not found. Please check FIREBASE_CREDENTIALS in your .env file.'
                );
            }

            return (new Factory)
                ->withServiceAccount($credentialsPath)
                ->createMessaging();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePasswordValidation();
        $this->configureRateLimiting();

        PersonalAccessToken::observe(PersonalAccessTokenObserver::class);
    }

    /**
     * Configure password validation rules.
     */
    protected function configurePasswordValidation(): void
    {
        Password::defaults(function () {
            $rule = Password::min(6)
                ->letters()
                ->numbers();

            return $this->app->environment('production')
                ? $rule->uncompromised()
                : $rule;
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Rate limiter general para API (120 requests por minuto)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter estricto para autenticación (5 intentos por minuto)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Rate limiter para OAuth (10 intentos por minuto)
        RateLimiter::for('oauth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
