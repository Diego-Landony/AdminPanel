<?php

namespace App\Http\Requests\Api\V1\Menu;

use Illuminate\Foundation\Http\FormRequest;

class GetNearbyRestaurantsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:0.1', 'max:50'],
            'delivery_active' => ['nullable', 'boolean'],
            'pickup_active' => ['nullable', 'boolean'],
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
            'latitude.required' => 'La latitud es requerida.',
            'latitude.numeric' => 'La latitud debe ser un número.',
            'latitude.between' => 'La latitud debe estar entre -90 y 90.',
            'longitude.required' => 'La longitud es requerida.',
            'longitude.numeric' => 'La longitud debe ser un número.',
            'longitude.between' => 'La longitud debe estar entre -180 y 180.',
            'radius_km.numeric' => 'El radio debe ser un número.',
            'radius_km.min' => 'El radio debe ser al menos 0.1 km.',
            'radius_km.max' => 'El radio no puede exceder 50 km.',
            'delivery_active.boolean' => 'El campo delivery activo debe ser verdadero o falso.',
            'pickup_active.boolean' => 'El campo pickup activo debe ser verdadero o falso.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'latitude' => 'latitud',
            'longitude' => 'longitud',
            'radius_km' => 'radio en kilómetros',
            'delivery_active' => 'delivery activo',
            'pickup_active' => 'pickup activo',
        ];
    }

    /**
     * Get validated data with defaults.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        return array_merge([
            'radius_km' => 10,
        ], $validated);
    }
}
