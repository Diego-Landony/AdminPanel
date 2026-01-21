<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Menu\Promotion;
use App\Models\RestaurantUser;
use App\Observers\PersonalAccessTokenObserver;
use App\Observers\PromotionObserver;
use App\Policies\DriverPolicy;
use App\Policies\RestaurantUserPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        $this->configurePolicies();

        PersonalAccessToken::observe(PersonalAccessTokenObserver::class);
        Promotion::observe(PromotionObserver::class);

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
        // Customize password reset URL for Customers (web page that allows reset)
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            // Only apply to Customer model
            if ($notifiable instanceof Customer) {
                // Use web URL - the web page will show a form to reset password
                return url(route('customer.password.reset', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false));
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
        // Requisitos: mínimo 8 caracteres, al menos 1 letra, 1 número y 1 caracter especial
        // Balance entre seguridad y facilidad de uso
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->numbers()
                ->symbols();
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

    /**
     * Configure policies for models.
     */
    protected function configurePolicies(): void
    {
        Gate::policy(Driver::class, DriverPolicy::class);
        Gate::policy(RestaurantUser::class, RestaurantUserPolicy::class);
    }
}
