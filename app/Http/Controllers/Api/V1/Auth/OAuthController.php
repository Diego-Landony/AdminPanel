<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
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
     *         description="Device identifier UUID for tracking. **REQUIRED when platform=mobile**, optional for web. Validated with rule: required_if:platform,mobile",
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
            'redirect_url' => 'nullable|url', // URL del frontend para redirección
        ]);

        // Encode all parameters in OAuth state parameter (OAuth 2.0 spec)
        // Google will return this state in the callback, allowing us to maintain context
        $state = base64_encode(json_encode([
            'platform' => $validated['platform'],
            'action' => $validated['action'],
            'device_id' => $validated['device_id'] ?? null,
            'redirect_url' => $validated['redirect_url'] ?? null, // Frontend redirect URL
            'nonce' => \Illuminate\Support\Str::random(32), // CSRF protection
            'timestamp' => time(), // For state expiration validation
        ]));

        \Log::info('OAuth Redirect Initiated', [
            'platform' => $validated['platform'],
            'action' => $validated['action'],
            'device_id' => $validated['device_id'] ?? 'none',
        ]);

        return Socialite::driver('google')
            ->with([
                'state' => $state,
                'prompt' => 'select_account',
            ])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback (Web and Mobile flow).
     *
     * Receives the OAuth callback from Google with authorization code and state parameter.
     * Decodes the state to retrieve platform, action, and device_id.
     * Exchanges code for user data, creates/authenticates customer, and responds accordingly:
     * - Web: Redirects to /oauth/success with session data
     * - Mobile: Redirects to app via deep link
     *
     * @OA\Get(
     *     path="/api/v1/auth/oauth/google/callback",
     *     tags={"OAuth"},
     *     summary="Handle Google OAuth callback (Web & Mobile)",
     *     description="Handles Google OAuth callback after user authorization. This endpoint is called automatically by Google. It decodes the OAuth 2.0 state parameter to determine platform and action, then authenticates or registers the user. For web: redirects to /oauth/success HTML page that stores token in localStorage. For mobile: redirects to deep link with token.",
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
     *         response=302,
     *         description="Redirect response - behavior depends on platform and success/error status. WEB SUCCESS: redirects to /oauth/success (HTML page that stores token in localStorage and redirects to /home). WEB ERROR: redirects to /login with error message. MOBILE SUCCESS: redirects to subwayapp://oauth/callback?token=xxx&customer_id=xxx. MOBILE ERROR: redirects to subwayapp://oauth/callback?error=xxx&message=xxx",
     *
     *         @OA\Header(
     *             header="Location",
     *             description="Redirect URL varies by platform: Web success -> /oauth/success, Web error -> /login, Mobile success -> subwayapp://oauth/callback?token=..., Mobile error -> subwayapp://oauth/callback?error=...",
     *
     *             @OA\Schema(type="string", example="/oauth/success")
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

            // Validate state timestamp (expire after 30 minutes)
            if (isset($state['timestamp']) && (time() - $state['timestamp']) > 1800) {
                throw new \Exception('State parameter expired');
            }

            $platform = $state['platform'] ?? 'web';
            $action = $state['action'] ?? 'login';
            $deviceId = $state['device_id'] ?? null;
            $redirectUrl = $state['redirect_url'] ?? null;

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
                    'customer_id' => $customer->id,
                    'is_new_customer' => $result['is_new'] ? '1' : '0',
                ]);
            }

            // WEB: Store in session and redirect to success route
            \Log::info('Web OAuth successful, redirecting to app', [
                'customer_id' => $customer->id,
                'is_new' => $result['is_new'],
            ]);

            // Si hay redirect_url personalizada (para desarrollo local), redirigir allá con datos en query string
            if ($redirectUrl) {
                $params = http_build_query([
                    'token' => $token,
                    'customer_id' => $customer->id,
                    'is_new_customer' => $result['is_new'] ? '1' : '0',
                    'message' => __($result['message_key']),
                ]);

                return redirect()->away($redirectUrl.'?'.$params);
            }

            // WEB: Pasar datos en URL en lugar de sesión (igual que mobile)
            // Esto evita problemas de sesión perdida en redirects cross-origin
            return redirect()->route('oauth.success', [
                'token' => $token,
                'customer_id' => $customer->id,
                'is_new' => $result['is_new'] ? '1' : '0',
                'message' => __($result['message_key']),
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

            // WEB: Redirect to oauth/success with error parameters
            return redirect()->route('oauth.success', [
                'error' => 'authentication_failed',
                'message' => 'Error al procesar autenticación. Por favor intenta nuevamente.',
            ]);
        }
    }

    /**
     * Redirect to mobile app via deep link with authentication data.
     */
    protected function redirectToApp(array $data): RedirectResponse
    {
        $scheme = config('app.mobile_scheme', 'subwayapp');
        $queryParams = http_build_query($data);

        return redirect()->away("{$scheme}://oauth/callback?{$queryParams}");
    }

    /**
     * Redirect to Apple OAuth provider (Unified flow for Web and Mobile).
     *
     * @OA\Get(
     *     path="/api/v1/auth/oauth/apple/redirect",
     *     tags={"OAuth"},
     *     summary="Redirect to Apple OAuth (Web & Mobile)",
     *     description="Initiates Apple OAuth flow for any platform. Uses OAuth 2.0 state parameter to maintain context. Works for web apps, mobile apps (React Native with WebBrowser), and any other client type.",
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
     *         description="Device identifier UUID for tracking. **REQUIRED when platform=mobile**, optional for web.",
     *
     *         @OA\Schema(type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *
     *     @OA\Response(
     *         response=302,
     *         description="Redirect to Apple OAuth consent screen"
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
    public function appleRedirect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:login,register',
            'platform' => 'required|in:web,mobile',
            'device_id' => 'required_if:platform,mobile|string|max:255',
            'redirect_url' => 'nullable|url',
        ]);

        $state = base64_encode(json_encode([
            'platform' => $validated['platform'],
            'action' => $validated['action'],
            'device_id' => $validated['device_id'] ?? null,
            'redirect_url' => $validated['redirect_url'] ?? null,
            'nonce' => \Illuminate\Support\Str::random(32),
            'timestamp' => time(),
        ]));

        \Log::info('Apple OAuth Redirect Initiated', [
            'platform' => $validated['platform'],
            'action' => $validated['action'],
            'device_id' => $validated['device_id'] ?? 'none',
        ]);

        return Socialite::driver('apple')
            ->with(['state' => $state])
            ->redirect();
    }

    /**
     * Handle Apple OAuth callback (Web and Mobile flow).
     *
     * @OA\Get(
     *     path="/api/v1/auth/oauth/apple/callback",
     *     tags={"OAuth"},
     *     summary="Handle Apple OAuth callback (Web & Mobile)",
     *     description="Handles Apple OAuth callback after user authorization. Decodes OAuth 2.0 state parameter to determine platform and action. NOTE: Apple only sends name/email on FIRST authentication, subsequent logins only return user ID.",
     *
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         required=true,
     *         description="Authorization code from Apple OAuth",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         required=true,
     *         description="OAuth 2.0 state parameter containing encoded platform, action, and device_id information",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=302,
     *         description="Redirect response based on platform. Web: redirects to /oauth/success. Mobile: redirects to deep link."
     *     )
     * )
     */
    public function appleCallback(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $stateEncoded = $request->query('state');

            if (! $stateEncoded) {
                throw new \Exception('Missing state parameter');
            }

            $stateJson = base64_decode($stateEncoded);
            $state = json_decode($stateJson, true);

            if (! $state || ! isset($state['nonce'])) {
                throw new \Exception('Invalid state parameter');
            }

            // Validate state timestamp (expire after 30 minutes)
            if (isset($state['timestamp']) && (time() - $state['timestamp']) > 1800) {
                throw new \Exception('State parameter expired');
            }

            $platform = $state['platform'] ?? 'web';
            $action = $state['action'] ?? 'login';
            $deviceId = $state['device_id'] ?? null;
            $redirectUrl = $state['redirect_url'] ?? null;

            \Log::info('Apple OAuth Callback', [
                'platform' => $platform,
                'action' => $action,
                'device_id' => $deviceId,
            ]);

            $socialiteUser = Socialite::driver('apple')->stateless()->user();

            $providerData = (object) [
                'provider_id' => $socialiteUser->getId(),
                'email' => $socialiteUser->getEmail(),
                'name' => $socialiteUser->getName() ?? 'Apple User',
                'avatar' => null, // Apple doesn't provide avatar
            ];

            \Log::info('Apple User Data', ['email' => $providerData->email]);

            if ($action === 'register') {
                $result = $this->socialAuthService->createCustomerFromOAuth('apple', $providerData);
            } else {
                $result = $this->socialAuthService->findAndLinkCustomer('apple', $providerData);
            }

            $customer = $result['customer'];
            $customer->enforceTokenLimit(5);

            $tokenName = $this->generateTokenName($deviceId);
            $newAccessToken = $customer->createToken($tokenName);
            $token = $newAccessToken->plainTextToken;

            if ($deviceId) {
                $this->deviceService->syncDeviceWithToken(
                    $customer,
                    $newAccessToken->accessToken,
                    $deviceId
                );
            }

            if ($platform === 'mobile') {
                return $this->redirectToApp([
                    'token' => $token,
                    'customer_id' => $customer->id,
                    'is_new_customer' => $result['is_new'] ? '1' : '0',
                ]);
            }

            if ($redirectUrl) {
                $params = http_build_query([
                    'token' => $token,
                    'customer_id' => $customer->id,
                    'is_new_customer' => $result['is_new'] ? '1' : '0',
                    'message' => __($result['message_key']),
                ]);

                return redirect()->away($redirectUrl.'?'.$params);
            }

            return redirect()->route('oauth.success', [
                'token' => $token,
                'customer_id' => $customer->id,
                'is_new' => $result['is_new'] ? '1' : '0',
                'message' => __($result['message_key']),
            ]);

        } catch (\Exception $e) {
            \Log::error('Apple OAuth Callback Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $platform = 'web';
            try {
                if ($request->query('state')) {
                    $state = json_decode(base64_decode($request->query('state')), true);
                    $platform = $state['platform'] ?? 'web';
                }
            } catch (\Exception $decodeError) {
                //
            }

            if ($platform === 'mobile') {
                return $this->redirectToApp([
                    'error' => 'authentication_failed',
                    'message' => 'Error al procesar autenticación: '.$e->getMessage(),
                ]);
            }

            // WEB: Redirect to oauth/success with error parameters
            return redirect()->route('oauth.success', [
                'error' => 'authentication_failed',
                'message' => 'Error al procesar autenticación con Apple. Por favor intenta nuevamente.',
            ]);
        }
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
