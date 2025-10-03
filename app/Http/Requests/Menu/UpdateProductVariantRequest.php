<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para actualizar una variante de producto
 *
 * Incluye los 4 precios regulares, configuración de Sub del Día,
 * y los 4 precios especiales del Sub del Día
 */
class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('menu.products.edit');
    }

    public function rules(): array
    {
        return [
            // Precios regulares (obligatorios)
            'precio_pickup_capital' => 'required|numeric|min:0',
            'precio_domicilio_capital' => 'required|numeric|min:0',
            'precio_pickup_interior' => 'required|numeric|min:0',
            'precio_domicilio_interior' => 'required|numeric|min:0',

            // Sub del Día
            'is_daily_special' => 'boolean',
            'daily_special_days' => 'nullable|array',
            'daily_special_days.*' => 'integer|between:0,6',

            // Precios especiales del Sub del Día (opcionales)
            'daily_special_precio_pickup_capital' => 'nullable|numeric|min:0',
            'daily_special_precio_domicilio_capital' => 'nullable|numeric|min:0',
            'daily_special_precio_pickup_interior' => 'nullable|numeric|min:0',
            'daily_special_precio_domicilio_interior' => 'nullable|numeric|min:0',

            // Estado
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            // Precios regulares
            'precio_pickup_capital.required' => 'El precio pickup capital es obligatorio.',
            'precio_pickup_capital.numeric' => 'El precio debe ser un número.',
            'precio_pickup_capital.min' => 'El precio debe ser mayor o igual a 0.',

            'precio_domicilio_capital.required' => 'El precio domicilio capital es obligatorio.',
            'precio_domicilio_capital.numeric' => 'El precio debe ser un número.',
            'precio_domicilio_capital.min' => 'El precio debe ser mayor o igual a 0.',

            'precio_pickup_interior.required' => 'El precio pickup interior es obligatorio.',
            'precio_pickup_interior.numeric' => 'El precio debe ser un número.',
            'precio_pickup_interior.min' => 'El precio debe ser mayor o igual a 0.',

            'precio_domicilio_interior.required' => 'El precio domicilio interior es obligatorio.',
            'precio_domicilio_interior.numeric' => 'El precio debe ser un número.',
            'precio_domicilio_interior.min' => 'El precio debe ser mayor o igual a 0.',

            // Sub del Día
            'is_daily_special.boolean' => 'El campo Sub del Día debe ser verdadero o falso.',
            'daily_special_days.array' => 'Los días del Sub del Día deben ser un arreglo.',
            'daily_special_days.*.integer' => 'Cada día debe ser un número entero.',
            'daily_special_days.*.between' => 'Los días deben estar entre 0 (Domingo) y 6 (Sábado).',

            // Precios especiales
            'daily_special_precio_pickup_capital.numeric' => 'El precio especial debe ser un número.',
            'daily_special_precio_pickup_capital.min' => 'El precio especial debe ser mayor o igual a 0.',

            'daily_special_precio_domicilio_capital.numeric' => 'El precio especial debe ser un número.',
            'daily_special_precio_domicilio_capital.min' => 'El precio especial debe ser mayor o igual a 0.',

            'daily_special_precio_pickup_interior.numeric' => 'El precio especial debe ser un número.',
            'daily_special_precio_pickup_interior.min' => 'El precio especial debe ser mayor o igual a 0.',

            'daily_special_precio_domicilio_interior.numeric' => 'El precio especial debe ser un número.',
            'daily_special_precio_domicilio_interior.min' => 'El precio especial debe ser mayor o igual a 0.',

            // Estado
            'is_active.boolean' => 'El estado debe ser verdadero o falso.',
        ];
    }
}
