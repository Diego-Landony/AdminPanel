<?php

namespace App\Http\Requests\Api\V1\Cart;

use App\Models\Menu\Combo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AddCartItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id'),
                'required_without:combo_id',
            ],
            'combo_id' => [
                'nullable',
                'integer',
                Rule::exists('combos', 'id'),
                'required_without:product_id',
            ],
            'variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id'),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'selected_options' => ['nullable', 'array'],
            'selected_options.*.section_id' => ['required', 'integer'],
            'selected_options.*.option_id' => ['required', 'integer'],
            'combo_selections' => ['nullable', 'array'],
            'combo_selections.*.combo_item_id' => ['required_with:combo_selections', 'integer'],
            'combo_selections.*.selections' => ['required_with:combo_selections', 'array'],
            'combo_selections.*.selections.*.option_id' => ['required', 'integer'],
            'combo_selections.*.selections.*.selected_options' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateComboSelections($validator);
        });
    }

    /**
     * Validate combo selections match the combo structure.
     */
    protected function validateComboSelections(Validator $validator): void
    {
        $comboId = $this->input('combo_id');

        if (! $comboId) {
            return;
        }

        $combo = Combo::with(['items.options'])->find($comboId);

        if (! $combo) {
            return; // La regla exists ya maneja esto
        }

        $comboSelections = $this->input('combo_selections', []);
        $selectionsMap = collect($comboSelections)->keyBy('combo_item_id');

        foreach ($combo->items as $item) {
            if (! $item->is_choice_group) {
                // Items fijos no requieren selección
                continue;
            }

            $itemSelection = $selectionsMap->get($item->id);

            // Validar que exista selección para este grupo
            if (! $itemSelection) {
                $validator->errors()->add(
                    'combo_selections',
                    "Falta selección para '{$item->choice_label}'. Debe elegir {$item->quantity} opción(es)."
                );

                continue;
            }

            $selections = $itemSelection['selections'] ?? [];
            $expectedQuantity = $item->quantity ?? 1;

            // Validar cantidad de selecciones
            if (count($selections) !== $expectedQuantity) {
                $validator->errors()->add(
                    'combo_selections',
                    "'{$item->choice_label}' requiere exactamente {$expectedQuantity} selección(es), pero se enviaron ".count($selections).'.'
                );

                continue;
            }

            // Validar que las opciones seleccionadas existan en el combo
            $validOptionIds = $item->options->pluck('id')->toArray();

            foreach ($selections as $index => $selection) {
                $optionId = $selection['option_id'] ?? null;

                if (! $optionId || ! in_array($optionId, $validOptionIds)) {
                    $validator->errors()->add(
                        'combo_selections',
                        "La opción seleccionada para '{$item->choice_label}' (selección #".($index + 1).') no es válida.'
                    );
                }
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required_without' => 'Debe seleccionar un producto o un combo.',
            'product_id.exists' => 'El producto seleccionado no existe.',
            'combo_id.required_without' => 'Debe seleccionar un producto o un combo.',
            'combo_id.exists' => 'El combo seleccionado no existe.',
            'variant_id.exists' => 'La variante seleccionada no existe.',
            'quantity.required' => 'La cantidad es requerida.',
            'quantity.integer' => 'La cantidad debe ser un número entero.',
            'quantity.min' => 'La cantidad debe ser al menos 1.',
            'quantity.max' => 'La cantidad no puede exceder 10 unidades.',
            'selected_options.array' => 'Las opciones seleccionadas deben ser un arreglo.',
            'selected_options.*.section_id.required' => 'El ID de la sección es requerido.',
            'selected_options.*.section_id.integer' => 'El ID de la sección debe ser un número entero.',
            'selected_options.*.option_id.required' => 'El ID de la opción es requerido.',
            'selected_options.*.option_id.integer' => 'El ID de la opción debe ser un número entero.',
            'combo_selections.array' => 'Las selecciones del combo deben ser un arreglo.',
            'notes.string' => 'Las notas deben ser texto.',
            'notes.max' => 'Las notas no pueden exceder 500 caracteres.',
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
            'product_id' => 'producto',
            'combo_id' => 'combo',
            'variant_id' => 'variante',
            'quantity' => 'cantidad',
            'selected_options' => 'opciones seleccionadas',
            'combo_selections' => 'selecciones del combo',
            'notes' => 'notas',
        ];
    }
}
