<?php

use App\Http\Middleware\CheckRestaurantAccess;
use App\Http\Middleware\CheckUserPermissions;
use App\Http\Middleware\EnsureDriverOwnership;
use App\Http\Middleware\EnsureOrderBelongsToRestaurant;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleValidationErrors;
use App\Http\Middleware\TrackUserActivity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleValidationErrors::class,
            HandleInertiaRequests::class,
            TrackUserActivity::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(append: [
            ForceJsonResponse::class,
        ]);

        // Registrar middleware personalizados
        $middleware->alias([
            'permission' => CheckUserPermissions::class,
            'verified.api' => \App\Http\Middleware\EnsureEmailIsVerifiedForApi::class,
            'restaurant.access' => CheckRestaurantAccess::class,
            'driver.ownership' => EnsureDriverOwnership::class,
            'restaurant.order' => EnsureOrderBelongsToRestaurant::class,
        ]);

        // Configurar redirección para usuarios ya autenticados (middleware guest)
        RedirectIfAuthenticated::redirectUsing(function ($request) {
            // Si la petición es para rutas de restaurante, redirigir al dashboard de restaurante
            if ($request->is('restaurant/*') || $request->is('restaurant')) {
                return route('restaurant.dashboard');
            }

            // Para rutas de admin, redirigir al home
            return route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle AuthenticationException (401)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => __('auth.unauthenticated'),
                ], 401);
            }
        });

        // Handle AuthorizationException (403)
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => __('auth.unauthorized'),
                ], 403);
            }
        });

        // Handle ModelNotFoundException (404)
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Recurso no encontrado.',
                ], 404);
            }
        });

        // Handle NotFoundHttpException (404)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Endpoint no encontrado.',
                ], 404);
            }
        });

        // Handle ThrottleRequestsException (429)
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;

                return response()->json([
                    'message' => 'Demasiadas peticiones. Por favor, intenta más tarde.',
                    'retry_after' => (int) $retryAfter,
                ], 429);
            }
        });

        // Handle QueryException (500)
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*')) {
                $message = config('app.debug')
                    ? $e->getMessage()
                    : 'Error procesando la solicitud.';

                return response()->json([
                    'message' => $message,
                ], 500);
            }
        });

        // Handle ValidationException (422)
        // Solo manejamos explícitamente para API, para web/Inertia dejamos que Laravel maneje normalmente
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }

            // Para peticiones web/Inertia, retornar null permite que Laravel
            // maneje la excepción con su comportamiento por defecto (redirect back with errors)
            return null;
        });

        // Handle InvalidSignatureException (403) - for expired/invalid signed URLs
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            // Check if this is an email verification request
            if ($request->is('api/v1/auth/email/verify/*')) {
                return app(\App\Http\Controllers\Api\V1\Auth\AuthController::class)
                    ->handleExpiredVerificationLink();
            }

            if ($request->is('api/*')) {
                return response()->json([
                    'message' => __('auth.invalid_or_expired_link'),
                ], 403);
            }
        });
    })->create();
