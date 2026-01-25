<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\RestaurantUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Procesa el login de restaurante
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Verificar que el usuario existe y está activo
        $user = RestaurantUser::where('email', $credentials['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ])->redirectTo(route('login'));
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta está desactivada. Contacta al administrador.'],
            ])->redirectTo(route('login'));
        }

        if (! Auth::guard('restaurant')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ])->redirectTo(route('login'));
        }

        $request->session()->regenerate();

        // Actualizar último login
        $user->update(['last_login_at' => now()]);

        return redirect()->intended(route('restaurant.dashboard.index'));
    }

    /**
     * Cierra la sesión
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('restaurant')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
