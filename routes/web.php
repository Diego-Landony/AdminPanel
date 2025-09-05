<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerTypeController;
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
})->name('home');

// Rutas para el manejo del tema
Route::post('/theme/update', [ThemeController::class, 'update'])->name('theme.update');
Route::get('/theme/get', [ThemeController::class, 'get'])->name('theme.get');

Route::middleware(['auth', 'verified'])->group(function () {
    // Home - página principal después del login
    Route::get('home', function () {
        return Inertia::render('home');
    })->name('home')->middleware('permission:home.view');

    // Dashboard - requiere permisos específicos
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard')->middleware('permission:dashboard.view');

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

    // Actividad - requiere permiso específico
    Route::get('activity', [ActivityController::class, 'index'])->name('activity.index')
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
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
