<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\OAuthController;
use App\Http\Controllers\Api\V1\BroadcastingController;
use App\Http\Controllers\Api\V1\CustomerAddressController;
use App\Http\Controllers\Api\V1\CustomerNitController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\WalletController;
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

        Route::post('/reactivate', [AuthController::class, 'reactivateAccount'])
            ->name('api.v1.auth.reactivate');

        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
            ->name('api.v1.auth.forgot-password');

        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->name('api.v1.auth.reset-password');

        // Email verification (POST for API, GET for web fallback)
        Route::match(['get', 'post'], '/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('api.v1.auth.verify-email');

        Route::post('/email/resend', [AuthController::class, 'resendVerification'])
            ->middleware(['throttle:6,1'])
            ->name('api.v1.auth.resend-verification');
    });

    // OAuth endpoints (separate rate limiting)
    Route::middleware(['throttle:oauth'])->prefix('auth/oauth')->group(function () {
        // Firebase native flow (Flutter - no browser needed)
        // Receives Firebase ID token from native Google Sign-In
        Route::post('/google/firebase', [OAuthController::class, 'googleFirebase'])
            ->name('api.v1.auth.oauth.google.firebase');

        // Firebase native flow for Apple Sign-In (Flutter - no browser needed)
        // Receives Firebase ID token from native Apple Sign-In
        Route::post('/apple/firebase', [OAuthController::class, 'appleFirebase'])
            ->name('api.v1.auth.oauth.apple.firebase');
    });

    // Public support endpoint for access issues (no auth required)
    Route::post('/support/access-issues', [App\Http\Controllers\Api\V1\Support\SupportTicketController::class, 'reportAccessIssue'])
        ->middleware(['throttle:5,1']) // 5 requests per minute to prevent abuse
        ->name('api.v1.support.access-issues');

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Authentication required)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        // Broadcasting authentication
        Route::post('/broadcasting/auth', [BroadcastingController::class, 'auth']);

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

            Route::put('/password', [ProfileController::class, 'updatePassword'])
                ->name('update-password');
        });

        // Customer devices & FCM tokens
        Route::prefix('devices')->name('api.v1.devices.')->group(function () {
            Route::get('/', [DeviceController::class, 'index'])
                ->name('index');

            // Rate limiting más estricto para registro de dispositivos (10 por minuto)
            Route::post('/register', [DeviceController::class, 'register'])
                ->middleware('throttle:10,1')
                ->name('register');

            Route::patch('/{device}/fcm-token', [DeviceController::class, 'updateFcmToken'])
                ->name('update-fcm-token');

            Route::delete('/{device}', [DeviceController::class, 'destroy'])
                ->name('destroy');
        });

        // Customer addresses
        Route::prefix('addresses')->name('api.v1.addresses.')->group(function () {
            Route::get('/', [CustomerAddressController::class, 'index'])->name('index');
            Route::post('/', [CustomerAddressController::class, 'store'])->name('store');
            Route::post('/validate', [CustomerAddressController::class, 'validateLocation'])->name('validate');
            Route::get('/{address}', [CustomerAddressController::class, 'show'])->name('show');
            Route::put('/{address}', [CustomerAddressController::class, 'update'])->name('update');
            Route::delete('/{address}', [CustomerAddressController::class, 'destroy'])->name('destroy');
            Route::post('/{address}/set-default', [CustomerAddressController::class, 'setDefault'])->name('set-default');
        });

        // Restaurant for delivery (requires address_id)
        Route::get('/restaurants/for-delivery', [App\Http\Controllers\Api\V1\Menu\RestaurantController::class, 'forDelivery'])
            ->name('api.v1.restaurants.for-delivery');

        // Customer NITs (Tax IDs)
        Route::prefix('nits')->name('api.v1.nits.')->group(function () {
            Route::get('/', [CustomerNitController::class, 'index'])->name('index');
            Route::post('/', [CustomerNitController::class, 'store'])->name('store');
            Route::get('/{nit}', [CustomerNitController::class, 'show'])->name('show');
            Route::put('/{nit}', [CustomerNitController::class, 'update'])->name('update');
            Route::delete('/{nit}', [CustomerNitController::class, 'destroy'])->name('destroy');
            Route::post('/{nit}/set-default', [CustomerNitController::class, 'setDefault'])->name('set-default');
        });

        // Favorites
        Route::prefix('favorites')->name('api.v1.favorites.')->group(function () {
            Route::get('/', [FavoriteController::class, 'index'])->name('index');
            Route::post('/', [FavoriteController::class, 'store'])->name('store');
            Route::delete('/{type}/{id}', [FavoriteController::class, 'destroy'])->name('destroy');
        });

        // Cart management
        Route::prefix('cart')->name('api.v1.cart.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\CartController::class, 'show'])
                ->name('show');

            Route::post('/items', [App\Http\Controllers\Api\V1\CartController::class, 'addItem'])
                ->name('items.add');

            Route::put('/items/{id}', [App\Http\Controllers\Api\V1\CartController::class, 'updateItem'])
                ->name('items.update');

            Route::delete('/items/{id}', [App\Http\Controllers\Api\V1\CartController::class, 'removeItem'])
                ->name('items.remove');

            Route::delete('/', [App\Http\Controllers\Api\V1\CartController::class, 'clear'])
                ->name('clear');

            Route::put('/restaurant', [App\Http\Controllers\Api\V1\CartController::class, 'updateRestaurant'])
                ->name('restaurant.update');

            Route::put('/service-type', [App\Http\Controllers\Api\V1\CartController::class, 'updateServiceType'])
                ->name('service-type.update');

            Route::put('/delivery-address', [App\Http\Controllers\Api\V1\CartController::class, 'setDeliveryAddress'])
                ->name('delivery-address.update');

            Route::post('/validate', [App\Http\Controllers\Api\V1\CartController::class, 'validate'])
                ->name('validate');
        });

        // Orders
        Route::prefix('orders')->name('api.v1.orders.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\OrderController::class, 'index'])
                ->name('index');

            // Crear orden requiere email verificado
            Route::post('/', [App\Http\Controllers\Api\V1\OrderController::class, 'store'])
                ->middleware('verified.api')
                ->name('store');

            Route::get('/active', [App\Http\Controllers\Api\V1\OrderController::class, 'active'])
                ->name('active');

            Route::get('/pending-review', [App\Http\Controllers\Api\V1\OrderController::class, 'pendingReview'])
                ->name('pending-review');

            Route::get('/cancellation-reasons', [App\Http\Controllers\Api\V1\OrderController::class, 'cancellationReasons'])
                ->name('cancellation-reasons');

            Route::get('/{order}', [App\Http\Controllers\Api\V1\OrderController::class, 'show'])
                ->name('show');

            Route::get('/{order}/track', [App\Http\Controllers\Api\V1\OrderController::class, 'track'])
                ->name('track');

            Route::post('/{order}/cancel', [App\Http\Controllers\Api\V1\OrderController::class, 'cancel'])
                ->name('cancel');

            Route::post('/{order}/reorder', [App\Http\Controllers\Api\V1\OrderController::class, 'reorder'])
                ->name('reorder');

            Route::post('/{order}/review', [App\Http\Controllers\Api\V1\OrderController::class, 'review'])
                ->name('review');
        });

        // Loyalty points (view only - redemption happens in-store)
        Route::prefix('points')->name('api.v1.points.')->group(function () {
            Route::get('/balance', [App\Http\Controllers\Api\V1\PointsController::class, 'balance'])
                ->name('balance');

            Route::get('/history', [App\Http\Controllers\Api\V1\PointsController::class, 'history'])
                ->name('history');

            Route::get('/expiring', [App\Http\Controllers\Api\V1\PointsController::class, 'expiring'])
                ->name('expiring');
        });

        Route::get('/me/recent-orders', [App\Http\Controllers\Api\V1\OrderController::class, 'recentOrders'])
            ->name('api.v1.me.recent-orders');

        // Wallet passes (Apple Wallet & Google Wallet)
        Route::prefix('wallet')->name('api.v1.wallet.')->middleware('throttle:wallet')->group(function () {
            Route::post('/apple/pass', [WalletController::class, 'applePass'])
                ->name('apple.pass');
            Route::post('/google/pass', [WalletController::class, 'googlePass'])
                ->name('google.pass');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Wallet Download (Signed URL - No Bearer Token Required)
    |--------------------------------------------------------------------------
    */
    Route::get('/wallet/apple/download/{customer}', [WalletController::class, 'applePassDownload'])
        ->middleware(['signed', 'throttle:60,1'])
        ->name('api.v1.wallet.apple.download');

    /*
    |--------------------------------------------------------------------------
    | Menu API Routes (Public - No Authentication Required)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['throttle:60,1'])->prefix('menu')->name('api.v1.menu.')->group(function () {
        // Versión del menú (para cache en Flutter)
        Route::get('/version', [App\Http\Controllers\Api\V1\Menu\MenuController::class, 'version'])
            ->name('version');

        // Menú completo
        Route::get('/', [App\Http\Controllers\Api\V1\Menu\MenuController::class, 'index'])
            ->name('index');

        // Featured products/combos (for Home screen carousels)
        Route::get('/featured', [App\Http\Controllers\Api\V1\Menu\MenuController::class, 'featured'])
            ->name('featured');

        // Promotional banners (for Home screen carousel)
        Route::get('/banners', [App\Http\Controllers\Api\V1\Menu\MenuController::class, 'banners'])
            ->name('banners');

        // Categorías
        Route::get('/categories', [App\Http\Controllers\Api\V1\Menu\CategoryController::class, 'index'])
            ->name('categories.index');
        Route::get('/categories/{category}', [App\Http\Controllers\Api\V1\Menu\CategoryController::class, 'show'])
            ->name('categories.show');

        // Productos
        Route::get('/products', [App\Http\Controllers\Api\V1\Menu\ProductController::class, 'index'])
            ->name('products.index');
        Route::get('/products/{product}', [App\Http\Controllers\Api\V1\Menu\ProductController::class, 'show'])
            ->name('products.show');

        // Combos
        Route::get('/combos', [App\Http\Controllers\Api\V1\Menu\ComboController::class, 'index'])
            ->name('combos.index');
        Route::get('/combos/{combo}', [App\Http\Controllers\Api\V1\Menu\ComboController::class, 'show'])
            ->name('combos.show');

        // Promociones
        Route::get('/promotions', [App\Http\Controllers\Api\V1\Menu\PromotionController::class, 'index'])
            ->name('promotions.index');
        Route::get('/promotions/daily', [App\Http\Controllers\Api\V1\Menu\PromotionController::class, 'daily'])
            ->name('promotions.daily');
        Route::get('/promotions/combinados', [App\Http\Controllers\Api\V1\Menu\PromotionController::class, 'combinados'])
            ->name('promotions.combinados');
        Route::get('/promotions/combinados/{id}', [App\Http\Controllers\Api\V1\Menu\PromotionController::class, 'showCombinado'])
            ->name('promotions.combinados.show');

        // Rewards catalog (redeemable items - redemption happens in-store)
        Route::get('/rewards', [App\Http\Controllers\Api\V1\RewardsController::class, 'index'])
            ->name('rewards.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Restaurants API Routes (Public - No Authentication Required)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['throttle:60,1'])->prefix('restaurants')->name('api.v1.restaurants.')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\Menu\RestaurantController::class, 'index'])
            ->name('index');
        Route::get('/nearby', [App\Http\Controllers\Api\V1\Menu\RestaurantController::class, 'nearby'])
            ->name('nearby');
        Route::get('/for-pickup', [App\Http\Controllers\Api\V1\Menu\RestaurantController::class, 'forPickup'])
            ->name('for-pickup');
        Route::get('/{restaurant}', [App\Http\Controllers\Api\V1\Menu\RestaurantController::class, 'show'])
            ->name('show');
        Route::get('/{restaurant}/reviews', [App\Http\Controllers\Api\V1\Menu\RestaurantController::class, 'reviews'])
            ->name('reviews');
    });

    /*
    |--------------------------------------------------------------------------
    | Legal Documents (Public - No Authentication Required)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['throttle:60,1'])->prefix('legal')->name('api.v1.legal.')->group(function () {
        Route::get('/terms', [App\Http\Controllers\Api\V1\Support\LegalDocumentController::class, 'terms'])
            ->name('terms');
        Route::get('/privacy', [App\Http\Controllers\Api\V1\Support\LegalDocumentController::class, 'privacy'])
            ->name('privacy');
    });

    /*
    |--------------------------------------------------------------------------
    | Support Tickets (Authentication Required)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('support')->name('api.v1.support.')->group(function () {
        Route::get('/reasons', [App\Http\Controllers\Api\V1\Support\SupportReasonController::class, 'index'])
            ->name('reasons.index');
        Route::get('/tickets', [App\Http\Controllers\Api\V1\Support\SupportTicketController::class, 'index'])
            ->name('tickets.index');
        Route::post('/tickets', [App\Http\Controllers\Api\V1\Support\SupportTicketController::class, 'store'])
            ->name('tickets.store');
        Route::get('/tickets/{ticket}', [App\Http\Controllers\Api\V1\Support\SupportTicketController::class, 'show'])
            ->name('tickets.show');
        Route::post('/tickets/{ticket}/messages', [App\Http\Controllers\Api\V1\Support\SupportTicketController::class, 'sendMessage'])
            ->name('tickets.messages.store');
    });
});
