<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerDeviceController;
use App\Http\Controllers\CustomerNitController;
use App\Http\Controllers\CustomerTypeController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\Marketing\PromotionalBannerController;
use App\Http\Controllers\Menu\BadgeTypeController;
use App\Http\Controllers\Menu\CategoryController;
use App\Http\Controllers\Menu\ComboController;
use App\Http\Controllers\Menu\MenuOrderController;
use App\Http\Controllers\Menu\ProductController;
use App\Http\Controllers\Menu\ProductVariantController;
use App\Http\Controllers\Menu\PromotionController;
use App\Http\Controllers\Menu\SectionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\RestaurantGeofencesController;
use App\Http\Controllers\RestaurantUserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Support\LegalDocumentController;
use App\Http\Controllers\Support\SupportReasonController;
use App\Http\Controllers\Support\SupportTicketController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Redirigir la página principal al login si no está autenticado
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('home');
    }

    return redirect()->route('login');
})->name('root');

// Rutas para el manejo del tema
Route::post('/theme/update', [ThemeController::class, 'update'])->name('theme.update');
Route::get('/theme/get', [ThemeController::class, 'get'])->name('theme.get');

// Customer Password Reset Web Page (for mobile app users clicking email link)
Route::get('/password/reset', [App\Http\Controllers\CustomerPasswordResetController::class, 'showResetForm'])
    ->name('customer.password.reset');
Route::post('/password/reset', [App\Http\Controllers\CustomerPasswordResetController::class, 'reset'])
    ->name('customer.password.update');

// OAuth Success Route (Web platform)
Route::get('/oauth/success', function (Illuminate\Http\Request $request) {
    // Recuperar datos de los query parameters (en lugar de sesión)
    // Esto funciona igual que mobile y evita problemas de sesión perdida
    $token = $request->query('token');
    $customerId = $request->query('customer_id');
    $isNew = $request->query('is_new');
    $message = $request->query('message');
    $error = $request->query('error');

    // Retornar vista que procesa el token
    return view('auth.oauth-success', [
        'token' => $token,
        'customerId' => $customerId,
        'isNewCustomer' => $isNew,
        'message' => $message,
        'error' => $error,
    ]);
})->name('oauth.success');

