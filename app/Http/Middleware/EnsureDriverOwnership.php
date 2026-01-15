<?php

namespace App\Http\Middleware;

use App\Models\Driver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar que un driver pertenece al restaurante del usuario autenticado
 */
class EnsureDriverOwnership
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtener el driver de la ruta
        $driver = $request->route('driver');

        // Si no hay driver en la ruta, continuar
        if (! $driver) {
            return $next($request);
        }

        // Si el driver es un ID, obtener el modelo
        if (! $driver instanceof Driver) {
            $driver = Driver::find($driver);

            if (! $driver) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Driver no encontrado.',
                    ], 404);
                }

                abort(404, 'Driver no encontrado');
            }
        }

        // Admin users (web guard) can access all drivers
        if (auth('web')->check()) {
            return $next($request);
        }

        // Restaurant users can only access drivers from their restaurant
        if (auth('restaurant')->check()) {
            $user = auth('restaurant')->user();

            if ($driver->restaurant_id !== $user->restaurant_id) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'No tienes acceso a este driver.',
                    ], 403);
                }

                abort(403, 'No tienes acceso a este driver');
            }

            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        abort(401);
    }
}
