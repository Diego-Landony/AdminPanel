<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class SendSupportMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:5000', 'required_without:attachments'],
            'attachments' => ['nullable', 'array', 'max:4'],
            'attachments.*' => ['image', 'mimes:jpeg,png,gif,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required_without' => 'Debes escribir un mensaje o adjuntar al menos una imagen.',
            'message.max' => 'El mensaje no puede tener más de 5000 caracteres.',
            'attachments.max' => 'Puedes adjuntar un máximo de 4 imágenes.',
            'attachments.*.image' => 'Solo se permiten archivos de imagen.',
            'attachments.*.mimes' => 'Solo se permiten imágenes en formato jpeg, png, gif o webp.',
            'attachments.*.max' => 'Cada imagen no puede pesar más de 5MB.',
        ];
    }
}
