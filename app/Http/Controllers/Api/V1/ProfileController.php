<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Get customer profile.
     *
     * @OA\Get(
     *     path="/api/v1/profile",
     *     tags={"Profile"},
     *     summary="Get customer profile",
     *     description="Returns authenticated customer profile with addresses, NITs, and active devices.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request): JsonResponse
    {
        $customer = $request->user()->load([
            'customerType',
            'addresses' => function ($query) {
                $query->orderBy('is_default', 'desc');
            },
            'nits' => function ($query) {
                $query->orderBy('is_default', 'desc');
            },
            'activeDevices',
        ]);

        return response()->json([
            'data' => [
                'customer' => CustomerResource::make($customer),
            ],
        ]);
    }

    /**
     * Update customer profile.
     *
     * @OA\Put(
     *     path="/api/v1/profile",
     *     tags={"Profile"},
     *     summary="Update customer profile",
     *     description="Updates customer profile information. If email is changed, marks email as unverified.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="first_name", type="string", example="Juan"),
     *             @OA\Property(property="last_name", type="string", example="Pérez Actualizado"),
     *             @OA\Property(property="email", type="string", format="email", example="nuevo@example.com"),
     *             @OA\Property(property="phone", type="string", nullable=true, example="+50212345678"),
     *             @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="1990-05-15"),
     *             @OA\Property(property="gender", type="string", enum={"male","female","other"}, nullable=true, example="male")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Perfil actualizado exitosamente."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer", ref="#/components/schemas/Customer")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $customer = $request->user();
        $validated = $request->validated();

        // If email is changed, mark as unverified
        if (isset($validated['email']) && $validated['email'] !== $customer->email) {
            $validated['email_verified_at'] = null;
        }

        $customer->update($validated);

        return response()->json([
            'message' => 'Perfil actualizado exitosamente.',
            'data' => [
                'customer' => CustomerResource::make($customer->fresh()->load('customerType')),
            ],
        ]);
    }

    /**
     * Delete customer account.
     *
     * @OA\Delete(
     *     path="/api/v1/profile",
     *     tags={"Profile"},
     *     summary="Eliminar cuenta de cliente",
     *     description="Soft delete de la cuenta del cliente. Revoca todos los tokens.
     *
     * **Para cuentas locales (email/contraseña):**
     * - Requiere: password (contraseña actual para confirmar)
     *
     * **Para cuentas OAuth (Google/Apple) sin contraseña:**
     * - NO requiere password (el body puede estar vacío o enviar `{}`)
     *
     * **Período de gracia:**
     * - La cuenta se puede recuperar dentro de 30 días usando POST /api/v1/auth/reactivate
     * - Después de 30 días, la cuenta se elimina permanentemente",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!", description="Contraseña actual. SOLO requerido para cuentas locales (oauth_provider='local'). NO enviar si es cuenta OAuth.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cuenta eliminada exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Cuenta eliminada exitosamente."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="can_reactivate_until", type="string", format="date-time", example="2025-12-27T10:30:00Z", description="Fecha límite para reactivar la cuenta"),
     *                 @OA\Property(property="days_to_reactivate", type="integer", example=30, description="Días restantes para reactivar")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(
     *         response=422,
     *         description="Contraseña incorrecta (solo cuentas locales)",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="La contraseña es incorrecta."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="password", type="array",
     *
     *                     @OA\Items(type="string", example="La contraseña es incorrecta.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function destroy(Request $request): JsonResponse
    {
        $customer = $request->user();

        // Solo validar y verificar contraseña para cuentas locales
        if ($customer->oauth_provider === 'local') {
            $request->validate([
                'password' => ['required', 'string'],
            ]);

            if (! Hash::check($request->password, $customer->password)) {
                throw ValidationException::withMessages([
                    'password' => [__('auth.incorrect_password')],
                ]);
            }
        }

        // Revoke all tokens
        $customer->tokens()->delete();

        // Soft delete customer
        $customer->delete();

        return response()->json([
            'message' => 'Cuenta eliminada exitosamente.',
            'data' => [
                'can_reactivate_until' => now()->addDays(30)->toIso8601String(),
                'days_to_reactivate' => 30,
            ],
        ]);
    }

    /**
     * Update customer avatar.
     *
     * @OA\Post(
     *     path="/api/v1/profile/avatar",
     *     tags={"Profile"},
     *     summary="Update customer avatar",
     *     description="Updates customer avatar URL. Can be a URL to uploaded image or OAuth provider avatar.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"avatar"},
     *
     *             @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg", description="Avatar URL")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Avatar updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Avatar actualizado exitosamente."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="avatar", type="string", example="https://example.com/avatar.jpg")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'avatar' => ['required', 'string'],
        ]);

        $customer = $request->user();
        $customer->update(['avatar' => $validated['avatar']]);

        return response()->json([
            'message' => 'Avatar actualizado exitosamente.',
            'data' => [
                'avatar' => $customer->avatar,
            ],
        ]);
    }

    /**
     * Delete customer avatar.
     *
     * @OA\Delete(
     *     path="/api/v1/profile/avatar",
     *     tags={"Profile"},
     *     summary="Delete customer avatar",
     *     description="Removes customer avatar. Sets avatar to null.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Avatar deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Avatar eliminado exitosamente.")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $customer = $request->user();
        $customer->update(['avatar' => null]);

        return response()->json([
            'message' => 'Avatar eliminado exitosamente.',
        ]);
    }

    /**
     * Update or create customer password.
     *
     * @OA\Put(
     *     path="/api/v1/profile/password",
     *     tags={"Profile"},
     *     summary="Actualizar o crear contraseña",
     *     description="Cambia o crea la contraseña del cliente. Revoca todos los tokens excepto el actual por seguridad.
     *
     * **Para cuentas locales (email/contraseña):**
     * - Requiere: current_password, password, password_confirmation
     * - La nueva contraseña debe ser diferente a la actual
     *
     * **Para cuentas OAuth (Google/Apple) sin contraseña:**
     * - Requiere: password, password_confirmation
     * - NO requiere current_password (están creando su primera contraseña)
     * - Después de crear contraseña, pueden usar ambos métodos: OAuth y contraseña
     *
     * **Comportamiento después de crear contraseña (OAuth):**
     * - El usuario mantiene su google_id/apple_id vinculado
     * - oauth_provider cambia a 'local'
     * - Puede iniciar sesión con Google/Apple O con email+contraseña",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"password","password_confirmation"},
     *
     *             @OA\Property(property="current_password", type="string", format="password", example="OldPass123!", description="Contraseña actual. SOLO requerido para cuentas locales (oauth_provider='local'). NO enviar si es cuenta OAuth."),
     *             @OA\Property(property="password", type="string", format="password", example="NuevaPass123!", description="Nueva contraseña (mínimo 6 caracteres, al menos 1 letra y 1 número)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NuevaPass123!", description="Debe coincidir con password")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Contraseña actualizada/creada exitosamente",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Contraseña creada exitosamente. Ahora puedes iniciar sesión con tu correo y contraseña."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="password_created", type="boolean", example=true, description="true si es primera contraseña (usuario OAuth), false si fue cambio de contraseña existente"),
     *                 @OA\Property(property="can_use_password_login", type="boolean", example=true, description="Siempre true después de esta operación")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="La contraseña actual es incorrecta."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="current_password", type="array",
     *
     *                     @OA\Items(type="string", example="La contraseña actual es incorrecta.")
     *                 ),
     *
     *                 @OA\Property(property="password", type="array",
     *
     *                     @OA\Items(type="string", example="Las contraseñas no coinciden.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function updatePassword(ChangePasswordRequest $request): JsonResponse
    {
        $customer = $request->user();
        $validated = $request->validated();

        // Determinar si es creación de contraseña (OAuth) o cambio (local)
        $isCreatingPassword = $customer->oauth_provider !== 'local';

        $customer->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Si era cuenta OAuth, ahora puede usar contraseña también
        // Cambiamos a 'local' para permitir login con contraseña
        if ($isCreatingPassword) {
            $customer->update([
                'oauth_provider' => 'local',
            ]);
        }

        // Revoke all tokens except current one for security
        $currentToken = $customer->currentAccessToken();
        $customer->tokens()->where('id', '!=', $currentToken->id)->delete();

        $message = $isCreatingPassword
            ? __('auth.password_created')
            : __('auth.password_updated');

        return response()->json([
            'message' => $message,
            'data' => [
                'password_created' => $isCreatingPassword,
                'can_use_password_login' => true,
            ],
        ]);
    }
}
