<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para manejar errores de validación globalmente
 * Solo maneja errores de validación en la vista, NO agrega mensajes flash
 */
class HandleValidationErrors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ValidationException $e) {
            // Para peticiones AJAX/Inertia, mantener el comportamiento normal
            // Los errores se mostrarán en la vista, no como notificaciones
            if ($request->expectsJson() || $request->header('X-Inertia')) {
                throw $e;
            }

            // Para peticiones normales (no Inertia), redirigir con errores
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }
}
