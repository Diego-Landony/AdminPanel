<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu\Category;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para crear un producto
 *
 * Los productos tienen:
 * - category_id: Categoría a la que pertenece
 * - has_variants: Flag para indicar si usa variantes
 * - Precios directos (si has_variants = false)
 * - Variantes con precios (si has_variants = true)
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    $category = Category::find($value);
                    if (! $category) {
                        return;
                    }

                    if ($category->uses_variants && empty($this->input('variants'))) {
                        $fail('Esta categoría requiere que definas al menos una variante para el producto.');
                    }

                    if (! $category->uses_variants && ! empty($this->input('variants'))) {
                        $fail('Esta categoría no permite variantes. Define un precio único para el producto.');
                    }
                },
            ],
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'image' => 'nullable|file|max:5120|mimetypes:image/jpeg,image/png,image/webp,image/gif,image/bmp,image/svg+xml,image/avif',

            // Configuración
            'is_active' => 'boolean',
            'has_variants' => 'boolean',

            // Campos de canje por puntos
            'is_redeemable' => 'boolean',
            'points_cost' => 'nullable|integer|min:1',

            // Precios del producto (requeridos si has_variants = false)
            'precio_pickup_capital' => 'required_if:has_variants,false|nullable|numeric|min:0',
            'precio_domicilio_capital' => 'required_if:has_variants,false|nullable|numeric|min:0',
            'precio_pickup_interior' => 'required_if:has_variants,false|nullable|numeric|min:0',
            'precio_domicilio_interior' => 'required_if:has_variants,false|nullable|numeric|min:0',

            // Redención por puntos (solo para productos SIN variantes)
            'is_redeemable' => 'boolean',
            'points_cost' => 'nullable|integer|min:1',

            // Variantes (requeridas si has_variants = true)
            'variants' => [
                'required_if:has_variants,true',
                'array',
                function ($attribute, $value, $fail) {
                    if ($this->input('has_variants') && is_array($value) && count($value) === 0) {
                        $fail('Debes activar al menos una variante cuando la categoría usa variantes.');
                    }

                    $category = Category::find($this->input('category_id'));
                    if (! $category || ! $category->uses_variants) {
                        return;
                    }

                    $categoryVariants = $category->variant_definitions ?? [];
                    foreach ($value as $variant) {
                        if (! in_array($variant['name'] ?? '', $categoryVariants)) {
                            $fail("La variante '{$variant['name']}' no existe en las variantes definidas de la categoría.");

                            return;
                        }
                    }
                },
            ],
            'variants.*.name' => 'required_if:has_variants,true|string|max:150',
            'variants.*.precio_pickup_capital' => 'required_if:has_variants,true|numeric|min:0',
            'variants.*.precio_domicilio_capital' => 'required_if:has_variants,true|numeric|min:0',
            'variants.*.precio_pickup_interior' => 'required_if:has_variants,true|numeric|min:0',
            'variants.*.precio_domicilio_interior' => 'required_if:has_variants,true|numeric|min:0',
            'variants.*.is_redeemable' => 'boolean',
            'variants.*.points_cost' => 'nullable|integer|min:1',

            // Secciones de personalización
            'sections' => 'nullable|array',
            'sections.*' => 'exists:sections,id',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Debes seleccionar una categoría.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'name.required' => 'El nombre del producto es obligatorio.',
            'name.max' => 'El nombre no puede exceder 150 caracteres.',

            'precio_pickup_capital.required_if' => 'El precio pickup capital es obligatorio cuando no se usan variantes.',
            'precio_domicilio_capital.required_if' => 'El precio domicilio capital es obligatorio cuando no se usan variantes.',
            'precio_pickup_interior.required_if' => 'El precio pickup interior es obligatorio cuando no se usan variantes.',
            'precio_domicilio_interior.required_if' => 'El precio domicilio interior es obligatorio cuando no se usan variantes.',

            'variants.required_if' => 'Debes agregar al menos una variante cuando usas variantes.',
            'variants.min' => 'Debes agregar al menos una variante.',
            'variants.*.name.required' => 'El nombre de la variante es obligatorio.',
            'variants.*.precio_pickup_capital.required' => 'El precio pickup capital de la variante es obligatorio.',
            'variants.*.precio_domicilio_capital.required' => 'El precio domicilio capital de la variante es obligatorio.',
            'variants.*.precio_pickup_interior.required' => 'El precio pickup interior de la variante es obligatorio.',
            'variants.*.precio_domicilio_interior.required' => 'El precio domicilio interior de la variante es obligatorio.',

            'sections.*.exists' => 'Una de las secciones seleccionadas no existe.',
        ];
    }
}
