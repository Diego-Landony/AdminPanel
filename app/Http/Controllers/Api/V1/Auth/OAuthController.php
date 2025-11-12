<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\OperatingSystem;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuthResource;
use App\Services\DeviceService;
use App\Services\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function __construct(
        protected SocialAuthService $socialAuthService,
        protected DeviceService $deviceService
    ) {}

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
     *             @OA\Property(property="os", type="string", enum={"ios","android","web"}, nullable=true, example="android", description="Operating system"),
     *             @OA\Property(property="device_identifier", type="string", nullable=true, example="550e8400-e29b-41d4-a716-446655440000", description="RECOMMENDED: Unique device UUID for tracking"),
     *             @OA\Property(property="device_fingerprint", type="string", nullable=true, example="a1b2c3d4e5f6...", description="Optional: SHA256 hash of device characteristics")
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
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'device_fingerprint' => ['nullable', 'string', 'max:255'],
        ]);

        $providerData = $this->socialAuthService->verifyGoogleToken($validated['id_token']);

        $result = $this->socialAuthService->findAndLinkCustomer('google', $providerData);

        $result['customer']->enforceTokenLimit(5);

        $tokenName = $this->generateTokenName(
            $validated['os'] ?? 'app',
            $validated['device_identifier'] ?? null
        );
        $newAccessToken = $result['customer']->createToken($tokenName);
        $token = $newAccessToken->plainTextToken;

        // Auto-create or update device if device_identifier provided
        if (isset($validated['device_identifier'])) {
            $this->deviceService->syncDeviceWithToken(
                $result['customer'],
                $newAccessToken->accessToken,
                $validated['device_identifier'],
                $validated['os'] ?? 'app',
                $validated['device_fingerprint'] ?? null
            );
        }

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
     *             @OA\Property(property="os", type="string", enum={"ios","android","web"}, nullable=true, example="android", description="Operating system"),
     *             @OA\Property(property="device_identifier", type="string", nullable=true, example="550e8400-e29b-41d4-a716-446655440000", description="RECOMMENDED: Unique device UUID for tracking"),
     *             @OA\Property(property="device_fingerprint", type="string", nullable=true, example="a1b2c3d4e5f6...", description="Optional: SHA256 hash of device characteristics")
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
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'device_fingerprint' => ['nullable', 'string', 'max:255'],
        ]);

        $providerData = $this->socialAuthService->verifyGoogleToken($validated['id_token']);

        $result = $this->socialAuthService->createCustomerFromOAuth('google', $providerData);

        $result['customer']->enforceTokenLimit(5);

        $tokenName = $this->generateTokenName(
            $validated['os'] ?? 'app',
            $validated['device_identifier'] ?? null
        );
        $newAccessToken = $result['customer']->createToken($tokenName);
        $token = $newAccessToken->plainTextToken;

        // Auto-create or update device if device_identifier provided
        if (isset($validated['device_identifier'])) {
            $this->deviceService->syncDeviceWithToken(
                $result['customer'],
                $newAccessToken->accessToken,
                $validated['device_identifier'],
                $validated['os'] ?? 'app',
                $validated['device_fingerprint'] ?? null
            );
        }

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
     * Redirect to Google OAuth for mobile app.
     *
     * Stores session data to track mobile platform and action.
     * After OAuth, the user will be redirected back to the mobile app via deep link.
     *
     * @OA\Get(
     *     path="/api/v1/auth/oauth/google/mobile",
     *     tags={"OAuth"},
     *     summary="Redirect to Google OAuth (Mobile)",
     *     description="Redirects user to Google OAuth consent screen for mobile apps. Stores session data to redirect back to mobile app after authentication.",
     *
     *     @OA\Parameter(
     *         name="action",
     *         in="query",
     *         required=true,
     *         description="Action type: login or register",
     *
     *         @OA\Schema(type="string", enum={"login", "register"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="device_id",
     *         in="query",
     *         required=false,
     *         description="Device identifier",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="os",
     *         in="query",
     *         required=false,
     *         description="Operating system",
     *
     *         @OA\Schema(type="string", enum={"ios", "android"})
     *     ),
     *
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to Google"
     *     )
     * )
     */
    public function redirectToMobile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:login,register',
            'device_id' => 'nullable|string|max:255',
            'os' => 'nullable|in:ios,android',
        ]);

        session([
            'oauth_platform' => 'mobile',
            'oauth_action' => $validated['action'],
            'oauth_device_id' => $validated['device_id'] ?? null,
            'oauth_os' => $validated['os'] ?? 'app',
        ]);

        return Socialite::driver('google')->redirect();
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
     * Handle Google OAuth callback (Web and Mobile flow).
     *
     * After user authorizes on Google, they're redirected here with an authorization code.
     * Backend exchanges code for access token, validates user, and returns Sanctum token.
     * For mobile, redirects to app via deep link. For web, returns JSON.
     *
     * @OA\Get(
     *     path="/api/v1/auth/oauth/google/callback",
     *     tags={"OAuth"},
     *     summary="Handle Google OAuth callback (Web & Mobile)",
     *     description="Handles Google OAuth callback and returns authentication token. For web applications, returns JSON. For mobile, redirects to app via deep link. If email doesn't exist (login action), returns error asking user to register first.",
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
     *         response=302,
     *         description="Redirect to mobile app with token"
     *     ),
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
    public function googleCallback(Request $request): JsonResponse|RedirectResponse
    {
        $platform = session('oauth_platform', 'web');
        $action = session('oauth_action', 'login');
        $deviceId = session('oauth_device_id');
        $os = session('oauth_os', 'app');

        $socialiteUser = Socialite::driver('google')->stateless()->user();

        $providerData = (object) [
            'provider_id' => $socialiteUser->getId(),
            'email' => $socialiteUser->getEmail(),
            'name' => $socialiteUser->getName(),
            'avatar' => $socialiteUser->getAvatar(),
        ];

        // Handle based on action (login vs register)
        if ($action === 'register') {
            $result = $this->socialAuthService->createCustomerFromOAuth('google', $providerData);
        } else {
            $result = $this->socialAuthService->findAndLinkCustomer('google', $providerData);
        }

        $result['customer']->enforceTokenLimit(5);

        $tokenName = $platform === 'mobile'
            ? $this->generateTokenName($os, $deviceId)
            : 'web';

        $newAccessToken = $result['customer']->createToken($tokenName);
        $token = $newAccessToken->plainTextToken;

        // Auto-create or update device if mobile and device_id provided
        if ($platform === 'mobile' && $deviceId) {
            $this->deviceService->syncDeviceWithToken(
                $result['customer'],
                $newAccessToken->accessToken,
                $deviceId,
                $os,
                null
            );
        }

        // If mobile platform, redirect to app
        if ($platform === 'mobile') {
            session()->forget(['oauth_platform', 'oauth_action', 'oauth_device_id', 'oauth_os']);

            return $this->redirectToApp([
                'token' => $token,
                'success' => true,
                'message' => $result['message'],
                'is_new_customer' => $result['is_new'],
            ]);
        }

        // Web platform - return JSON
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
     * Redirect to mobile app via deep link with authentication data.
     */
    protected function redirectToApp(array $data): RedirectResponse
    {
        $scheme = config('app.mobile_scheme', 'subwayapp');
        $queryParams = http_build_query($data);

        return redirect()->away("{$scheme}://callback?{$queryParams}");
    }

    /**
     * Generate token name with device identifier if available
     */
    protected function generateTokenName(string $os, ?string $deviceIdentifier): string
    {
        if ($deviceIdentifier) {
            return $os.'-'.substr($deviceIdentifier, 0, 8);
        }

        return $os;
    }
}
