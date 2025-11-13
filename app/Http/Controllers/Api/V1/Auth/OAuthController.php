<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuthResource;
use App\Services\DeviceService;
use App\Services\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     *             required={"id_token","device_identifier"},
     *
     *             @OA\Property(property="id_token", type="string", example="eyJhbGciOiJSUzI1NiIsImtpZCI6IjhlY...", description="Google OAuth id_token obtained from Google Sign-In SDK"),
     *             @OA\Property(property="device_identifier", type="string", example="550e8400-e29b-41d4-a716-446655440000", description="REQUIRED: Unique device UUID for tracking")
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
            'device_identifier' => ['required', 'string', 'max:255'],
        ]);

        $providerData = $this->socialAuthService->verifyGoogleToken($validated['id_token']);

        $result = $this->socialAuthService->findAndLinkCustomer('google', $providerData);

        $result['customer']->enforceTokenLimit(5);

        $tokenName = $this->generateTokenName($validated['device_identifier']);
        $newAccessToken = $result['customer']->createToken($tokenName);
        $token = $newAccessToken->plainTextToken;

        // Sync device with token
        $this->deviceService->syncDeviceWithToken(
            $result['customer'],
            $newAccessToken->accessToken,
            $validated['device_identifier']
        );

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
     *             required={"id_token","device_identifier"},
     *
     *             @OA\Property(property="id_token", type="string", example="eyJhbGciOiJSUzI1NiIsImtpZCI6IjhlY...", description="Google OAuth id_token obtained from Google Sign-In SDK"),
     *             @OA\Property(property="device_identifier", type="string", example="550e8400-e29b-41d4-a716-446655440000", description="REQUIRED: Unique device UUID for tracking")
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
            'device_identifier' => ['required', 'string', 'max:255'],
        ]);

        $providerData = $this->socialAuthService->verifyGoogleToken($validated['id_token']);

        $result = $this->socialAuthService->createCustomerFromOAuth('google', $providerData);

        $result['customer']->enforceTokenLimit(5);

        $tokenName = $this->generateTokenName($validated['device_identifier']);
        $newAccessToken = $result['customer']->createToken($tokenName);
        $token = $newAccessToken->plainTextToken;

        // Sync device with token
        $this->deviceService->syncDeviceWithToken(
            $result['customer'],
            $newAccessToken->accessToken,
            $validated['device_identifier']
        );

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
     * Redirect to Google OAuth provider (Unified flow for Web and Mobile).
     *
     * Handles OAuth redirect for all platforms using OAuth 2.0 state parameter.
     * Supports both login and register actions.
     *
     * @OA\Get(
     *     path="/api/v1/auth/oauth/google/redirect",
     *     tags={"OAuth"},
     *     summary="Redirect to Google OAuth (Web & Mobile)",
     *     description="Initiates Google OAuth flow for any platform. Uses OAuth 2.0 state parameter to maintain context across the redirect flow. Works for web apps, mobile apps (React Native with WebBrowser), and any other client type. After Google authorization, redirects to callback which returns JSON for web or deep link for mobile.",
     *
     *     @OA\Parameter(
     *         name="action",
     *         in="query",
     *         required=true,
     *         description="Action type: 'login' for existing users or 'register' for new users",
     *
     *         @OA\Schema(type="string", enum={"login", "register"}, example="login")
     *     ),
     *
     *     @OA\Parameter(
     *         name="platform",
     *         in="query",
     *         required=true,
     *         description="Client platform: 'web' for web applications (returns JSON) or 'mobile' for mobile apps (redirects via deep link)",
     *
     *         @OA\Schema(type="string", enum={"web", "mobile"}, example="web")
     *     ),
     *
     *     @OA\Parameter(
     *         name="device_id",
     *         in="query",
     *         required=false,
     *         description="Device identifier for tracking and multi-device management. Required for mobile apps.",
     *
     *         @OA\Schema(type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to Google OAuth consent screen"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The action field is required."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function googleRedirect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:login,register',
            'platform' => 'required|in:web,mobile',
            'device_id' => 'required_if:platform,mobile|string|max:255',
        ]);

        // Encode all parameters in OAuth state parameter (OAuth 2.0 spec)
        // Google will return this state in the callback, allowing us to maintain context
        $state = base64_encode(json_encode([
            'platform' => $validated['platform'],
            'action' => $validated['action'],
            'device_id' => $validated['device_id'] ?? null,
            'nonce' => \Illuminate\Support\Str::random(32), // CSRF protection
            'timestamp' => time(), // For state expiration validation
        ]));

        \Log::info('OAuth Redirect Initiated', [
            'platform' => $validated['platform'],
            'action' => $validated['action'],
            'device_id' => $validated['device_id'] ?? 'none',
        ]);

        return Socialite::driver('google')
            ->with(['state' => $state])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback (Web and Mobile flow).
     *
     * Receives the OAuth callback from Google with authorization code and state parameter.
     * Decodes the state to retrieve platform, action, and device_id.
     * Exchanges code for user data, creates/authenticates customer, and responds accordingly:
     * - Web: Returns JSON with token
     * - Mobile: Redirects to app via deep link
     *
     * @OA\Get(
     *     path="/api/v1/auth/oauth/google/callback",
     *     tags={"OAuth"},
     *     summary="Handle Google OAuth callback (Web & Mobile)",
     *     description="Handles Google OAuth callback after user authorization. This endpoint is called automatically by Google. It decodes the OAuth 2.0 state parameter to determine platform and action, then authenticates or registers the user. Returns JSON for web clients or redirects to deep link for mobile apps.",
     *
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         required=true,
     *         description="Authorization code from Google OAuth",
     *
     *         @OA\Schema(type="string", example="4/0AY0e-g7...")
     *     ),
     *
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         required=true,
     *         description="OAuth 2.0 state parameter containing encoded platform, action, and device_id information",
     *
     *         @OA\Schema(type="string", example="eyJwbGF0Zm9ybSI6IndlYiIsImFjdGlvbiI6ImxvZ2luIn0=")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful (web platform)",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Inicio de sesión exitoso."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", example="12|SUis1Iwo3q8vZ0bqDH8qRofURWHygpX75ek4rH3p018743cd"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=525600),
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *                 @OA\Property(property="is_new_customer", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to mobile app with token (mobile platform)",
     *
     *         @OA\Header(header="Location", description="Deep link to mobile app", @OA\Schema(type="string", example="subwayapp://callback?token=12|SUis...&customer={...}&is_new_customer=0"))
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Email not registered (login action) or email already exists (register action)",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No existe una cuenta con este correo electrónico. Por favor regístrate primero."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error or invalid state parameter",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Error al procesar autenticación"),
     *             @OA\Property(property="error", type="string", example="Invalid state parameter")
     *         )
     *     )
     * )
     */
    public function googleCallback(Request $request): JsonResponse|RedirectResponse
    {
        try {
            // Decode OAuth state parameter (contains platform, action, device_id)
            $stateEncoded = $request->query('state');

            if (! $stateEncoded) {
                throw new \Exception('Missing state parameter');
            }

            $stateJson = base64_decode($stateEncoded);
            $state = json_decode($stateJson, true);

            if (! $state || ! isset($state['nonce'])) {
                throw new \Exception('Invalid state parameter');
            }

            // Validate state timestamp (expire after 10 minutes)
            if (isset($state['timestamp']) && (time() - $state['timestamp']) > 600) {
                throw new \Exception('State parameter expired');
            }

            $platform = $state['platform'] ?? 'web';
            $action = $state['action'] ?? 'login';
            $deviceId = $state['device_id'] ?? null;

            \Log::info('OAuth Callback', [
                'platform' => $platform,
                'action' => $action,
                'device_id' => $deviceId,
                'email' => '(fetching...)',
            ]);

            // Get user data from Google
            $socialiteUser = Socialite::driver('google')->stateless()->user();

            $providerData = (object) [
                'provider_id' => $socialiteUser->getId(),
                'email' => $socialiteUser->getEmail(),
                'name' => $socialiteUser->getName(),
                'avatar' => $socialiteUser->getAvatar(),
            ];

            \Log::info('Google User Data', ['email' => $providerData->email]);

            // Process based on action (login vs register)
            if ($action === 'register') {
                $result = $this->socialAuthService->createCustomerFromOAuth('google', $providerData);
            } else {
                $result = $this->socialAuthService->findAndLinkCustomer('google', $providerData);
            }

            $customer = $result['customer'];
            $customer->enforceTokenLimit(5);

            // Generate token
            $tokenName = $this->generateTokenName($deviceId);
            $newAccessToken = $customer->createToken($tokenName);
            $token = $newAccessToken->plainTextToken;

            // Sync device if device_id provided
            if ($deviceId) {
                $this->deviceService->syncDeviceWithToken(
                    $customer,
                    $newAccessToken->accessToken,
                    $deviceId
                );

                \Log::info('Device synced', [
                    'customer_id' => $customer->id,
                    'device_id' => $deviceId,
                ]);
            }

            // MOBILE: Redirect to app with deep link
            if ($platform === 'mobile') {
                \Log::info('Redirecting to mobile app', [
                    'customer_id' => $customer->id,
                    'is_new' => $result['is_new'],
                ]);

                return $this->redirectToApp([
                    'token' => $token,
                    'customer' => json_encode([
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'avatar' => $customer->avatar,
                        'subway_card' => $customer->subway_card,
                        'birth_date' => $customer->birth_date,
                        'gender' => $customer->gender,
                        'points' => $customer->points,
                        'customer_type' => $customer->customerType,
                    ]),
                    'is_new_customer' => $result['is_new'] ? '1' : '0',
                ]);
            }

            // WEB: Return JSON
            $authData = AuthResource::make([
                'token' => $token,
                'customer' => $customer->load('customerType'),
            ])->resolve();

            return response()->json([
                'message' => $result['message'],
                'data' => array_merge($authData, [
                    'is_new_customer' => $result['is_new'],
                ]),
            ]);

        } catch (\Exception $e) {
            \Log::error('OAuth Callback Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Try to get platform from state (if available)
            $platform = 'web';
            try {
                if ($request->query('state')) {
                    $state = json_decode(base64_decode($request->query('state')), true);
                    $platform = $state['platform'] ?? 'web';
                }
            } catch (\Exception $decodeError) {
                // Ignore decode errors, use default platform
            }

            // MOBILE: Redirect with error
            if ($platform === 'mobile') {
                return $this->redirectToApp([
                    'error' => 'authentication_failed',
                    'message' => 'Error al procesar autenticación: '.$e->getMessage(),
                ]);
            }

            // WEB: Return JSON error
            return response()->json([
                'message' => 'Error al procesar autenticación',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error',
            ], 500);
        }
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
    protected function generateTokenName(?string $deviceIdentifier): string
    {
        if ($deviceIdentifier) {
            return substr($deviceIdentifier, 0, 8);
        }

        return 'device-'.uniqid();
    }
}
