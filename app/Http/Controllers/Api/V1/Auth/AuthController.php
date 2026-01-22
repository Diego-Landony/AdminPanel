<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\ReactivateAccountRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\AuthResource;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Services\DeviceService;
use App\Services\SocialAuthService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected DeviceService $deviceService,
        protected SocialAuthService $socialAuthService
    ) {}

    /**
     * Register a new customer account.
     *
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     tags={"Authentication"},
     *     summary="Registrar nuevo cliente",
     *     description="Crea una nueva cuenta de cliente con email y contraseña.
     *
     * **Nota:** Si el email ya está registrado (activo), retorna error 422.
     * Si deseas registrar un email que tenía cuenta eliminada, usa POST /api/v1/auth/reactivate o espera 30 días.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"first_name","last_name","email","password","password_confirmation","phone","birth_date","gender","device_identifier","terms_accepted"},
     *
     *             @OA\Property(property="first_name", type="string", example="Juan", description="Nombre del cliente"),
     *             @OA\Property(property="last_name", type="string", example="Pérez", description="Apellido del cliente"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com", description="Correo electrónico válido"),
     *             @OA\Property(property="password", type="string", format="password", example="Pass123!", description="Mínimo 8 caracteres, 1 letra, 1 número, 1 símbolo"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Pass123!", description="Debe coincidir con password"),
     *             @OA\Property(property="phone", type="string", example="+50212345678", description="Número de teléfono"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-05-15", description="Fecha de nacimiento"),
     *             @OA\Property(property="gender", type="string", enum={"male","female","other"}, example="male", description="Género"),
     *             @OA\Property(property="device_identifier", type="string", example="550e8400-e29b-41d4-a716-446655440000", description="UUID único del dispositivo"),
     *             @OA\Property(property="terms_accepted", type="boolean", example=true, description="Aceptacion de terminos y condiciones (requerido)")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Registro exitoso",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Registro exitoso. Verifica tu email."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *                 @OA\Property(property="token", type="string", example="1|abc123xyz...")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Este correo ya está registrado."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="Este correo ya está registrado.")),
     *                 @OA\Property(property="first_name", type="array", @OA\Items(type="string", example="El nombre es requerido.")),
     *                 @OA\Property(property="last_name", type="array", @OA\Items(type="string", example="El apellido es requerido.")),
     *                 @OA\Property(property="password", type="array", @OA\Items(type="string", example="Las contraseñas no coinciden.")),
     *                 @OA\Property(property="phone", type="array", @OA\Items(type="string", example="El teléfono debe tener exactamente 8 dígitos.")),
     *                 @OA\Property(property="birth_date", type="array", @OA\Items(type="string", example="La fecha de nacimiento es requerida.")),
     *                 @OA\Property(property="gender", type="array", @OA\Items(type="string", example="El género es requerido.")),
     *                 @OA\Property(property="terms_accepted", type="array", @OA\Items(type="string", example="Debes aceptar los terminos y condiciones."))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Demasiadas solicitudes")
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Simple: crear cuenta nueva
        // La validación en RegisterRequest ya verifica que el email no exista (activo)
        $customer = Customer::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'oauth_provider' => 'local',
            'customer_type_id' => CustomerType::getDefault()?->id,
            'terms_accepted_at' => now(),
        ]);

        event(new Registered($customer));

        $customer->enforceTokenLimit(5);

        $tokenName = $this->generateTokenName($validated['device_identifier']);
        $newAccessToken = $customer->createToken($tokenName);
        $token = $newAccessToken->plainTextToken;

        // Sync device with token
        $this->deviceService->syncDeviceWithToken(
            $customer,
            $newAccessToken->accessToken,
            $validated['device_identifier']
        );

        return response()->json([
            'message' => __('auth.register_success'),
            'data' => AuthResource::make([
                'token' => $token,
                'customer' => $customer->load('customerType'),
            ]),
        ], 201);
    }

    /**
     * Authenticate customer and generate token.
     *
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Authentication"},
     *     summary="Iniciar sesión con email y contraseña",
     *     description="Autentica al cliente y devuelve token Sanctum. Limitado a 5 intentos por minuto.
     *
     * **Si la cuenta usa OAuth (Google/Apple):**
     * - Retorna 409 con `code: oauth_account_required`
     * - El cliente debe redirigir al usuario al flujo OAuth correspondiente
     *
     * **Si la cuenta fue eliminada:**
     * - Retorna 409 con `code: account_deleted_recoverable`
     * - Incluye días restantes para reactivar y puntos acumulados
     * - El cliente debe usar POST /api/v1/auth/reactivate para recuperar la cuenta",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","password","device_identifier"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!"),
     *             @OA\Property(property="device_identifier", type="string", example="550e8400-e29b-41d4-a716-446655440000", description="UUID único del dispositivo")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Inicio de sesión exitoso",
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
     *     @OA\Response(
     *         response=409,
     *         description="Conflicto - Cuenta OAuth o eliminada",
     *
     *         @OA\JsonContent(
     *             oneOf={
     *
     *                 @OA\Schema(
     *
     *                     @OA\Property(property="message", type="string", example="Esta cuenta usa autenticación con google. Por favor inicia sesión con google."),
     *                     @OA\Property(property="error_code", type="string", example="oauth_account_required"),
     *                     @OA\Property(property="data", type="object",
     *                         @OA\Property(property="oauth_provider", type="string", enum={"google", "apple"}, example="google", description="Proveedor OAuth que debe usar"),
     *                         @OA\Property(property="email", type="string", format="email", example="juan@example.com")
     *                     )
     *                 ),
     *
     *                 @OA\Schema(
     *
     *                     @OA\Property(property="message", type="string", example="Encontramos una cuenta eliminada con este correo."),
     *                     @OA\Property(property="error_code", type="string", example="account_deleted_recoverable"),
     *                     @OA\Property(property="data", type="object",
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", example="2025-11-15T10:30:00Z"),
     *                         @OA\Property(property="days_until_permanent_deletion", type="integer", example=15),
     *                         @OA\Property(property="points", type="integer", example=150),
     *                         @OA\Property(property="can_reactivate", type="boolean", example=true),
     *                         @OA\Property(property="oauth_provider", type="string", example="local")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Credenciales inválidas",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Las credenciales proporcionadas son incorrectas."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="Las credenciales proporcionadas son incorrectas."))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Demasiados intentos de acceso")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $this->ensureIsNotRateLimited($request);

        $customer = Customer::where('email', $request->email)->first();

        if (! $customer) {
            // Verificar si existe una cuenta eliminada (soft deleted)
            $deletedCustomer = Customer::where('email', $request->email)
                ->onlyTrashed()
                ->first();

            if ($deletedCustomer) {
                $deletedAt = Carbon::parse($deletedCustomer->deleted_at);
                $daysUntilPermanentDeletion = 30 - $deletedAt->diffInDays(now());

                // Si la cuenta fue eliminada hace menos de 30 días, ofrecer reactivación
                if ($daysUntilPermanentDeletion > 0) {
                    return response()->json([
                        'message' => __('auth.account_deleted_recoverable'),
                        'error_code' => 'account_deleted_recoverable',
                        'data' => [
                            'deleted_at' => $deletedAt->toIso8601String(),
                            'days_until_permanent_deletion' => $daysUntilPermanentDeletion,
                            'points' => $deletedCustomer->points ?? 0,
                            'can_reactivate' => true,
                            'oauth_provider' => $deletedCustomer->oauth_provider,
                        ],
                    ], 409);
                }

                // Más de 30 días - eliminar permanentemente
                $deletedCustomer->forceDelete();
            }

            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => [__('auth.account_not_found')],
            ]);
        }

        // Verificar si el usuario tiene contraseña configurada
        // Un usuario OAuth puede tener contraseña si la agregó después
        if ($customer->password === null) {
            return response()->json([
                'message' => __('auth.oauth_account', ['provider' => $customer->oauth_provider]),
                'error_code' => 'oauth_account_required',
                'data' => [
                    'oauth_provider' => $customer->oauth_provider,
                    'email' => $customer->email,
                ],
            ], 409);
        }

        if (! Hash::check($request->password, $customer->password)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'password' => [__('auth.incorrect_password')],
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        // Update last login timestamp
        $customer->update([
            'last_login_at' => now(),
            'last_activity_at' => now(),
        ]);

        $customer->enforceTokenLimit(5);

        $tokenName = $this->generateTokenName($request->device_identifier);
        $newAccessToken = $customer->createToken($tokenName);
        $token = $newAccessToken->plainTextToken;

        // Sync device with token
        $this->deviceService->syncDeviceWithToken(
            $customer,
            $newAccessToken->accessToken,
            $request->device_identifier
        );

        return response()->json([
            'message' => __('auth.login_success'),
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
     *             @OA\Property(property="message", type="string", example="Sesión cerrada")
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
            'message' => __('auth.logout_success'),
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
     *             @OA\Property(property="message", type="string", example="Todas las sesiones cerradas")
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
            'message' => __('auth.logout_all_success'),
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
     *             @OA\Property(property="message", type="string", example="Token renovado"),
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
            'message' => __('auth.token_refreshed'),
            'data' => AuthResource::make([
                'token' => $token,
                'customer' => $customer->load('customerType'),
            ]),
        ]);
    }

    /**
     * Request password reset link.
     *
     * @OA\Post(
     *     path="/api/v1/auth/forgot-password",
     *     tags={"Authentication"},
     *     summary="Solicitar restablecimiento de contraseña",
     *     description="Envía un enlace de restablecimiento de contraseña al correo del cliente. Solo funciona para cuentas locales (no OAuth).
     *
     * **Errores posibles:**
     * - email_not_found: No existe una cuenta activa con este correo
     * - oauth_no_password: La cuenta usa OAuth, no tiene contraseña",
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
     *         description="Enlace de restablecimiento enviado",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Enlace de restablecimiento enviado")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No existe una cuenta con este correo electrónico."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *
     *                     @OA\Items(type="string", example="No existe una cuenta con este correo electrónico.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Demasiadas solicitudes")
     * )
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        // Verificar si el email existe (solo cuentas activas)
        $customer = Customer::where('email', $request->email)->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'email' => [__('auth.email_not_found')],
            ]);
        }

        // Verificar si el usuario tiene contraseña configurada
        // Un usuario OAuth puede tener contraseña si la agregó después
        if ($customer->password === null) {
            throw ValidationException::withMessages([
                'email' => [__('auth.oauth_no_password', ['provider' => ucfirst($customer->oauth_provider)])],
            ]);
        }

        $status = Password::broker('customers')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_THROTTLED) {
            // Obtener tiempo de throttle de la configuración (default 60 segundos)
            $throttleSeconds = config('auth.passwords.customers.throttle', 60);

            throw ValidationException::withMessages([
                'email' => [__('auth.password_reset_throttled', ['seconds' => $throttleSeconds])],
            ]);
        }

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => __('auth.password_reset_link_sent'),
        ]);
    }

    /**
     * Reset password with token.
     *
     * @OA\Post(
     *     path="/api/v1/auth/reset-password",
     *     tags={"Authentication"},
     *     summary="Restablecer contraseña con token",
     *     description="Restablece la contraseña usando el token recibido por email. Revoca todos los tokens existentes por seguridad.
     *
     * **Errores posibles:**
     * - passwords.token: Token inválido o expirado
     * - passwords.user: No existe una cuenta con este email",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","password","password_confirmation","token"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="NuevaPass123!", description="Mínimo 8 caracteres, 1 letra, 1 número, 1 símbolo"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NuevaPass123!"),
     *             @OA\Property(property="token", type="string", example="abc123resettoken456", description="Token recibido en el email")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Contraseña restablecida exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Contraseña restablecida")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Token inválido o error de validación",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Este token de restablecimiento de contraseña es inválido."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *
     *                     @OA\Items(type="string", example="Este token de restablecimiento de contraseña es inválido.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Demasiadas solicitudes")
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
            'message' => __('auth.password_reset_success'),
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
     *             @OA\Property(property="message", type="string", example="Email verificado")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Invalid verification link"),
     *     @OA\Response(response=429, description="Too many requests")
     * )
     */
    public function verifyEmail(Request $request): JsonResponse|\Illuminate\Contracts\View\View
    {
        $customer = Customer::findOrFail($request->route('id'));

        if (! hash_equals(sha1($customer->email), (string) $request->route('hash'))) {
            if ($request->isMethod('get')) {
                return $this->verifyEmailWebResponse(false, __('auth.invalid_verification_link'));
            }
            throw ValidationException::withMessages([
                'email' => [__('auth.invalid_verification_link')],
            ]);
        }

        $alreadyVerified = $customer->hasVerifiedEmail();

        if (! $alreadyVerified && $customer->markEmailAsVerified()) {
            event(new Verified($customer));
        }

        $message = $alreadyVerified
            ? __('auth.email_already_verified')
            : __('auth.email_verified');

        // For GET requests (browser clicks), return HTML page
        if ($request->isMethod('get')) {
            return $this->verifyEmailWebResponse(true, $message);
        }

        // For POST requests (API), return JSON
        return response()->json(['message' => $message]);
    }

    /**
     * Generate HTML response for web-based email verification.
     */
    private function verifyEmailWebResponse(bool $success, string $message): \Illuminate\Contracts\View\View
    {
        return view('auth.email-verified', [
            'success' => $success,
            'message' => $message,
            'scheme' => config('app.mobile_scheme', 'subwayapp'),
            'appName' => config('app.mobile_name', 'Subway App'),
            'baseUrl' => config('app.url'),
            'status' => $success ? 'success' : 'error',
            'subtitle' => $success
                ? 'Tu correo ha sido verificado. Ya puedes usar todas las funciones de la app.'
                : 'El enlace es inválido o ha expirado. Solicita uno nuevo desde la app.',
        ]);
    }

    /**
     * Handle expired or invalid verification link.
     * Called from exception handler for InvalidSignatureException.
     */
    public function handleExpiredVerificationLink(): \Illuminate\Contracts\View\View
    {
        return $this->verifyEmailWebResponse(false, __('auth.verification_link_expired'));
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
     *             @OA\Property(property="message", type="string", example="Enlace reenviado")
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
                'email' => [__('auth.account_not_found')],
            ]);
        }

        if ($customer->hasVerifiedEmail()) {
            return response()->json([
                'message' => __('auth.email_already_verified'),
            ]);
        }

        $customer->sendEmailVerificationNotification();

        return response()->json([
            'message' => __('auth.verification_link_resent'),
        ]);
    }

    /**
     * Reactivate a soft-deleted customer account.
     *
     * @OA\Post(
     *     path="/api/v1/auth/reactivate",
     *     tags={"Authentication"},
     *     summary="Reactivar cuenta eliminada",
     *     description="Reactiva una cuenta de cliente que fue soft-deleted. Solo funciona si no han pasado más de 30 días desde la eliminación.
     *
     * **Flujo:**
     * 1. Busca cuenta eliminada con el email proporcionado
     * 2. Verifica que no hayan pasado más de 30 días desde eliminación
     * 3. Verifica identidad con: password, google_token, o apple_token
     * 4. Restaura la cuenta y genera nuevo token
     * 5. Retorna datos del usuario incluyendo puntos acumulados
     *
     * **Métodos de verificación (uno es requerido):**
     * - password: Si la cuenta tiene contraseña configurada
     * - google_token: Token de Google OAuth (si tiene google_id vinculado)
     *
     * **Errores posibles:**
     * - account_not_found_deleted: No existe cuenta eliminada con este email
     * - reactivation_period_expired: Han pasado más de 30 días desde eliminación
     * - incorrect_password: Contraseña incorrecta
     * - oauth_invalid_token: Token OAuth inválido",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email","device_identifier"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com", description="Email de la cuenta eliminada"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!", description="Contraseña (si la cuenta tiene password)"),
     *             @OA\Property(property="google_token", type="string", example="eyJhbGciOiJS...", description="Token de Google OAuth (alternativa a password)"),
     *             @OA\Property(property="device_identifier", type="string", example="550e8400-e29b-41d4-a716-446655440000", description="Identificador único del dispositivo")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cuenta reactivada exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Cuenta reactivada. Bienvenido."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer"),
     *                 @OA\Property(property="token", type="string", example="1|abc123xyz..."),
     *                 @OA\Property(property="points", type="integer", example=150, description="Puntos acumulados que se conservaron"),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", example="2025-11-20T10:30:00.000000Z", description="Fecha original de eliminación")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="No se encontró una cuenta eliminada con este correo electrónico."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *
     *                     @OA\Items(type="string", example="No se encontró una cuenta eliminada con este correo electrónico.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=429, description="Demasiadas solicitudes")
     * )
     */
    public function reactivateAccount(ReactivateAccountRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Buscar cuenta eliminada con el email proporcionado
        $customer = Customer::onlyTrashed()
            ->where('email', $validated['email'])
            ->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'email' => [__('auth.account_not_found_deleted')],
            ]);
        }

        // Verificar que no hayan pasado más de 30 días desde la eliminación
        $deletedAt = $customer->deleted_at;
        $daysSinceDeletion = $deletedAt->diffInDays(now());

        if ($daysSinceDeletion > 30) {
            // Limpiar la cuenta expirada
            $customer->forceDelete();

            throw ValidationException::withMessages([
                'email' => [__('auth.reactivation_period_expired')],
            ]);
        }

        // Verificar identidad del usuario con uno de los métodos disponibles
        $identityVerified = false;

        // Opción 1: Verificar con Google token
        if (! $identityVerified && isset($validated['google_token'])) {
            try {
                $providerData = $this->socialAuthService->verifyGoogleToken($validated['google_token']);
                // Verificar que el email coincida
                if ($providerData->email === $customer->email) {
                    $identityVerified = true;
                    // Actualizar google_id si no estaba vinculado
                    if (! $customer->google_id) {
                        $customer->google_id = $providerData->provider_id;
                    }
                }
            } catch (ValidationException $e) {
                // Token inválido, continuar con otros métodos
            }
        }

        // Opción 2: Verificar con contraseña
        if (! $identityVerified && isset($validated['password'])) {
            if ($customer->password !== null && Hash::check($validated['password'], $customer->password)) {
                $identityVerified = true;
            }
        }

        // Si no se verificó identidad, determinar qué método se esperaba
        if (! $identityVerified) {
            // Si enviaron password pero falló
            if (isset($validated['password'])) {
                throw ValidationException::withMessages([
                    'password' => [__('auth.incorrect_password')],
                ]);
            }

            // Si enviaron token OAuth pero falló
            if (isset($validated['google_token'])) {
                throw ValidationException::withMessages([
                    'google_token' => [__('auth.oauth_invalid_token')],
                ]);
            }

            // No enviaron ningún método de verificación
            $availableMethods = [];
            if ($customer->password !== null) {
                $availableMethods[] = 'password';
            }
            if ($customer->google_id) {
                $availableMethods[] = 'google_token';
            }

            throw ValidationException::withMessages([
                'auth' => ['Se requiere verificación de identidad. Métodos disponibles: '.implode(', ', $availableMethods)],
            ]);
        }

        // Guardar puntos antes de restaurar
        $preservedPoints = $customer->points ?? 0;
        $originalDeletedAt = $customer->deleted_at;

        // Restaurar la cuenta
        $customer->restore();

        // Update last login timestamp
        $customer->update([
            'last_login_at' => now(),
            'last_activity_at' => now(),
        ]);

        // Enforce token limit
        $customer->enforceTokenLimit(5);

        // Generar nuevo token
        $tokenName = $this->generateTokenName($validated['device_identifier']);
        $newAccessToken = $customer->createToken($tokenName);
        $token = $newAccessToken->plainTextToken;

        // Sync device with token
        $this->deviceService->syncDeviceWithToken(
            $customer,
            $newAccessToken->accessToken,
            $validated['device_identifier']
        );

        return response()->json([
            'message' => __('auth.account_reactivated'),
            'data' => [
                'token' => $token,
                'customer' => new \App\Http\Resources\Api\V1\CustomerResource($customer->load('customerType')),
                'points' => $preservedPoints,
                'deleted_at' => $originalDeletedAt->toIso8601String(),
            ],
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
