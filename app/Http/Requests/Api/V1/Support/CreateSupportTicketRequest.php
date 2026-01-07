<?php

namespace App\Http\Requests\Api\V1\Support;

use Illuminate\Foundation\Http\FormRequest;

class CreateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason_id' => ['required', 'exists:support_reasons,id'],
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:4'],
            'attachments.*' => ['image', 'mimes:jpeg,png,gif,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason_id.required' => 'El motivo es obligatorio.',
            'reason_id.exists' => 'El motivo seleccionado no es válido.',
            'message.required' => 'El mensaje es obligatorio.',
            'message.max' => 'El mensaje no puede tener más de 5000 caracteres.',
            'attachments.max' => 'Puedes adjuntar un máximo de 4 imágenes.',
            'attachments.*.image' => 'Solo se permiten archivos de imagen.',
            'attachments.*.mimes' => 'Solo se permiten imágenes en formato jpeg, png, gif o webp.',
            'attachments.*.max' => 'Cada imagen no puede pesar más de 5MB.',
        ];
    }
}
