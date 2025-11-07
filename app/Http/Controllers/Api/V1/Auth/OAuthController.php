<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuthResource;
use App\Services\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OAuthController extends Controller
{
    public function __construct(protected SocialAuthService $socialAuthService) {}

    /**
     * Authenticate or register customer with Google OAuth token.
     *
     * Mobile app sends the id_token obtained from Google Sign-In SDK.
     * Backend verifies the token with Google and returns a Sanctum token.
     *
     * @OA\Post(
     *     path="/api/v1/auth/oauth/google",
     *     tags={"OAuth"},
     *     summary="Login/Register with Google",
     *     description="Authenticates or registers customer using Google OAuth id_token. Mobile app obtains id_token from Google Sign-In SDK, backend verifies with Google and returns Sanctum token. Creates new account if email doesn't exist.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"id_token"},
     *
     *             @OA\Property(property="id_token", type="string", example="eyJhbGciOiJSUzI1NiIsImtpZCI6IjhlY...", description="Google OAuth id_token obtained from Google Sign-In SDK"),
     *             @OA\Property(property="device_name", type="string", nullable=true, example="Pixel 7 Pro", description="Device name for token")
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
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $providerData = $this->socialAuthService->verifyGoogleToken($validated['id_token']);

        $result = $this->socialAuthService->findOrCreateCustomer('google', $providerData);

        $deviceName = $validated['device_name'] ?? 'google-app';
        $token = $result['customer']->createToken($deviceName)->plainTextToken;

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
     * Authenticate or register customer with Apple OAuth token.
     *
     * Mobile app sends the id_token obtained from Apple Sign-In SDK.
     * Backend verifies the token with Apple and returns a Sanctum token.
     *
     * @OA\Post(
     *     path="/api/v1/auth/oauth/apple",
     *     tags={"OAuth"},
     *     summary="Login/Register with Apple",
     *     description="Authenticates or registers customer using Apple Sign-In id_token. Mobile app obtains id_token from Apple Sign-In SDK, backend verifies with Apple and returns Sanctum token. Creates new account if email doesn't exist.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"id_token"},
     *
     *             @OA\Property(property="id_token", type="string", example="eyJraWQiOiJlWGF1bm1MIiwiYWxnIj...", description="Apple OAuth id_token obtained from Apple Sign-In SDK"),
     *             @OA\Property(property="device_name", type="string", nullable=true, example="iPhone 14 Pro", description="Device name for token")
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
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $providerData = $this->socialAuthService->verifyAppleToken($validated['id_token']);

        $result = $this->socialAuthService->findOrCreateCustomer('apple', $providerData);

        $deviceName = $validated['device_name'] ?? 'apple-app';
        $token = $result['customer']->createToken($deviceName)->plainTextToken;

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
