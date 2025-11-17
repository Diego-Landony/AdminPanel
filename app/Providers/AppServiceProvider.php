<?php

namespace App\Providers;

use App\Models\Customer;
use App\Observers\PersonalAccessTokenObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
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
        $this->configureCustomerNotifications();

        PersonalAccessToken::observe(PersonalAccessTokenObserver::class);

        // Register mail views namespace for custom email templates
        $this->loadViewsFrom([
            resource_path('views/vendor/mail/html'),
            resource_path('views/vendor/mail/text'),
        ], 'mail');
    }

    /**
     * Configure custom notification URLs for Customer model (mobile deep links).
     */
    protected function configureCustomerNotifications(): void
    {
        // Customize password reset URL for Customers (mobile app deep link)
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            // Only apply to Customer model
            if ($notifiable instanceof Customer) {
                $scheme = config('app.mobile_scheme', 'subwayapp');
                $queryParams = http_build_query([
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ]);

                return "{$scheme}://reset-password?{$queryParams}";
            }

            // Default behavior for User model (AdminPanel)
            return url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        });

        // Customize email verification URL for Customers (mobile app deep link)
        VerifyEmail::createUrlUsing(function ($notifiable) {
            // Only apply to Customer model
            if ($notifiable instanceof Customer) {
                $scheme = config('app.mobile_scheme', 'subwayapp');

                // Generate signed API URL
                $apiUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'api.v1.auth.verify-email',
                    now()->addMinutes(60),
                    [
                        'id' => $notifiable->getKey(),
                        'hash' => sha1($notifiable->getEmailForVerification()),
                    ]
                );

                $encodedUrl = urlencode($apiUrl);

                return "{$scheme}://verify-email?url={$encodedUrl}";
            }

            // Default behavior for User model (AdminPanel)
            return \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });
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
