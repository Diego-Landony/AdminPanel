<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Redirigir la página principal al login si no está autenticado
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

// Rutas para el manejo del tema
Route::post('/theme/update', [ThemeController::class, 'update'])->name('theme.update');
Route::get('/theme/get', [ThemeController::class, 'get'])->name('theme.get');

Route::middleware(['auth', 'verified'])->group(function () {
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
