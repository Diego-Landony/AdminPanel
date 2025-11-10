<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\OperatingSystem;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuthResource;
use App\Services\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function __construct(protected SocialAuthService $socialAuthService) {}

    /**
     * Authenticate customer with Google OAuth token (Mobile flow).
     *
     * Mobile app sends the id_token obtained from Google Sign-In SDK.
     * Backend verifies the token with Google and returns a Sanctum token.
     * NOTE: Does NOT create new accounts. Email must already exist in database.
     *
     * @OA\Post(
     *     path="/api/v1/auth/oauth/google",
     *     tags={"OAuth"},
     *     summary="Login with Google (Mobile)",
     *     description="Authenticates customer using Google OAuth id_token from mobile app. If email exists, links Google account. If email doesn't exist, returns error asking to register first. Does NOT auto-create accounts.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"id_token"},
     *
     *             @OA\Property(property="id_token", type="string", example="eyJhbGciOiJSUzI1NiIsImtpZCI6IjhlY...", description="Google OAuth id_token obtained from Google Sign-In SDK"),
     *             @OA\Property(property="os", type="string", enum={"ios","android","web"}, nullable=true, example="android", description="Operating system")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Inicio de sesión exitoso."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *                 @OA\Property(property="token", type="string", example="1|abc123xyz..."),
     *                 @OA\Property(property="is_new_customer", type="boolean", example=false, description="True if account was just created")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Invalid token or account conflict",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Token de Google inválido o expirado."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function google(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'os' => ['nullable', Rule::enum(OperatingSystem::class)],
        ]);

        $providerData = $this->socialAuthService->verifyGoogleToken($validated['id_token']);

        $result = $this->socialAuthService->findAndLinkCustomer('google', $providerData);

        $tokenName = $validated['os'] ?? 'app';
        $token = $result['customer']->createToken($tokenName)->plainTextToken;

        $authData = AuthResource::make([
            'token' => $token,
            'customer' => $result['customer']->load('customerType'),
        ])->resolve();

        return response()->json([
            'message' => $result['message'],
            'data' => array_merge($authData, [
                'is_new_customer' => $result['is_new'],
            ]),
        ]);
    }

    /**
     * Authenticate customer with Apple OAuth token (Mobile flow).
     *
     * Mobile app sends the id_token obtained from Apple Sign-In SDK.
     * Backend verifies the token with Apple and returns a Sanctum token.
     * NOTE: Does NOT create new accounts. Email must already exist in database.
     *
     * @OA\Post(
     *     path="/api/v1/auth/oauth/apple",
     *     tags={"OAuth"},
     *     summary="Login with Apple (Mobile)",
     *     description="Authenticates customer using Apple Sign-In id_token from mobile app. If email exists, links Apple account. If email doesn't exist, returns error asking to register first. Does NOT auto-create accounts.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"id_token"},
     *
     *             @OA\Property(property="id_token", type="string", example="eyJraWQiOiJlWGF1bm1MIiwiYWxnIj...", description="Apple OAuth id_token obtained from Apple Sign-In SDK"),
     *             @OA\Property(property="os", type="string", enum={"ios","android","web"}, nullable=true, example="ios", description="Operating system")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Cuenta creada exitosamente."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *                 @OA\Property(property="token", type="string", example="1|abc123xyz..."),
     *                 @OA\Property(property="is_new_customer", type="boolean", example=true, description="True if account was just created")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Invalid token or account conflict",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Token de Apple inválido o expirado."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function apple(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'os' => ['nullable', Rule::enum(OperatingSystem::class)],
        ]);

        $providerData = $this->socialAuthService->verifyAppleToken($validated['id_token']);

        $result = $this->socialAuthService->findAndLinkCustomer('apple', $providerData);

        $tokenName = $validated['os'] ?? 'app';
        $token = $result['customer']->createToken($tokenName)->plainTextToken;

        $authData = AuthResource::make([
            'token' => $token,
            'customer' => $result['customer']->load('customerType'),
        ])->resolve();

        return response()->json([
            'message' => $result['message'],
            'data' => array_merge($authData, [
                'is_new_customer' => $result['is_new'],
            ]),
        ]);
    }

    /**
     * Register new customer with Google OAuth token (Mobile flow).
     *
     * Mobile app sends the id_token obtained from Google Sign-In SDK.
     * Backend verifies the token and creates a new account.
     * NOTE: DOES create new accounts. For registration only.
     *
     * @OA\Post(
     *     path="/api/v1/auth/oauth/google/register",
     *     tags={"OAuth"},
     *     summary="Register with Google (Mobile)",
     *     description="Registers new customer using Google OAuth id_token from mobile app. Creates new account if email doesn't exist. If email already exists, returns error.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"id_token"},
     *
     *             @OA\Property(property="id_token", type="string", example="eyJhbGciOiJSUzI1NiIsImtpZCI6IjhlY...", description="Google OAuth id_token obtained from Google Sign-In SDK"),
     *             @OA\Property(property="os", type="string", enum={"ios","android","web"}, nullable=true, example="android", description="Operating system")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Cuenta creada exitosamente."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *                 @OA\Property(property="token", type="string", example="1|abc123xyz..."),
     *                 @OA\Property(property="is_new_customer", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Email already exists or invalid token",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Ya existe una cuenta con este correo electrónico."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function googleRegister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'os' => ['nullable', Rule::enum(OperatingSystem::class)],
        ]);

        $providerData = $this->socialAuthService->verifyGoogleToken($validated['id_token']);

        $result = $this->socialAuthService->createCustomerFromOAuth('google', $providerData);

        $tokenName = $validated['os'] ?? 'app';
        $token = $result['customer']->createToken($tokenName)->plainTextToken;

        $authData = AuthResource::make([
            'token' => $token,
            'customer' => $result['customer']->load('customerType'),
        ])->resolve();

        return response()->json([
            'message' => $result['message'],
            'data' => array_merge($authData, [
                'is_new_customer' => $result['is_new'],
            ]),
        ], $result['is_new'] ? 201 : 200);
    }

    /**
     * Register new customer with Apple OAuth token (Mobile flow).
     *
     * Mobile app sends the id_token obtained from Apple Sign-In SDK.
     * Backend verifies the token and creates a new account.
     * NOTE: DOES create new accounts. For registration only.
     *
     * @OA\Post(
     *     path="/api/v1/auth/oauth/apple/register",
     *     tags={"OAuth"},
     *     summary="Register with Apple (Mobile)",
     *     description="Registers new customer using Apple Sign-In id_token from mobile app. Creates new account if email doesn't exist. If email already exists, returns error.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"id_token"},
     *
     *             @OA\Property(property="id_token", type="string", example="eyJraWQiOiJlWGF1bm1MIiwiYWxnIj...", description="Apple OAuth id_token obtained from Apple Sign-In SDK"),
     *             @OA\Property(property="os", type="string", enum={"ios","android","web"}, nullable=true, example="ios", description="Operating system")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Cuenta creada exitosamente."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *                 @OA\Property(property="token", type="string", example="1|abc123xyz..."),
     *                 @OA\Property(property="is_new_customer", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Email already exists or invalid token",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Ya existe una cuenta con este correo electrónico."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function appleRegister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'os' => ['nullable', Rule::enum(OperatingSystem::class)],
        ]);

        $providerData = $this->socialAuthService->verifyAppleToken($validated['id_token']);

        $result = $this->socialAuthService->createCustomerFromOAuth('apple', $providerData);

        $tokenName = $validated['os'] ?? 'app';
        $token = $result['customer']->createToken($tokenName)->plainTextToken;

        $authData = AuthResource::make([
            'token' => $token,
            'customer' => $result['customer']->load('customerType'),
        ])->resolve();

        return response()->json([
            'message' => $result['message'],
            'data' => array_merge($authData, [
                'is_new_customer' => $result['is_new'],
            ]),
        ], $result['is_new'] ? 201 : 200);
    }

    /**
     * Redirect to Google OAuth provider (Web flow).
     *
     * This method is for web applications using the traditional OAuth redirect flow.
     * Mobile apps should use the google() method with id_token instead.
     *
     * @OA\Get(
     *     path="/api/v1/auth/oauth/google/redirect",
     *     tags={"OAuth"},
     *     summary="Redirect to Google OAuth (Web)",
     *     description="Redirects user to Google OAuth consent screen. For web applications only. Mobile apps should use POST /api/v1/auth/oauth/google with id_token.",
     *
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to Google"
     *     )
     * )
     */
    public function googleRedirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback (Web flow).
     *
     * After user authorizes on Google, they're redirected here with an authorization code.
     * Backend exchanges code for access token, validates user, and returns Sanctum token.
     *
     * @OA\Get(
     *     path="/api/v1/auth/oauth/google/callback",
     *     tags={"OAuth"},
     *     summary="Handle Google OAuth callback (Web)",
     *     description="Handles Google OAuth callback and returns authentication token. For web applications. If email doesn't exist, returns error asking user to register first.",
     *
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         required=true,
     *         description="Authorization code from Google",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         required=false,
     *         description="CSRF state token",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Inicio de sesión exitoso."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *                 @OA\Property(property="token", type="string", example="1|abc123xyz..."),
     *                 @OA\Property(property="is_new_customer", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Email not registered or other validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No existe una cuenta con este correo electrónico. Por favor regístrate primero."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function googleCallback(Request $request): JsonResponse
    {
        $socialiteUser = Socialite::driver('google')->stateless()->user();

        $providerData = (object) [
            'provider_id' => $socialiteUser->getId(),
            'email' => $socialiteUser->getEmail(),
            'name' => $socialiteUser->getName(),
            'avatar' => $socialiteUser->getAvatar(),
        ];

        $result = $this->socialAuthService->findOrCreateCustomer('google', $providerData);

        $tokenName = 'web';
        $token = $result['customer']->createToken($tokenName)->plainTextToken;

        $authData = AuthResource::make([
            'token' => $token,
            'customer' => $result['customer']->load('customerType'),
        ])->resolve();

        return response()->json([
            'message' => $result['message'],
            'data' => array_merge($authData, [
                'is_new_customer' => $result['is_new'],
            ]),
        ]);
    }
}
