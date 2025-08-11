<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ThemeController;

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
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
