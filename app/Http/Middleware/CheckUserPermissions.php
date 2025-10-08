<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar permisos de usuarios
 * Los usuarios sin roles solo pueden acceder a no-access
 */
class CheckUserPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  Permiso requerido
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = auth()->user();

        // Si no hay usuario autenticado, continuar (será manejado por auth middleware)
        if (! $user) {
            return $next($request);
        }

        // Eager load roles y permisos para evitar N+1 queries
        $user->load('roles.permissions');

        // Si no se especifica permiso, continuar
        if (! $permission) {
            return $next($request);
        }

        // Admin siempre tiene acceso (bypass automático)
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Si el usuario no tiene roles o no tiene permisos, solo puede acceder a no-access
        if ($user->roles->count() === 0 || count($user->getAllPermissions()) === 0) {
            // Si es una petición AJAX, devolver JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'No tienes permisos para acceder a esta página. Contacta al administrador para asignar roles.',
                ], 403);
            }

            // Redirigir a no-access para usuarios sin permisos
            return redirect()->route('no-access')
                ->with('error', 'No tienes permisos para acceder a esta página. Contacta al administrador para asignar roles.');
        }

        // Verificar si el usuario tiene el permiso requerido
        if (! $user->hasPermission($permission)) {
            // Si es una petición AJAX, devolver JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'No tienes permisos para acceder a esta página.',
                ], 403);
            }

            // Redirigir a home si no tiene el permiso específico
            return redirect()->route('home')
                ->with('error', 'No tienes permisos para acceder a esta página.');
        }

        return $next($request);
    }
}
