<?php

namespace App\Http\Requests\Order;

use App\Models\Driver;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class AssignDriverRequest extends FormRequest
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
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $this->validateDriverBelongsToRestaurant($validator);
            $this->validateOrderCanBeAssigned($validator);
        });
    }

    /**
     * Verificar que el motorista pertenece al mismo restaurante que la orden.
     */
    protected function validateDriverBelongsToRestaurant(\Illuminate\Validation\Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        /** @var Order $order */
        $order = $this->route('order');
        $driver = Driver::find($this->input('driver_id'));

        if ($driver && $order && $driver->restaurant_id !== $order->restaurant_id) {
            $validator->errors()->add(
                'driver_id',
                'El motorista debe pertenecer al mismo restaurante que la orden.'
            );
        }
    }

    /**
     * Verificar que la orden puede ser asignada a un motorista.
     */
    protected function validateOrderCanBeAssigned(\Illuminate\Validation\Validator $validator): void
    {
        /** @var Order $order */
        $order = $this->route('order');

        if ($order && ! $order->canBeAssignedToDriver()) {
            $validator->errors()->add(
                'order',
                'Esta orden no puede ser asignada a un motorista. Solo ordenes de delivery en estado "lista" pueden ser asignadas.'
            );
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
            'driver_id.required' => 'El motorista es obligatorio.',
            'driver_id.exists' => 'El motorista seleccionado no existe.',
        ];
    }
}
