<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrderBelongsToRestaurant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $order = $request->route('order');
        $restaurantUser = auth('restaurant')->user();

        if ($order && $restaurantUser && $order->restaurant_id !== $restaurantUser->restaurant_id) {
            abort(403, 'No tienes acceso a esta orden');
        }

        return $next($request);
    }
}
