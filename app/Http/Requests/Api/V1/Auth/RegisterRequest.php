<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Enums\Gender;
use App\Rules\CustomPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('customers', 'email')],
            'password' => ['required', 'confirmed', new CustomPassword],
            'phone' => ['required', 'string', 'size:8', 'regex:/^[0-9]{8}$/'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'device_identifier' => ['required', 'string', 'max:255'],
            'terms_accepted' => ['required', 'accepted'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre es requerido.',
            'last_name.required' => 'El apellido es requerido.',
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es requerida.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'phone.required' => 'El teléfono es requerido.',
            'phone.size' => 'El teléfono debe tener exactamente 8 dígitos.',
            'phone.regex' => 'El teléfono debe contener solo números.',
            'birth_date.required' => 'La fecha de nacimiento es requerida.',
            'birth_date.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'gender.required' => 'El género es requerido.',
            'gender.enum' => 'El género seleccionado no es válido.',
            'device_identifier.required' => 'El identificador de dispositivo es requerido.',
            'device_identifier.max' => 'El identificador de dispositivo no puede exceder 255 caracteres.',
            'terms_accepted.required' => 'Debes aceptar los terminos y condiciones.',
            'terms_accepted.accepted' => 'Debes aceptar los terminos y condiciones.',
        ];
    }
}