// Rutas de subida de imágenes (requieren autenticación)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/api/upload/image', [ImageUploadController::class, 'upload'])->name('upload.image');
    Route::post('/api/delete/image', [ImageUploadController::class, 'delete'])->name('delete.image');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Home - página principal después del login
    Route::get('home', function () {
        return Inertia::render('home');
    })->name('home')->middleware('permission:home.view');

    // Página de sin acceso
    Route::get('no-access', function () {
        return Inertia::render('no-access');
    })->name('no-access');

    // Gestión de usuarios - requiere permisos específicos
    Route::get('users', [UserController::class, 'index'])->name('users.index')
        ->middleware('permission:users.view');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create')
        ->middleware('permission:users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store')
        ->middleware('permission:users.create');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')
        ->middleware('permission:users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update')
        ->middleware('permission:users.edit');
    Route::patch('users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.edit');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy')
        ->middleware('permission:users.delete');
    Route::patch('users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.update-roles')
        ->middleware('permission:users.edit');
    Route::post('users/keep-alive', [UserController::class, 'keepAlive'])->name('users.keep-alive');

    // Gestión de clientes - requiere permisos específicos
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index')
        ->middleware('permission:customers.view');
    Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create')
        ->middleware('permission:customers.create');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show')
        ->middleware('permission:customers.view');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store')
        ->middleware('permission:customers.create');
    Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit')
        ->middleware('permission:customers.edit');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update')
        ->middleware('permission:customers.edit');
    Route::patch('customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('permission:customers.edit');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy')
        ->middleware('permission:customers.delete');

    // Gestión de direcciones de clientes
    Route::post('customers/{customer}/addresses', [CustomerAddressController::class, 'store'])->name('customers.addresses.store')
        ->middleware('permission:customers.edit');
    Route::put('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'update'])->name('customers.addresses.update')
        ->middleware('permission:customers.edit');
    Route::delete('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('customers.addresses.destroy')
        ->middleware('permission:customers.edit');

    // Gestión de dispositivos de clientes
    Route::post('customers/{customer}/devices', [CustomerDeviceController::class, 'store'])->name('customers.devices.store')
        ->middleware('permission:customers.edit');
    Route::put('customers/{customer}/devices/{device}', [CustomerDeviceController::class, 'update'])->name('customers.devices.update')
        ->middleware('permission:customers.edit');
    Route::delete('customers/{customer}/devices/{device}', [CustomerDeviceController::class, 'destroy'])->name('customers.devices.destroy')
        ->middleware('permission:customers.edit');
    Route::delete('customers/{customer}/devices-inactive', [CustomerDeviceController::class, 'destroyInactive'])->name('customers.devices.destroy-inactive')
        ->middleware('permission:customers.edit');

    // Gestión de NITs de clientes
    Route::post('customers/{customer}/nits', [CustomerNitController::class, 'store'])->name('customers.nits.store')
        ->middleware('permission:customers.edit');
    Route::put('customers/{customer}/nits/{nit}', [CustomerNitController::class, 'update'])->name('customers.nits.update')
        ->middleware('permission:customers.edit');
    Route::delete('customers/{customer}/nits/{nit}', [CustomerNitController::class, 'destroy'])->name('customers.nits.destroy')
        ->middleware('permission:customers.edit');

    // Gestión de tipos de clientes - requiere permisos específicos
    Route::get('customer-types', [CustomerTypeController::class, 'index'])->name('customer-types.index')
        ->middleware('permission:customer-types.view');
    Route::get('customer-types/create', [CustomerTypeController::class, 'create'])->name('customer-types.create')
        ->middleware('permission:customer-types.create');
    Route::post('customer-types', [CustomerTypeController::class, 'store'])->name('customer-types.store')
        ->middleware('permission:customer-types.create');
    Route::get('customer-types/{customerType}', [CustomerTypeController::class, 'show'])->name('customer-types.show')
        ->middleware('permission:customer-types.view');
    Route::get('customer-types/{customerType}/edit', [CustomerTypeController::class, 'edit'])->name('customer-types.edit')
        ->middleware('permission:customer-types.edit');
    Route::put('customer-types/{customerType}', [CustomerTypeController::class, 'update'])->name('customer-types.update')
        ->middleware('permission:customer-types.edit');
    Route::patch('customer-types/{customerType}', [CustomerTypeController::class, 'update'])
        ->middleware('permission:customer-types.edit');
    Route::delete('customer-types/{customerType}', [CustomerTypeController::class, 'destroy'])->name('customer-types.destroy')
        ->middleware('permission:customer-types.delete');

    // Gestión de restaurantes - requiere permisos específicos
    Route::get('restaurants', [RestaurantController::class, 'index'])->name('restaurants.index')
        ->middleware('permission:restaurants.view');
    Route::get('restaurants/create', [RestaurantController::class, 'create'])->name('restaurants.create')
        ->middleware('permission:restaurants.create');
    Route::post('restaurants', [RestaurantController::class, 'store'])->name('restaurants.store')
        ->middleware('permission:restaurants.create');
    Route::get('restaurants/{restaurant}/edit', [RestaurantController::class, 'edit'])->name('restaurants.edit')
        ->middleware('permission:restaurants.edit');
    Route::put('restaurants/{restaurant}', [RestaurantController::class, 'update'])->name('restaurants.update')
        ->middleware('permission:restaurants.edit');
    Route::patch('restaurants/{restaurant}', [RestaurantController::class, 'update'])
        ->middleware('permission:restaurants.edit');
    Route::delete('restaurants/{restaurant}', [RestaurantController::class, 'destroy'])->name('restaurants.destroy')
        ->middleware('permission:restaurants.delete');
    Route::post('restaurants/{restaurant}/geofence', [RestaurantController::class, 'saveGeofence'])->name('restaurants.geofence.save')
        ->middleware('permission:restaurants.edit');

    // Vista general de geocercas
    Route::get('restaurants-geofences', [RestaurantGeofencesController::class, 'index'])->name('restaurants.geofences')
        ->middleware('permission:restaurants.view');

    // Gestión de usuarios de restaurante
    Route::get('restaurants/{restaurant}/users', [RestaurantUserController::class, 'index'])->name('restaurants.users.index')
        ->middleware('permission:restaurants.view');
    Route::post('restaurants/{restaurant}/users', [RestaurantUserController::class, 'store'])->name('restaurants.users.store')
        ->middleware('permission:restaurants.edit');
    Route::put('restaurants/{restaurant}/users/{restaurantUser}', [RestaurantUserController::class, 'update'])->name('restaurants.users.update')
        ->middleware('permission:restaurants.edit');
    Route::delete('restaurants/{restaurant}/users/{restaurantUser}', [RestaurantUserController::class, 'destroy'])->name('restaurants.users.destroy')
        ->middleware('permission:restaurants.delete');
    Route::post('restaurants/{restaurant}/users/{restaurantUser}/reset-password', [RestaurantUserController::class, 'resetPassword'])->name('restaurants.users.reset-password')
        ->middleware('permission:restaurants.edit');

    // Gestión de motoristas - requiere permisos específicos
    Route::get('drivers', [DriverController::class, 'index'])->name('drivers.index')
        ->middleware('permission:drivers.view');
    Route::get('drivers/create', [DriverController::class, 'create'])->name('drivers.create')
        ->middleware('permission:drivers.create');
    Route::post('drivers', [DriverController::class, 'store'])->name('drivers.store')
        ->middleware('permission:drivers.create');
    Route::get('drivers/{driver}', [DriverController::class, 'show'])->name('drivers.show')
        ->middleware('permission:drivers.view');
    Route::get('drivers/{driver}/edit', [DriverController::class, 'edit'])->name('drivers.edit')
        ->middleware('permission:drivers.edit');
    Route::put('drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update')
        ->middleware('permission:drivers.edit');
    Route::patch('drivers/{driver}', [DriverController::class, 'update'])
        ->middleware('permission:drivers.edit');
    Route::delete('drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy')
        ->middleware('permission:drivers.delete');
    Route::post('drivers/{driver}/toggle-availability', [DriverController::class, 'toggleAvailability'])->name('drivers.toggle-availability')
        ->middleware('permission:drivers.edit');

    // Gestión de órdenes - requiere permisos específicos
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index')
        ->middleware('permission:orders.view');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show')
        ->middleware('permission:orders.view');
    Route::post('orders/{order}/assign-driver', [OrderController::class, 'assignDriver'])->name('orders.assign-driver')
        ->middleware('permission:orders.edit');
    Route::post('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status')
        ->middleware('permission:orders.edit');

    // Actividad - requiere permiso específico
    Route::get('activity', [ActivityController::class, 'index'])->name('activity.index')
        ->middleware('permission:activity.view');
    Route::post('activity', [ActivityController::class, 'store'])
        ->middleware('permission:activity.view');

    // Gestión de roles - requiere permisos específicos
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index')
        ->middleware('permission:roles.view');
    Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create')
        ->middleware('permission:roles.create');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store')
        ->middleware('permission:roles.create');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit')
        ->middleware('permission:roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update')
        ->middleware('permission:roles.edit');
    Route::patch('roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:roles.edit');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy')
        ->middleware('permission:roles.delete');
    Route::patch('roles/{role}/users', [RoleController::class, 'updateUsers'])->name('roles.update-users')
        ->middleware('permission:roles.edit');

    // Gestión de Menú
    Route::prefix('menu')->name('menu.')->group(function () {
        // Menu Order (Vista unificada de ordenamiento)
        Route::get('order', [MenuOrderController::class, 'index'])->name('order.index');
        Route::post('order/badges', [MenuOrderController::class, 'updateBadges'])->name('order.badges');
        Route::post('order/toggle-item', [MenuOrderController::class, 'toggleItem'])->name('order.toggle-item');
        Route::post('order/toggle-category', [MenuOrderController::class, 'toggleCategory'])->name('order.toggle-category');

        // Badge Types
        Route::get('badge-types', [BadgeTypeController::class, 'index'])->name('badge-types.index')
            ->middleware('permission:menu.categories.view');
        Route::get('badge-types/create', [BadgeTypeController::class, 'create'])->name('badge-types.create')
            ->middleware('permission:menu.categories.create');
        Route::post('badge-types', [BadgeTypeController::class, 'store'])->name('badge-types.store')
            ->middleware('permission:menu.categories.create');
        Route::get('badge-types/{badgeType}/edit', [BadgeTypeController::class, 'edit'])->name('badge-types.edit')
            ->middleware('permission:menu.categories.edit');
        Route::put('badge-types/{badgeType}', [BadgeTypeController::class, 'update'])->name('badge-types.update')
            ->middleware('permission:menu.categories.edit');
        Route::delete('badge-types/{badgeType}', [BadgeTypeController::class, 'destroy'])->name('badge-types.destroy')
            ->middleware('permission:menu.categories.delete');
        Route::post('badge-types/reorder', [BadgeTypeController::class, 'reorder'])->name('badge-types.reorder')
            ->middleware('permission:menu.categories.edit');

        // Categories
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index')
            ->middleware('permission:menu.categories.view');
        Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create')
            ->middleware('permission:menu.categories.create');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store')
            ->middleware('permission:menu.categories.create');
        Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show')
            ->middleware('permission:menu.categories.view');
        Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit')
            ->middleware('permission:menu.categories.edit');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update')
            ->middleware('permission:menu.categories.edit');
        Route::patch('categories/{category}', [CategoryController::class, 'update'])
            ->middleware('permission:menu.categories.edit');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy')
            ->middleware('permission:menu.categories.delete');
        Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder')
            ->middleware('permission:menu.categories.edit');

        // Products
        Route::get('products', [ProductController::class, 'index'])->name('products.index')
            ->middleware('permission:menu.products.view');
        Route::get('products/create', [ProductController::class, 'create'])->name('products.create')
            ->middleware('permission:menu.products.create');
        Route::post('products', [ProductController::class, 'store'])->name('products.store')
            ->middleware('permission:menu.products.create');
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show')
            ->middleware('permission:menu.products.view');
        Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit')
            ->middleware('permission:menu.products.edit');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update')
            ->middleware('permission:menu.products.edit');
        Route::patch('products/{product}', [ProductController::class, 'update'])
            ->middleware('permission:menu.products.edit');
        Route::get('products/{product}/usage-info', [ProductController::class, 'usageInfo'])->name('products.usage-info')
            ->middleware('permission:menu.products.view');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy')
            ->middleware('permission:menu.products.delete');
        Route::post('products/{product}/clone', [ProductController::class, 'clone'])->name('products.clone')
            ->middleware('permission:menu.products.create');
        Route::post('products/reorder', [ProductController::class, 'reorder'])->name('products.reorder')
            ->middleware('permission:menu.products.edit');

        // Product Variants Management
        Route::get('products/{product}/variants', [ProductVariantController::class, 'index'])
            ->name('products.variants.index')
            ->middleware('permission:menu.products.view');
        Route::get('products/{product}/variants/{variant}/edit', [ProductVariantController::class, 'edit'])
            ->name('products.variants.edit')
            ->middleware('permission:menu.products.edit');
        Route::patch('products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])
            ->name('products.variants.update')
            ->middleware('permission:menu.products.edit');
        Route::delete('products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])
            ->name('products.variants.destroy')
            ->middleware('permission:menu.products.delete');
        Route::post('products/{product}/variants/reorder', [ProductVariantController::class, 'reorder'])
            ->name('products.variants.reorder')
            ->middleware('permission:menu.products.edit');
        Route::patch('products/{product}/variants/{variant}/quick-prices', [ProductVariantController::class, 'quickUpdatePrices'])
            ->name('products.variants.quick-update-prices')
            ->middleware('permission:menu.products.edit');

        // Promotions - Estructura jerárquica por tipo
        Route::prefix('promotions')->name('promotions.')->group(function () {
            // Sub del Día
            Route::get('/daily-special', [PromotionController::class, 'dailySpecialIndex'])->name('daily-special.index')
                ->middleware('permission:menu.promotions.view');
            Route::post('/daily-special', [PromotionController::class, 'dailySpecialIndex'])
                ->middleware('permission:menu.promotions.view');
            Route::get('/daily-special/create', [PromotionController::class, 'createDailySpecial'])->name('daily-special.create')
                ->middleware('permission:menu.promotions.create');

            // 2x1 (preparación futura)
            Route::get('/two-for-one', [PromotionController::class, 'twoForOneIndex'])->name('two-for-one.index')
                ->middleware('permission:menu.promotions.view');
            Route::post('/two-for-one', [PromotionController::class, 'twoForOneIndex'])
                ->middleware('permission:menu.promotions.view');
            Route::get('/two-for-one/create', [PromotionController::class, 'createTwoForOne'])->name('two-for-one.create')
                ->middleware('permission:menu.promotions.create');

            // Porcentaje (preparación futura)
            Route::get('/percentage', [PromotionController::class, 'percentageIndex'])->name('percentage.index')
                ->middleware('permission:menu.promotions.view');
            Route::post('/percentage', [PromotionController::class, 'percentageIndex'])
                ->middleware('permission:menu.promotions.view');
            Route::get('/percentage/create', [PromotionController::class, 'createPercentage'])->name('percentage.create')
                ->middleware('permission:menu.promotions.create');

            // Combinados (bundle specials)
            Route::get('/bundle-specials', [PromotionController::class, 'bundleSpecialsIndex'])->name('bundle-specials.index')
                ->middleware('permission:menu.promotions.view');
            Route::get('/bundle-specials/create', [PromotionController::class, 'createBundleSpecial'])->name('bundle-specials.create')
                ->middleware('permission:menu.promotions.create');
            Route::post('/bundle-specials', [PromotionController::class, 'storeBundleSpecial'])->name('bundle-specials.store')
                ->middleware('permission:menu.promotions.create');
            Route::get('/bundle-specials/{promotion}/edit', [PromotionController::class, 'editBundleSpecial'])->name('bundle-specials.edit')
                ->middleware('permission:menu.promotions.edit');
            Route::put('/bundle-specials/{promotion}', [PromotionController::class, 'updateBundleSpecial'])->name('bundle-specials.update')
                ->middleware('permission:menu.promotions.edit');
            Route::delete('/bundle-specials/{promotion}', [PromotionController::class, 'destroy'])->name('bundle-specials.destroy')
                ->middleware('permission:menu.promotions.delete');
            Route::post('/bundle-specials/{promotion}/toggle', [PromotionController::class, 'toggleBundleSpecial'])->name('bundle-specials.toggle')
                ->middleware('permission:menu.promotions.edit');
            Route::post('/bundle-specials/reorder', [PromotionController::class, 'reorderBundleSpecials'])->name('bundle-specials.reorder')
                ->middleware('permission:menu.promotions.edit');

            // Rutas compartidas (aplican a todos los tipos)
            Route::post('/preview', [PromotionController::class, 'preview'])->name('preview')
                ->middleware('permission:menu.promotions.create');
            Route::post('/', [PromotionController::class, 'store'])->name('store')
                ->middleware('permission:menu.promotions.create');
            Route::get('/{promotion}', [PromotionController::class, 'show'])->name('show')
                ->middleware('permission:menu.promotions.view');
            Route::get('/{promotion}/edit', [PromotionController::class, 'edit'])->name('edit')
                ->middleware('permission:menu.promotions.edit');
            Route::put('/{promotion}', [PromotionController::class, 'update'])->name('update')
                ->middleware('permission:menu.promotions.edit');
            Route::patch('/{promotion}', [PromotionController::class, 'update'])
                ->middleware('permission:menu.promotions.edit');
            Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('destroy')
                ->middleware('permission:menu.promotions.delete');
            Route::post('/{promotion}/toggle', [PromotionController::class, 'toggle'])->name('toggle')
                ->middleware('permission:menu.promotions.edit');
        });

        // Sections
        Route::get('sections', [SectionController::class, 'index'])->name('sections.index')
            ->middleware('permission:menu.sections.view');
        Route::get('sections/create', [SectionController::class, 'create'])->name('sections.create')
            ->middleware('permission:menu.sections.create');
        Route::post('sections', [SectionController::class, 'store'])->name('sections.store')
            ->middleware('permission:menu.sections.create');
        Route::get('sections/{section}', [SectionController::class, 'show'])->name('sections.show')
            ->middleware('permission:menu.sections.view');
        Route::get('sections/{section}/edit', [SectionController::class, 'edit'])->name('sections.edit')
            ->middleware('permission:menu.sections.edit');
        Route::put('sections/{section}', [SectionController::class, 'update'])->name('sections.update')
            ->middleware('permission:menu.sections.edit');
        Route::patch('sections/{section}', [SectionController::class, 'update'])
            ->middleware('permission:menu.sections.edit');
        Route::delete('sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy')
            ->middleware('permission:menu.sections.delete');
        Route::post('sections/reorder', [SectionController::class, 'reorder'])->name('sections.reorder')
            ->middleware('permission:menu.sections.edit');
        Route::get('sections/{section}/usage', [SectionController::class, 'usage'])->name('sections.usage')
            ->middleware('permission:menu.sections.view');

        // Combos
        Route::get('combos', [ComboController::class, 'index'])->name('combos.index')
            ->middleware('permission:menu.combos.view');
        Route::get('combos/create', [ComboController::class, 'create'])->name('combos.create')
            ->middleware('permission:menu.combos.create');
        Route::post('combos', [ComboController::class, 'store'])->name('combos.store')
            ->middleware('permission:menu.combos.create');
        Route::get('combos/{combo}', [ComboController::class, 'show'])->name('combos.show')
            ->middleware('permission:menu.combos.view');
        Route::get('combos/{combo}/edit', [ComboController::class, 'edit'])->name('combos.edit')
            ->middleware('permission:menu.combos.edit');
        Route::put('combos/{combo}', [ComboController::class, 'update'])->name('combos.update')
            ->middleware('permission:menu.combos.edit');
        Route::patch('combos/{combo}', [ComboController::class, 'update'])
            ->middleware('permission:menu.combos.edit');
        Route::delete('combos/{combo}', [ComboController::class, 'destroy'])->name('combos.destroy')
            ->middleware('permission:menu.combos.delete');
        Route::post('combos/{combo}/toggle', [ComboController::class, 'toggle'])->name('combos.toggle')
            ->middleware('permission:menu.combos.edit');
        Route::post('combos/reorder', [ComboController::class, 'reorder'])->name('combos.reorder')
            ->middleware('permission:menu.combos.edit');
    });

    // Marketing
    Route::prefix('marketing')->name('marketing.')->group(function () {
        // Promotional Banners
        Route::get('banners', [PromotionalBannerController::class, 'index'])->name('banners.index')
            ->middleware('permission:marketing.banners.view');
        Route::get('banners/create', [PromotionalBannerController::class, 'create'])->name('banners.create')
            ->middleware('permission:marketing.banners.create');
        Route::post('banners', [PromotionalBannerController::class, 'store'])->name('banners.store')
            ->middleware('permission:marketing.banners.create');
        Route::get('banners/{banner}/edit', [PromotionalBannerController::class, 'edit'])->name('banners.edit')
            ->middleware('permission:marketing.banners.edit');
        Route::put('banners/{banner}', [PromotionalBannerController::class, 'update'])->name('banners.update')
            ->middleware('permission:marketing.banners.edit');
        Route::delete('banners/{banner}', [PromotionalBannerController::class, 'destroy'])->name('banners.destroy')
            ->middleware('permission:marketing.banners.delete');
        Route::post('banners/reorder', [PromotionalBannerController::class, 'reorder'])->name('banners.reorder')
            ->middleware('permission:marketing.banners.edit');
        Route::post('banners/{banner}/toggle', [PromotionalBannerController::class, 'toggleActive'])->name('banners.toggle')
            ->middleware('permission:marketing.banners.edit');
    });

    // Support
    Route::prefix('support')->name('support.')->group(function () {
        // Términos y Condiciones (documento único)
        Route::get('terms-and-conditions', [LegalDocumentController::class, 'termsIndex'])->name('terms.index')
            ->middleware('permission:support.legal.view');
        Route::get('terms-and-conditions/edit', [LegalDocumentController::class, 'termsEdit'])->name('terms.edit')
            ->middleware('permission:support.legal.edit');
        Route::put('terms-and-conditions', [LegalDocumentController::class, 'termsUpdate'])->name('terms.update')
            ->middleware('permission:support.legal.edit');

        // Política de Privacidad (documento único)
        Route::get('privacy-policy', [LegalDocumentController::class, 'privacyIndex'])->name('privacy.index')
            ->middleware('permission:support.legal.view');
        Route::get('privacy-policy/edit', [LegalDocumentController::class, 'privacyEdit'])->name('privacy.edit')
            ->middleware('permission:support.legal.edit');
        Route::put('privacy-policy', [LegalDocumentController::class, 'privacyUpdate'])->name('privacy.update')
            ->middleware('permission:support.legal.edit');

        // Tickets de Soporte
        Route::get('tickets', [SupportTicketController::class, 'index'])->name('tickets.index')
            ->middleware('permission:support.tickets.view');
        Route::get('tickets/{ticket}', [SupportTicketController::class, 'show'])->name('tickets.show')
            ->middleware('permission:support.tickets.view');
        Route::post('tickets/{ticket}/messages', [SupportTicketController::class, 'sendMessage'])->name('tickets.messages.store')
            ->middleware('permission:support.tickets.manage');
        Route::post('tickets/{ticket}/take', [SupportTicketController::class, 'takeTicket'])->name('tickets.take')
            ->middleware('permission:support.tickets.manage');
        Route::patch('tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->name('tickets.status')
            ->middleware('permission:support.tickets.manage');
        Route::patch('tickets/{ticket}/priority', [SupportTicketController::class, 'updatePriority'])->name('tickets.priority')
            ->middleware('permission:support.tickets.manage');
        Route::delete('tickets/{ticket}', [SupportTicketController::class, 'destroy'])->name('tickets.destroy')
            ->middleware('permission:support.tickets.manage');

        // Motivos de Soporte
        Route::get('reasons', [SupportReasonController::class, 'index'])->name('reasons.index')
            ->middleware('permission:support.tickets.manage');
        Route::post('reasons', [SupportReasonController::class, 'store'])->name('reasons.store')
            ->middleware('permission:support.tickets.manage');
        Route::put('reasons/{reason}', [SupportReasonController::class, 'update'])->name('reasons.update')
            ->middleware('permission:support.tickets.manage');
        Route::delete('reasons/{reason}', [SupportReasonController::class, 'destroy'])->name('reasons.destroy')
            ->middleware('permission:support.tickets.manage');
        Route::post('reasons/order', [SupportReasonController::class, 'updateOrder'])->name('reasons.order')
            ->middleware('permission:support.tickets.manage');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Restaurant Panel Routes
|--------------------------------------------------------------------------
*/
Route::prefix('restaurant')->name('restaurant.')->group(function () {
    // Guest routes (no autenticado como restaurant)
    Route::middleware('guest:restaurant')->group(function () {
        Route::get('login', [App\Http\Controllers\Restaurant\AuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [App\Http\Controllers\Restaurant\AuthController::class, 'login']);
    });

    // Authenticated routes (autenticado como restaurant)
    Route::middleware('auth:restaurant')->group(function () {
        Route::post('logout', [App\Http\Controllers\Restaurant\AuthController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/', [App\Http\Controllers\Restaurant\DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard', [App\Http\Controllers\Restaurant\DashboardController::class, 'index'])->name('dashboard.index');

        // Orders
        Route::get('orders', [App\Http\Controllers\Restaurant\OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [App\Http\Controllers\Restaurant\OrderController::class, 'show'])->name('orders.show');
        Route::post('orders/{order}/accept', [App\Http\Controllers\Restaurant\OrderController::class, 'accept'])->name('orders.accept');
        Route::post('orders/{order}/ready', [App\Http\Controllers\Restaurant\OrderController::class, 'markReady'])->name('orders.ready');
        Route::post('orders/{order}/assign-driver', [App\Http\Controllers\Restaurant\OrderController::class, 'assignDriver'])->name('orders.assign-driver');

        // Drivers (solo lectura)
        Route::get('drivers', [App\Http\Controllers\Restaurant\DriverController::class, 'index'])->name('drivers.index');

        // Profile
        Route::get('profile', [App\Http\Controllers\Restaurant\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [App\Http\Controllers\Restaurant\ProfileController::class, 'update'])->name('profile.update');
    });
});
