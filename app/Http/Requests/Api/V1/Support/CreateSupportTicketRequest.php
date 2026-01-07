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
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:4'],
            'attachments.*' => ['image', 'mimes:jpeg,png,gif,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede tener más de 255 caracteres.',
            'message.required' => 'El mensaje es obligatorio.',
            'message.max' => 'El mensaje no puede tener más de 5000 caracteres.',
            'attachments.max' => 'Puedes adjuntar un máximo de 4 imágenes.',
            'attachments.*.image' => 'Solo se permiten archivos de imagen.',
            'attachments.*.mimes' => 'Solo se permiten imágenes en formato jpeg, png, gif o webp.',
            'attachments.*.max' => 'Cada imagen no puede pesar más de 5MB.',
        ];
    }
}
