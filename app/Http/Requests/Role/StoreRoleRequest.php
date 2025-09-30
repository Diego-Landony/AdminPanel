<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name',
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
            'name.required' => 'El nombre del rol es obligatorio',
            'name.unique' => 'Este nombre de rol ya existe en el sistema',
            'name.max' => 'El nombre del rol no puede exceder 255 caracteres',
            'description.max' => 'La descripción no puede exceder 500 caracteres',
            'permissions.required' => 'Debes seleccionar al menos un permiso para el rol',
            'permissions.min' => 'Debes seleccionar al menos un permiso para el rol',
            'permissions.*.exists' => 'Uno o más permisos seleccionados no son válidos',
        ];
    }
}
