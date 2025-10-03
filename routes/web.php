<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerTypeController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\KMLUploadController;
use App\Http\Controllers\Menu\CategoryController;
use App\Http\Controllers\Menu\ProductController;
use App\Http\Controllers\Menu\ProductVariantController;
use App\Http\Controllers\Menu\PromotionController;
use App\Http\Controllers\Menu\SectionController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\RestaurantGeofencesController;
use App\Http\Controllers\RoleController;
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

    // KML Upload para restaurantes
    Route::get('restaurants/{restaurant}/kml', [KMLUploadController::class, 'show'])->name('restaurants.kml.show')
        ->middleware('permission:restaurants.edit');
    Route::post('restaurants/{restaurant}/kml', [KMLUploadController::class, 'upload'])->name('restaurants.kml.upload')
        ->middleware('permission:restaurants.edit');
    Route::delete('restaurants/{restaurant}/kml', [KMLUploadController::class, 'remove'])->name('restaurants.kml.remove')
        ->middleware('permission:restaurants.edit');
    Route::get('restaurants/{restaurant}/kml/preview', [KMLUploadController::class, 'preview'])->name('restaurants.kml.preview')
        ->middleware('permission:restaurants.view');

    // Vista general de geocercas
    Route::get('restaurants-geofences', [RestaurantGeofencesController::class, 'index'])->name('restaurants.geofences')
        ->middleware('permission:restaurants.view');

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

        // Category Products Management (attach/detach products to categories)
        Route::post('categories/{category}/products/attach', [CategoryController::class, 'attachProduct'])
            ->name('categories.products.attach')
            ->middleware('permission:menu.categories.edit');
        Route::delete('categories/{category}/products/{product}', [CategoryController::class, 'detachProduct'])
            ->name('categories.products.detach')
            ->middleware('permission:menu.categories.edit');
        Route::patch('categories/{category}/products/{product}/prices', [CategoryController::class, 'updateProductPrices'])
            ->name('categories.products.update-prices')
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

        // Promotions
        Route::get('promotions', [PromotionController::class, 'index'])->name('promotions.index')
            ->middleware('permission:menu.promotions.view');
        Route::get('promotions/create', [PromotionController::class, 'create'])->name('promotions.create')
            ->middleware('permission:menu.promotions.create');
        Route::post('promotions', [PromotionController::class, 'store'])->name('promotions.store')
            ->middleware('permission:menu.promotions.create');
        Route::get('promotions/{promotion}', [PromotionController::class, 'show'])->name('promotions.show')
            ->middleware('permission:menu.promotions.view');
        Route::get('promotions/{promotion}/edit', [PromotionController::class, 'edit'])->name('promotions.edit')
            ->middleware('permission:menu.promotions.edit');
        Route::put('promotions/{promotion}', [PromotionController::class, 'update'])->name('promotions.update')
            ->middleware('permission:menu.promotions.edit');
        Route::patch('promotions/{promotion}', [PromotionController::class, 'update'])
            ->middleware('permission:menu.promotions.edit');
        Route::delete('promotions/{promotion}', [PromotionController::class, 'destroy'])->name('promotions.destroy')
            ->middleware('permission:menu.promotions.delete');
        Route::post('promotions/{promotion}/toggle', [PromotionController::class, 'toggle'])->name('promotions.toggle')
            ->middleware('permission:menu.promotions.edit');

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
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
