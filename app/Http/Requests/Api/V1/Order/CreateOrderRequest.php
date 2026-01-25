<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
{
    /**
     * Metadata adicional para errores de validación (ej: hora mínima sugerida)
     */
    protected array $validationMeta = [];

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
            'restaurant_id' => [
                'required',
                'integer',
                Rule::exists('restaurants', 'id'),
            ],
            'service_type' => [
                'required',
                'string',
                Rule::in(['pickup', 'delivery']),
            ],
            'delivery_address_id' => [
                Rule::requiredIf(fn () => $this->service_type === 'delivery'),
                'nullable',
                'integer',
                Rule::exists('customer_addresses', 'id')->where('customer_id', auth()->id()),
            ],
            'scheduled_pickup_time' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if ($this->service_type !== 'pickup' || ! $value) {
                        return;
                    }

                    $restaurant = \App\Models\Restaurant::find($this->restaurant_id);
                    $estimatedMinutes = $restaurant?->estimated_pickup_time ?? 30;
                    $scheduledTime = \Carbon\Carbon::parse($value);

                    // Validar que sea para hoy
                    if (! $scheduledTime->isToday()) {
                        $fail('Solo puedes programar pedidos para el día de hoy.');

                        return;
                    }

                    // Validar tiempo mínimo de preparación
                    // Buffer de 30 segundos para evitar race condition entre validación y procesamiento
                    $minimumTime = now()->addMinutes($estimatedMinutes)->subSeconds(30);
                    if ($scheduledTime->lt($minimumTime)) {
                        // Agregar 2 minutos de buffer para que el usuario tenga tiempo de aceptar
                        $suggestedTime = now()->addMinutes($estimatedMinutes + 2);
                        $this->validationMeta['suggested_pickup_time'] = $suggestedTime->format('Y-m-d H:i:s');
                        $this->validationMeta['suggested_pickup_time_formatted'] = $suggestedTime->format('H:i');
                        $this->validationMeta['time_expired'] = true;
                        $fail("La hora de recogida ya no está disponible. Hora mínima sugerida: {$suggestedTime->format('H:i')}.");

                        return;
                    }

                    // Validar que esté dentro del horario del restaurante
                    $closingTime = $restaurant?->getClosingTimeToday();
                    if ($closingTime) {
                        $closingCarbon = \Carbon\Carbon::createFromFormat('H:i', $closingTime)->setDateFrom(now());
                        if ($scheduledTime->gt($closingCarbon)) {
                            $fail("La hora de recogida debe ser antes del cierre ({$closingTime}).");
                        }
                    }
                },
            ],
            'scheduled_delivery_time' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if ($this->service_type !== 'delivery' || ! $value) {
                        return;
                    }

                    $restaurant = \App\Models\Restaurant::find($this->restaurant_id);
                    $estimatedMinutes = $restaurant?->estimated_delivery_time ?? 45;
                    $scheduledTime = \Carbon\Carbon::parse($value);

                    // Validar que sea para hoy
                    if (! $scheduledTime->isToday()) {
                        $fail('Solo puedes programar pedidos para el día de hoy.');

                        return;
                    }

                    // Validar tiempo mínimo (preparación + entrega)
                    // Buffer de 30 segundos para evitar race condition entre validación y procesamiento
                    $minimumTime = now()->addMinutes($estimatedMinutes)->subSeconds(30);
                    if ($scheduledTime->lt($minimumTime)) {
                        // Agregar 2 minutos de buffer para que el usuario tenga tiempo de aceptar
                        $suggestedTime = now()->addMinutes($estimatedMinutes + 2);
                        $this->validationMeta['suggested_delivery_time'] = $suggestedTime->format('Y-m-d H:i:s');
                        $this->validationMeta['suggested_delivery_time_formatted'] = $suggestedTime->format('H:i');
                        $this->validationMeta['time_expired'] = true;
                        $fail("La hora de entrega ya no está disponible. Hora mínima sugerida: {$suggestedTime->format('H:i')}.");

                        return;
                    }

                    // Para delivery, validamos que no sea demasiado tarde
                    $closingTime = $restaurant?->getClosingTimeToday();
                    if ($closingTime) {
                        $maxDeliveryTime = \Carbon\Carbon::createFromFormat('H:i', $closingTime)
                            ->setDateFrom(now())
                            ->addMinutes($estimatedMinutes);
                        if ($scheduledTime->gt($maxDeliveryTime)) {
                            $fail("La hora de entrega no puede ser después de las {$maxDeliveryTime->format('H:i')}.");
                        }
                    }
                },
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in(['cash', 'card']),
            ],
            'nit_id' => [
                'nullable',
                'integer',
                Rule::exists('customer_nits', 'id'),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
            'points_to_redeem' => [
                'nullable',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) {
                    if (! $value || $value <= 0) {
                        return;
                    }

                    $customer = auth()->user();
                    if (! $customer) {
                        $fail('Usuario no autenticado.');

                        return;
                    }

                    // Verificar que el cliente tenga suficientes puntos
                    if ($value > $customer->points) {
                        $fail("No tienes suficientes puntos. Disponibles: {$customer->points}");

                        return;
                    }

                    // Verificar mínimo de puntos para redimir (configurable, por defecto 100)
                    $minPoints = config('loyalty.min_points_to_redeem', 100);
                    if ($value < $minPoints) {
                        $fail("Debes redimir al menos {$minPoints} puntos.");

                        return;
                    }
                },
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
            'restaurant_id.required' => 'El restaurante es requerido.',
            'restaurant_id.exists' => 'El restaurante seleccionado no existe.',
            'service_type.required' => 'El tipo de servicio es requerido.',
            'service_type.in' => 'El tipo de servicio debe ser pickup o delivery.',
            'delivery_address_id.required' => 'La dirección de entrega es requerida para pedidos delivery.',
            'delivery_address_id.exists' => 'La dirección seleccionada no existe o no te pertenece.',
            'scheduled_pickup_time.date' => 'La hora de recogida debe ser una fecha válida.',
            'scheduled_delivery_time.date' => 'La hora de entrega debe ser una fecha válida.',
            'payment_method.required' => 'El método de pago es requerido.',
            'payment_method.in' => 'El método de pago debe ser cash o card.',
            'nit_id.exists' => 'El NIT seleccionado no existe.',
            'notes.string' => 'Las notas deben ser texto.',
            'notes.max' => 'Las notas no pueden exceder 500 caracteres.',
            'points_to_redeem.integer' => 'Los puntos a redimir deben ser un número entero.',
            'points_to_redeem.min' => 'Los puntos a redimir no pueden ser negativos.',
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
            'restaurant_id' => 'restaurante',
            'service_type' => 'tipo de servicio',
            'delivery_address_id' => 'dirección de entrega',
            'scheduled_pickup_time' => 'hora de recogida',
            'scheduled_delivery_time' => 'hora de entrega',
            'payment_method' => 'método de pago',
            'nit_id' => 'NIT',
            'notes' => 'notas',
        ];
    }

    /**
     * Handle a failed validation attempt.
     * Incluye metadata adicional cuando hay errores de tiempo expirado.
     */
    protected function failedValidation(Validator $validator): void
    {
        $response = [
            'message' => 'Los datos proporcionados no son válidos.',
            'errors' => $validator->errors()->toArray(),
        ];

        // Agregar metadata si hay información de tiempo sugerido
        if (! empty($this->validationMeta)) {
            $response['meta'] = $this->validationMeta;
        }

        throw new HttpResponseException(
            response()->json($response, 422)
        );
    }
}
