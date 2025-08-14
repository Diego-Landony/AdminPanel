<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo rastrear actividad si el usuario está autenticado
        if (Auth::check()) {
            $user = Auth::user();
            
            // Actualizar last_activity_at cada 30 segundos para mantener sesión activa
            if (!$user->last_activity_at || $user->last_activity_at->diffInSeconds(now()) >= 30) {
                $user->updateLastActivity();
            }
        }

        return $response;
    }
}
