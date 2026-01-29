<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // ✅ Registrar inicio de sesión en actividad
        $user = auth()->user();
        if ($user) {
            $user->logActivity('login', 'Usuario inició sesión');
        }

        // ✅ Redirección inteligente basada en permisos
        $user = auth()->user();
        if ($user) {
            // Eager load roles y permisos para evitar N+1 queries
            $user->load('roles.permissions');

            // Admin siempre va a home (bypass automático)
            if ($user->isAdmin()) {
                return redirect()->intended(route('home', absolute: false));
            }

            // Si no tiene roles, ir directamente a no-access
            if ($user->roles->count() === 0) {
                return redirect()->route('no-access');
            }

            // Verificar si tiene permisos (roles sin permisos = no-access)
            if (count($user->getAllPermissions()) === 0) {
                return redirect()->route('no-access');
            }

            // Verificar si tiene acceso al home (página principal)
            if ($user->hasPermission('home.view')) {
                return redirect()->intended(route('home', absolute: false));
            }

            // Si no tiene acceso al home, buscar la primera página a la que tenga acceso
            $firstAccessiblePage = $user->getFirstAccessiblePage();
            if ($firstAccessiblePage) {
                return redirect()->intended($firstAccessiblePage);
            }

            // Fallback: ir a no-access si no tiene permisos
            return redirect()->route('no-access');
        }

        return redirect()->route('no-access');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
