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
     *     summary="Delete customer account",
     *     description="Soft deletes customer account. Requires password confirmation for local accounts. Revokes all tokens.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"password"},
     *
     *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!", description="Current password for verification")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Account deleted successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Cuenta eliminada exitosamente.")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Incorrect password")
     * )
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $customer = $request->user();

        // Verify password for local accounts
        if ($customer->oauth_provider === 'local') {
            if (! Hash::check($request->password, $customer->password)) {
                throw ValidationException::withMessages([
                    'password' => ['La contraseña es incorrecta.'],
                ]);
            }
        }

        // Revoke all tokens
        $customer->tokens()->delete();

        // Soft delete customer
        $customer->delete();

        return response()->json([
            'message' => 'Cuenta eliminada exitosamente.',
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
     * Update customer password.
     *
     * @OA\Put(
     *     path="/api/v1/profile/password",
     *     tags={"Profile"},
     *     summary="Update customer password",
     *     description="Changes customer password. Only for local accounts (not OAuth). Revokes all tokens except current one for security.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"current_password","password","password_confirmation"},
     *
     *             @OA\Property(property="current_password", type="string", format="password", example="OldPass123!", description="Current password for verification"),
     *             @OA\Property(property="password", type="string", format="password", example="NewSecurePass123!", description="New password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="NewSecurePass123!", description="Must match new password")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Contraseña actualizada exitosamente. Se cerraron todas las otras sesiones.")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error or OAuth account")
     * )
     */
    public function updatePassword(ChangePasswordRequest $request): JsonResponse
    {
        $customer = $request->user();

        // Only local accounts can change password
        if ($customer->oauth_provider !== 'local') {
            return response()->json([
                'message' => 'Las cuentas de '.$customer->oauth_provider.' no pueden cambiar la contraseña.',
            ], 422);
        }

        $validated = $request->validated();

        $customer->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Revoke all tokens except current one for security
        $currentToken = $customer->currentAccessToken();
        $customer->tokens()->where('id', '!=', $currentToken->id)->delete();

        return response()->json([
            'message' => 'Contraseña actualizada exitosamente. Se cerraron todas las otras sesiones.',
        ]);
    }
}
