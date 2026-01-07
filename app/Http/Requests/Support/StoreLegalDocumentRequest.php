<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;

class StoreLegalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_json' => ['required', 'array'],
            'content_html' => ['required', 'string'],
            'version' => ['nullable', 'string', 'max:20'],
            'publish' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'content_json.required' => 'El contenido es obligatorio.',
            'content_html.required' => 'El contenido HTML es obligatorio.',
            'version.max' => 'La versión no puede tener más de 20 caracteres.',
        ];
    }
}
