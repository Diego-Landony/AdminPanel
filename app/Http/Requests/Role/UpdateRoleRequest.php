<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Verificar permisos de edición según tipo de rol
        $role = $this->route('role');
        $currentUser = auth()->user();
        $isCurrentUserAdmin = $currentUser && $currentUser->hasRole('admin');

        // Solo administradores pueden editar roles del sistema
        if ($role->is_system && $role->name !== 'admin' && ! $isCurrentUserAdmin) {
            return false;
        }

        // Para el rol "admin", solo usuarios administradores pueden editarlo
        if ($role->name === 'admin' && ! $isCurrentUserAdmin) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleId = $this->route('role')->id;

        return [
            'name' => "required|string|max:255|unique:roles,name,{$roleId}",
            'description' => 'nullable|string|max:500',
            'permissions' => 'array',
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
            'permissions.*.exists' => 'Uno o más permisos seleccionados no son válidos',
        ];
    }
}
