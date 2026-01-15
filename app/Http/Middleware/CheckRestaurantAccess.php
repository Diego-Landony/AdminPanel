<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar acceso a restaurantes
 * - Admins (User) pueden acceder a todos los restaurantes
 * - RestaurantUsers solo pueden acceder a su propio restaurante
 */
class CheckRestaurantAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $restaurantId = $request->route('restaurant')?->id
            ?? $request->route('restaurant_id')
            ?? $request->input('restaurant_id');

        // Admin users can access all restaurants
        if (auth('web')->check()) {
            return $next($request);
        }

        // Restaurant users can only access their own restaurant
        if (auth('restaurant')->check()) {
            $user = auth('restaurant')->user();

            if ($restaurantId && $user->restaurant_id !== (int) $restaurantId) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'No tienes acceso a este restaurante.',
                    ], 403);
                }

                abort(403, 'No tienes acceso a este restaurante');
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
