<?php

namespace App\Http\Requests\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionalBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'orientation' => 'required|in:horizontal,vertical',
            'display_seconds' => 'required|integer|min:1|max:30',
            'link_type' => 'nullable|in:product,combo,category,promotion,url,none',
            'link_id' => 'nullable|required_if:link_type,product,combo,category,promotion|integer',
            'link_url' => 'nullable|required_if:link_type,url|url|max:500',
            'validity_type' => 'required|in:permanent,date_range,weekdays',
            'valid_from' => 'nullable|required_if:validity_type,date_range|date',
            'valid_until' => 'nullable|required_if:validity_type,date_range|date|after_or_equal:valid_from',
            'weekdays' => 'nullable|required_if:validity_type,weekdays|array|min:1',
            'weekdays.*' => 'integer|min:1|max:7',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El titulo es obligatorio.',
            'title.max' => 'El titulo no puede tener mas de 100 caracteres.',
            'image.required' => 'La imagen es obligatoria.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.mimes' => 'La imagen debe ser jpeg, png, jpg, gif o webp.',
            'image.max' => 'La imagen no puede pesar mas de 5MB.',
            'orientation.required' => 'La orientacion es obligatoria.',
            'orientation.in' => 'La orientacion debe ser horizontal o vertical.',
            'display_seconds.required' => 'El tiempo de visualizacion es obligatorio.',
            'display_seconds.min' => 'El tiempo minimo es 1 segundo.',
            'display_seconds.max' => 'El tiempo maximo es 30 segundos.',
            'link_id.required_if' => 'Debe seleccionar un elemento para el enlace.',
            'link_url.required_if' => 'La URL es obligatoria cuando el tipo de enlace es URL.',
            'link_url.url' => 'La URL debe ser valida.',
            'validity_type.required' => 'El tipo de validez es obligatorio.',
            'valid_from.required_if' => 'La fecha de inicio es obligatoria para rango de fechas.',
            'valid_until.required_if' => 'La fecha de fin es obligatoria para rango de fechas.',
            'valid_until.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'weekdays.required_if' => 'Debe seleccionar al menos un dia de la semana.',
            'weekdays.min' => 'Debe seleccionar al menos un dia de la semana.',
        ];
    }
}
