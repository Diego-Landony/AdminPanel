<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\OAuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Subway Guatemala Customer App
|--------------------------------------------------------------------------
|
| Rutas API para la aplicación móvil de clientes de Subway Guatemala.
| Usa Laravel Sanctum para autenticación basada en tokens.
|
*/

// API v1 routes
Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Public Routes (No authentication required)
    |--------------------------------------------------------------------------
    */

    // Authentication endpoints (strict rate limiting)
    Route::middleware(['throttle:auth'])->prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])
            ->name('api.v1.auth.register');

        Route::post('/login', [AuthController::class, 'login'])
            ->name('api.v1.auth.login');

        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
            ->name('api.v1.auth.forgot-password');

        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->name('api.v1.auth.reset-password');

        // Email verification
        Route::post('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('api.v1.auth.verify-email');

        Route::post('/email/resend', [AuthController::class, 'resendVerification'])
            ->middleware(['throttle:6,1'])
            ->name('api.v1.auth.resend-verification');
    });

    // OAuth endpoints (separate rate limiting)
    Route::middleware(['throttle:oauth'])->prefix('auth/oauth')->group(function () {
        // Mobile app LOGIN endpoint - id_token validation (does NOT create accounts)
        Route::post('/google', [OAuthController::class, 'google'])
            ->name('api.v1.auth.oauth.google.login');

        // Mobile app REGISTER endpoint - id_token validation (creates accounts)
        Route::post('/google/register', [OAuthController::class, 'googleRegister'])
            ->name('api.v1.auth.oauth.google.register');

        // Mobile app OAuth redirect flow (opens browser, redirects back to app)
        Route::get('/google/mobile', [OAuthController::class, 'redirectToMobile'])
            ->name('api.v1.auth.oauth.google.mobile');

        // Web app endpoints - OAuth redirect flow
        Route::get('/google/redirect', [OAuthController::class, 'googleRedirect'])
            ->name('api.v1.auth.oauth.google.redirect');

        Route::get('/google/callback', [OAuthController::class, 'googleCallback'])
            ->name('api.v1.auth.oauth.google.callback');
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Authentication required)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        // Authentication management
        Route::post('/auth/logout', [AuthController::class, 'logout'])
            ->name('api.v1.auth.logout');

        Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])
            ->name('api.v1.auth.logout-all');

        Route::post('/auth/refresh', [AuthController::class, 'refresh'])
            ->name('api.v1.auth.refresh');

        // Profile management
        Route::prefix('profile')->name('api.v1.profile.')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])
                ->name('show');

            Route::put('/', [ProfileController::class, 'update'])
                ->name('update');

            Route::delete('/', [ProfileController::class, 'destroy'])
                ->name('destroy');

            Route::post('/avatar', [ProfileController::class, 'updateAvatar'])
                ->name('update-avatar');

            Route::delete('/avatar', [ProfileController::class, 'deleteAvatar'])
                ->name('delete-avatar');

            Route::put('/password', [ProfileController::class, 'updatePassword'])
                ->name('update-password');
        });

        // Customer devices & FCM tokens
        Route::prefix('devices')->name('api.v1.devices.')->group(function () {
            Route::get('/', [DeviceController::class, 'index'])
                ->name('index');

            Route::post('/register', [DeviceController::class, 'register'])
                ->name('register');

            Route::delete('/{device}', [DeviceController::class, 'destroy'])
                ->name('destroy');
        });

        // Customer addresses
        // TODO: Future phase

        // Customer NITs (Tax IDs)
        // TODO: Future phase

        // Menu & products
        // TODO: Future phase

        // Orders
        // TODO: Phase 6

        // Loyalty points & rewards
        // TODO: Phase 6

        // Restaurants & locations
        // TODO: Phase 6
    });
});
