<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\AuthResource;
use App\Models\Customer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new customer account.
     *
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     tags={"Authentication"},
     *     summary="Register a new customer",
     *     description="Creates a new customer account with email and password. Sends email verification notification.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *
     *             @OA\Property(property="name", type="string", example="Juan Pérez", description="Customer full name"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com", description="Valid email address"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!", description="Min 8 characters, 1 uppercase, 1 number, 1 special char"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="SecurePass123!", description="Must match password"),
     *             @OA\Property(property="phone", type="string", nullable=true, example="+50212345678", description="Optional phone number"),
     *             @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="1990-05-15", description="Optional birth date"),
     *             @OA\Property(property="gender", type="string", enum={"male","female","other"}, nullable=true, example="male", description="Optional gender"),
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
     *             @OA\Property(property="message", type="string", example="Registro exitoso. Por favor verifica tu email."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *                 @OA\Property(property="token", type="string", example="1|abc123xyz...")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The email has already been taken."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'oauth_provider' => 'local',
        ]);

        event(new Registered($customer));

        $tokenName = isset($validated['os']) ? $validated['os'] : 'app';
        $token = $customer->createToken($tokenName)->plainTextToken;

        return response()->json([
            'message' => 'Registro exitoso. Por favor verifica tu email.',
            'data' => AuthResource::make([
                'token' => $token,
                'customer' => $customer,
            ]),
        ], 201);
    }

    /**
     * Authenticate customer and generate token.
     *
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Authentication"},
     *     summary="Login with email and password",
     *     description="Authenticates customer and returns Sanctum token. Rate limited to 5 attempts per minute.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!"),
     *             @OA\Property(property="os", type="string", enum={"ios","android","web"}, nullable=true, example="ios", description="Operating system")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Inicio de sesión exitoso."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *                 @OA\Property(property="token", type="string", example="1|abc123xyz...")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Invalid credentials or OAuth account"),
     *     @OA\Response(response=429, description="Too many login attempts")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $this->ensureIsNotRateLimited($request);

        $customer = Customer::where('email', $request->email)->first();

        if (! $customer || ! Hash::check($request->password, $customer->password)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if ($customer->oauth_provider !== 'local') {
            throw ValidationException::withMessages([
                'email' => ['Esta cuenta usa autenticación con '.$customer->oauth_provider.'. Por favor inicia sesión con '.$customer->oauth_provider.'.'],
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        // Update last login timestamp
        $customer->update([
            'last_login_at' => now(),
            'last_activity_at' => now(),
        ]);

        $tokenName = $request->os ?? 'app';
        $token = $customer->createToken($tokenName)->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'data' => AuthResource::make([
                'token' => $token,
                'customer' => $customer->load('customerType'),
            ]),
        ]);
    }

    /**
     * Logout customer (revoke current token).
     *
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout from current device",
     *     description="Revokes the current access token. Other devices remain logged in.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Sesión cerrada exitosamente.")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.',
        ]);
    }

    /**
     * Logout from all devices (revoke all tokens).
     *
     * @OA\Post(
     *     path="/api/v1/auth/logout-all",
     *     tags={"Authentication"},
     *     summary="Logout from all devices",
     *     description="Revokes all access tokens. Customer will be logged out from all devices.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Se cerraron todas las sesiones exitosamente.")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Se cerraron todas las sesiones exitosamente.',
        ]);
    }

    /**
     * Refresh token (generate new token and revoke old one).
     *
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     tags={"Authentication"},
     *     summary="Refresh access token",
     *     description="Generates new access token and revokes the old one. Token rotation for security.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Token renovado exitosamente."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="2|xyz456abc...")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        $customer = $request->user();
        $oldToken = $request->user()->currentAccessToken();
        $tokenName = $oldToken->name;

        // Revoke old token
        $oldToken->delete();

        // Create new token
        $token = $customer->createToken($tokenName)->plainTextToken;

        return response()->json([
            'message' => 'Token renovado exitosamente.',
            'data' => AuthResource::make([
                'token' => $token,
                'customer' => $customer,
            ]),
        ]);
    }

    /**
     * Request password reset link.
     *
     * @OA\Post(
     *     path="/api/v1/auth/forgot-password",
     *     tags={"Authentication"},
     *     summary="Request password reset",
     *     description="Sends password reset link to customer's email. Only for local accounts (not OAuth).",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Reset link sent",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Enlace de restablecimiento de contraseña enviado.")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('customers')->sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Enlace de restablecimiento de contraseña enviado.',
        ]);
    }

    /**
     * Reset password with token.
     *
     * @OA\Post(
     *     path="/api/v1/auth/reset-password",
     *     tags={"Authentication"},
     *     summary="Reset password",
     *     description="Resets password using token from email. Revokes all existing tokens for security.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","password","password_confirmation","token"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="NewSecurePass123!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NewSecurePass123!"),
     *             @OA\Property(property="token", type="string", example="abc123resettoken456")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Contraseña restablecida exitosamente.")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Invalid token or validation error"),
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password) {
                $customer->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // Revoke all existing tokens for security
                $customer->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Contraseña restablecida exitosamente.',
        ]);
    }

    /**
     * Verify email address.
     *
     * @OA\Post(
     *     path="/api/v1/auth/email/verify/{id}/{hash}",
     *     tags={"Authentication"},
     *     summary="Verify email address",
     *     description="Verifies customer email using signed URL from verification email.",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="hash",
     *         in="path",
     *         required=true,
     *         description="Email hash for verification",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Email verified successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Email verificado exitosamente.")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Invalid verification link"),
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $customer = Customer::findOrFail($request->route('id'));

        if (! hash_equals(sha1($customer->email), (string) $request->route('hash'))) {
            throw ValidationException::withMessages([
                'email' => ['El enlace de verificación no es válido.'],
            ]);
        }

        if ($customer->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'El email ya ha sido verificado.',
            ]);
        }

        if ($customer->markEmailAsVerified()) {
            event(new Verified($customer));
        }

        return response()->json([
            'message' => 'Email verificado exitosamente.',
        ]);
    }

    /**
     * Resend email verification notification.
     *
     * @OA\Post(
     *     path="/api/v1/auth/email/resend",
     *     tags={"Authentication"},
     *     summary="Resend verification email",
     *     description="Sends a new verification email to the customer.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Verification email sent",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Enlace de verificación reenviado.")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Email not found or already verified"),
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'email' => ['No se encontró una cuenta con este email.'],
            ]);
        }

        if ($customer->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'El email ya ha sido verificado.',
            ]);
        }

        $customer->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Enlace de verificación reenviado.',
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => [
                __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ],
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip());
    }
}
