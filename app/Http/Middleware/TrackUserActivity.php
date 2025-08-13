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
            
            // Actualizar last_activity_at cada 30 segundos para heartbeat
            if (!$user->last_activity_at || $user->last_activity_at->diffInSeconds(now()) >= 30) {
                $user->updateLastActivity();
                
                // También registrar la actividad de página vista (solo para rutas importantes)
                if (!str_contains($request->path(), 'keep-alive')) {
                    $user->logActivity('page_view', 'Vista de página: ' . $request->path(), [
                        'route' => $request->route()?->getName(),
                        'params' => $request->route()?->parameters(),
                    ]);
                }
            }
        }

        return $response;
    }
}
