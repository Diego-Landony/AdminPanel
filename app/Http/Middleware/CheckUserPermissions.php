<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar permisos de usuarios
 * Los usuarios sin roles solo pueden acceder al dashboard
 */
class CheckUserPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission Permiso requerido
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $user = auth()->user();
        
        // Si no hay usuario autenticado, continuar (será manejado por auth middleware)
        if (!$user) {
            return $next($request);
        }

        // Si no se especifica permiso, continuar
        if (!$permission) {
            return $next($request);
        }

        // Si el usuario no tiene roles, solo puede acceder al dashboard
        if ($user->roles()->count() === 0) {
            if ($permission === 'dashboard.view') {
                return $next($request);
            }
            
            // Si es una petición AJAX, devolver JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'No tienes permisos para acceder a esta página. Contacta al administrador para asignar roles.'
                ], 403);
            }
            
            // Redirigir al dashboard si intenta acceder a otra página
            return redirect()->route('dashboard')
                ->with('error', 'No tienes permisos para acceder a esta página. Contacta al administrador para asignar roles.');
        }

        // Verificar si el usuario tiene el permiso requerido
        if (!$user->hasPermission($permission)) {
            // Si es una petición AJAX, devolver JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'No tienes permisos para acceder a esta página.'
                ], 403);
            }
            
            return redirect()->route('dashboard')
                ->with('error', 'No tienes permisos para acceder a esta página.');
        }

        return $next($request);
    }
}
