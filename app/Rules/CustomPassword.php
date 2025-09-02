<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regla de validación personalizada para contraseñas
 * Solo requiere un mínimo de 6 caracteres, sin restricciones adicionales
 */
class CustomPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) < 6) {
            $fail('La contraseña debe tener al menos 6 caracteres.');
        }
    }
}
