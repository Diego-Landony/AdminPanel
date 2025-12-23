<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerifiedForApi
{
    /**
     * Handle an incoming request.
     *
     * Verifies that the authenticated user has a verified email address.
     * Returns a 403 JSON response if email is not verified.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Debes verificar tu correo electronico para realizar esta accion.',
                'error_code' => 'EMAIL_NOT_VERIFIED',
                'data' => [
                    'email' => $user?->email,
                    'resend_verification_url' => route('api.v1.auth.resend-verification'),
                ],
            ], 403);
        }

        return $next($request);
    }
}
