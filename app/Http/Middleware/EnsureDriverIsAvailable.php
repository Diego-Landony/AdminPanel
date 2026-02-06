<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar que el driver está disponible
 */
class EnsureDriverIsAvailable
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

        if (! $driver->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'Debes estar disponible para aceptar órdenes.',
                'error_code' => 'DRIVER_UNAVAILABLE',
            ], 409);
        }

        return $next($request);
    }
}
