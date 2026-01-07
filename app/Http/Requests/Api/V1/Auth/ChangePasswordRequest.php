<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Rules\CustomPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authenticated users only (via middleware)
    }

    /**
     * Check if user has a password set.
     * Un usuario tiene contraseña si el campo password no es null.
     * Esto aplica tanto para cuentas locales como OAuth que agregaron contraseña.
     */
    protected function userHasPassword(): bool
    {
        return $this->user()->password !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'string', 'confirmed', new CustomPassword],
        ];

        // Solo requerir current_password si el usuario tiene contraseña (cuenta local)
        if ($this->userHasPassword()) {
            $rules['current_password'] = ['required', 'string'];
            $rules['password'][] = 'different:current_password';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'La contraseña actual es requerida.',
            'password.required' => 'La nueva contraseña es requerida.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.different' => 'La nueva contraseña debe ser diferente a la actual.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Solo validar current_password si el usuario tiene contraseña
            if ($this->userHasPassword() && $this->current_password) {
                if (! Hash::check($this->current_password, $this->user()->password)) {
                    $validator->errors()->add('current_password', 'La contraseña actual es incorrecta.');
                }
            }
        });
    }
}
