<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para verificar que la orden pertenece al driver autenticado
 */
class EnsureOrderBelongsToDriver
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $order = $request->route('order');
        $driver = auth('driver')->user();

        if ($order && $driver && $order->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Esta orden no está asignada a ti.',
                'error_code' => 'ORDER_NOT_ASSIGNED',
            ], 403);
        }

        return $next($request);
    }
}
