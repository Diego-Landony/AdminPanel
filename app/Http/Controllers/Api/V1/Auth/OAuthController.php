<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\AccountDeletedException;
use App\Http\Controllers\Controller;
use App\Services\DeviceService;
use App\Services\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OAuthController extends Controller
{
    public function __construct(
        protected SocialAuthService $socialAuthService,
        protected DeviceService $deviceService
    ) {}

    /**
     * Handle Google Sign-In via Firebase Auth (Native mobile flow).
     *
     * Receives a Firebase ID token from the Flutter app (obtained via native Google Sign-In + Firebase Auth),
     * verifies it server-side using the Firebase Admin SDK, and returns a Sanctum token.
     *
     * This endpoint replaces the browser-based OAuth redirect flow for mobile apps,
     * providing a seamless native experience without opening external browsers.
     *
     * @OA\Post(
     *     path="/api/v1/auth/oauth/google/firebase",
     *     tags={"OAuth"},
     *     summary="Google Sign-In via Firebase (Mobile native)",
     *     description="Verifies a Firebase ID token from native Google Sign-In and authenticates/registers the customer. No browser redirect needed.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"firebase_token", "action", "device_identifier"},
     *
     *             @OA\Property(property="firebase_token", type="string", description="Firebase ID token from FirebaseAuth.signInWithCredential()"),
     *             @OA\Property(property="action", type="string", enum={"login", "register"}, description="Action type"),
     *             @OA\Property(property="device_identifier", type="string", description="Unique device identifier")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="is_new_customer", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error or invalid token"),
     *     @OA\Response(response=409, description="Account already exists (register) or account deleted")
     * )
     */
    public function googleFirebase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firebase_token' => 'required|string',
            'action' => 'required|in:login,register',
            'device_identifier' => 'required|string|max:255',
        ]);

        try {
            // Verify Firebase ID token using Firebase Admin SDK
            $providerData = $this->socialAuthService->verifyFirebaseToken($validated['firebase_token']);

            \Log::info('Firebase Google Sign-In', [
                'action' => $validated['action'],
                'email' => $providerData->email,
                'device_id' => $validated['device_identifier'],
            ]);

            // Process based on action (login vs register)
            if ($validated['action'] === 'register') {
                $result = $this->socialAuthService->createCustomerFromOAuth('google', $providerData);
            } else {
                $result = $this->socialAuthService->findAndLinkCustomer('google', $providerData);
            }

            $customer = $result['customer'];
            $customer->enforceTokenLimit(5);

            // Generate Sanctum token
            $tokenName = $this->generateTokenName($validated['device_identifier']);
            $newAccessToken = $customer->createToken($tokenName);

            // Sync device
            $this->deviceService->syncDeviceWithToken(
                $customer,
                $newAccessToken->accessToken,
                $validated['device_identifier']
            );

            $response = [
                'token' => $newAccessToken->plainTextToken,
                'customer_id' => $customer->id,
                'is_new_customer' => $result['is_new'],
                'message' => __($result['message_key']),
            ];

            // Include reactivation info if applicable
            if ($result['was_reactivated'] ?? false) {
                $response['was_reactivated'] = true;
                $response['points_recovered'] = $result['points_recovered'] ?? 0;
            }

            return response()->json($response);

        } catch (AccountDeletedException $e) {
            return response()->json([
                'error' => 'account_deleted_recoverable',
                'message' => $e->getMessage(),
                'points' => $e->getPoints(),
                'days_left' => $e->getDaysLeft(),
                'email' => $e->getEmail(),
                'oauth_provider' => $e->getOAuthProvider(),
            ], 409);
        }
    }

    /**
     * Handle Apple Sign-In via Firebase Auth (Native mobile flow).
     *
     * Receives a Firebase ID token from the Flutter app (obtained via native Apple Sign-In + Firebase Auth),
     * verifies it server-side using the Firebase Admin SDK, and returns a Sanctum token.
     *
     * @OA\Post(
     *     path="/api/v1/auth/oauth/apple/firebase",
     *     tags={"OAuth"},
     *     summary="Apple Sign-In via Firebase (Mobile native)",
     *     description="Verifies a Firebase ID token from native Apple Sign-In and authenticates/registers the customer. No browser redirect needed.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"firebase_token", "action", "device_identifier"},
     *
     *             @OA\Property(property="firebase_token", type="string", description="Firebase ID token from FirebaseAuth.signInWithCredential()"),
     *             @OA\Property(property="action", type="string", enum={"login", "register"}, description="Action type"),
     *             @OA\Property(property="device_identifier", type="string", description="Unique device identifier"),
     *             @OA\Property(property="first_name", type="string", nullable=true, description="User's first name (Apple only provides this on first authorization)"),
     *             @OA\Property(property="last_name", type="string", nullable=true, description="User's last name (Apple only provides this on first authorization)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Authentication successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="is_new_customer", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error or invalid token"),
     *     @OA\Response(response=409, description="Account already exists (register) or account deleted")
     * )
     */
    public function appleFirebase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'firebase_token' => 'required|string',
            'action' => 'required|in:login,register',
            'device_identifier' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
        ]);

        try {
            // Verify Firebase ID token using Firebase Admin SDK
            $providerData = $this->socialAuthService->verifyFirebaseToken($validated['firebase_token']);

            // Apple only provides name on first authorization, so we accept it from the request
            // and use it if the Firebase token doesn't have it
            if (empty($providerData->name) && ($validated['first_name'] ?? $validated['last_name'] ?? null)) {
                $providerData->name = trim(($validated['first_name'] ?? '').' '.($validated['last_name'] ?? ''));
            }

            \Log::info('Firebase Apple Sign-In', [
                'action' => $validated['action'],
                'email' => $providerData->email,
                'name' => $providerData->name ?? 'not provided',
                'device_id' => $validated['device_identifier'],
            ]);

            // Process based on action (login vs register)
            if ($validated['action'] === 'register') {
                $result = $this->socialAuthService->createCustomerFromOAuth('apple', $providerData);
            } else {
                $result = $this->socialAuthService->findAndLinkCustomer('apple', $providerData);
            }

            $customer = $result['customer'];
            $customer->enforceTokenLimit(5);

            // Generate Sanctum token
            $tokenName = $this->generateTokenName($validated['device_identifier']);
            $newAccessToken = $customer->createToken($tokenName);

            // Sync device
            $this->deviceService->syncDeviceWithToken(
                $customer,
                $newAccessToken->accessToken,
                $validated['device_identifier']
            );

            $response = [
                'token' => $newAccessToken->plainTextToken,
                'customer_id' => $customer->id,
                'is_new_customer' => $result['is_new'],
                'message' => __($result['message_key']),
            ];

            // Include reactivation info if applicable
            if ($result['was_reactivated'] ?? false) {
                $response['was_reactivated'] = true;
                $response['points_recovered'] = $result['points_recovered'] ?? 0;
            }

            return response()->json($response);

        } catch (AccountDeletedException $e) {
            return response()->json([
                'error' => 'account_deleted_recoverable',
                'message' => $e->getMessage(),
                'points' => $e->getPoints(),
                'days_left' => $e->getDaysLeft(),
                'email' => $e->getEmail(),
                'oauth_provider' => $e->getOAuthProvider(),
            ], 409);
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
