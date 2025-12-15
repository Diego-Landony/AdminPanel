<?php

namespace App\Http\Requests\Api\V1\Favorite;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'favorable_type' => ['required', 'string', Rule::in(['product', 'combo'])],
            'favorable_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $type = $this->input('favorable_type');
                    $modelClass = $type === 'product'
                        ? \App\Models\Menu\Product::class
                        : \App\Models\Menu\Combo::class;

                    if (! $modelClass::where('id', $value)->exists()) {
                        $fail('El '.($type === 'product' ? 'producto' : 'combo').' seleccionado no existe.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'favorable_type.required' => 'El tipo de favorito es requerido',
            'favorable_type.in' => 'El tipo de favorito debe ser producto o combo',
            'favorable_id.required' => 'El ID del elemento es requerido',
            'favorable_id.integer' => 'El ID debe ser un número entero',
        ];
    }
}
