<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar que el driver autenticado esté activo
 */
class EnsureDriverIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $driver = auth('driver')->user();

        if (! $driver) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'error_code' => 'UNAUTHENTICATED',
            ], 401);
        }

        if (! $driver->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta de motorista ha sido desactivada. Contacta con tu restaurante.',
                'error_code' => 'DRIVER_INACTIVE',
            ], 403);
        }

        return $next($request);
    }
}
