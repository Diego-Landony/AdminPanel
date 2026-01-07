<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class CustomerPasswordResetController extends Controller
{
    /**
     * Show the password reset form.
     */
    public function showResetForm(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');

        if (! $token || ! $email) {
            return view('auth.customer-password-reset', [
                'error' => 'Enlace de restablecimiento inválido. Por favor, solicita un nuevo enlace.',
                'token' => null,
                'email' => null,
            ]);
        }

        return view('auth.customer-password-reset', [
            'token' => $token,
            'email' => $email,
            'error' => null,
        ]);
    }

    /**
     * Handle the password reset.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [
            'password.required' => 'La contraseña es requerida.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
        ]);

        // Use the customers password broker
        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password) {
                // Solo actualizar password, NO cambiar oauth_provider
                // El usuario puede usar ambos métodos: OAuth y contraseña
                $customer->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $customer->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return view('auth.customer-password-reset', [
                'success' => true,
                'message' => '¡Tu contraseña ha sido restablecida exitosamente! Ya puedes iniciar sesión en la app.',
                'token' => null,
                'email' => null,
            ]);
        }

        // Handle errors
        $errorMessages = [
            Password::INVALID_TOKEN => 'El enlace de restablecimiento ha expirado o es inválido. Por favor, solicita uno nuevo.',
            Password::INVALID_USER => 'No encontramos una cuenta con ese correo electrónico.',
            Password::THROTTLED => 'Por favor espera antes de intentar nuevamente.',
        ];

        return view('auth.customer-password-reset', [
            'token' => $request->token,
            'email' => $request->email,
            'error' => $errorMessages[$status] ?? 'Ocurrió un error al restablecer la contraseña.',
        ]);
    }
}
