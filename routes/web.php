<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuditController;

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
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users/keep-alive', [UserController::class, 'keepAlive'])->name('users.keep-alive');
    
    Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
