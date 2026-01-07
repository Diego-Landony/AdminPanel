<?php

namespace App\Http\Requests\Customer;

use App\Rules\CustomPassword;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customerId = $this->route('customer')->id;

        return [
            'full_name' => 'required|string|max:255',
            'email' => "required|string|lowercase|email|max:255|unique:customers,email,{$customerId}",
            'password' => ['nullable', 'string', 'confirmed', new CustomPassword],
            'subway_card' => "required|string|max:255|unique:customers,subway_card,{$customerId}",
            'birth_date' => 'required|date|before:today',
            'gender' => 'nullable|string|max:50',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'nit' => 'nullable|string|max:255',
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
            'full_name.required' => 'El nombre completo es obligatorio',
            'full_name.max' => 'El nombre completo no puede exceder 255 caracteres',
            'email.required' => 'El correo electrónico es obligatorio',
            'email.email' => 'El correo electrónico debe ser válido',
            'email.unique' => 'Este correo electrónico ya está registrado por otro cliente',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'subway_card.required' => 'La tarjeta Subway es obligatoria',
            'subway_card.unique' => 'Esta tarjeta Subway ya está registrada por otro cliente',
            'birth_date.required' => 'La fecha de nacimiento es obligatoria',
            'birth_date.date' => 'La fecha de nacimiento debe ser válida',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy',
            'customer_type_id.exists' => 'El tipo de cliente seleccionado no existe',
            'phone.max' => 'El teléfono no puede exceder 255 caracteres',
            'address.max' => 'La dirección no puede exceder 1000 caracteres',
            'location.max' => 'La ubicación no puede exceder 255 caracteres',
            'nit.max' => 'El NIT no puede exceder 255 caracteres',
        ];
    }
}
