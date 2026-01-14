<?php

namespace App\Http\Requests\Api\V1\Order;

use App\Enums\OrderCancellationReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelOrderRequest extends FormRequest
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
            'reason_code' => [
                'required',
                'string',
                Rule::enum(OrderCancellationReason::class),
            ],
            'reason_detail' => [
                'nullable',
                'string',
                'max:500',
                'required_if:reason_code,other',
            ],
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
            'reason_code.required' => 'El motivo de cancelación es requerido.',
            'reason_code.enum' => 'El motivo de cancelación no es válido.',
            'reason_detail.required_if' => 'Por favor especifica el motivo de cancelación.',
            'reason_detail.max' => 'El detalle del motivo no puede exceder 500 caracteres.',
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
            'reason_code' => 'motivo de cancelación',
            'reason_detail' => 'detalle del motivo',
        ];
    }

    /**
     * Get the cancellation reason text.
     */
    public function getCancellationReason(): string
    {
        $reasonCode = $this->validated()['reason_code'];
        $reasonEnum = OrderCancellationReason::from($reasonCode);

        if ($reasonEnum === OrderCancellationReason::Other) {
            return $this->validated()['reason_detail'];
        }

        return $reasonEnum->label();
    }
}
