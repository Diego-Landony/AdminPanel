<?php

use App\Http\Middleware\CheckUserPermissions;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleValidationErrors;
use App\Http\Middleware\TrackUserActivity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
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

        // Registrar middleware de permisos
        $middleware->alias([
            'permission' => CheckUserPermissions::class,
        ]);
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
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    })->create();
