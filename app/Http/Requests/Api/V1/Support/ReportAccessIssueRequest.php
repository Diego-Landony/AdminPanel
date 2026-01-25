<?php

namespace App\Http\Requests\Api\V1\Support;

use Illuminate\Foundation\Http\FormRequest;

class ReportAccessIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'dpi' => ['nullable', 'string', 'regex:/^\d+$/', 'max:20'],
            'issue_type' => ['required', 'string', 'in:cant_find_account,cant_login,account_locked,no_reset_email,other'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'issue_type.required' => 'El tipo de problema es obligatorio.',
            'issue_type.in' => 'El tipo de problema no es válido.',
            'description.required' => 'La descripción del problema es obligatoria.',
            'description.max' => 'La descripción no puede tener más de 2000 caracteres.',
        ];
    }
}
