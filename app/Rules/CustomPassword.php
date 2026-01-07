<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regla de validación personalizada para contraseñas de clientes
 * Requisitos:
 * - Mínimo 8 caracteres
 * - Al menos 1 letra
 * - Al menos 1 número
 * - Al menos 1 símbolo especial
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
        if (strlen($value) < 8) {
            $fail('La contraseña debe tener al menos 8 caracteres.');

            return;
        }

        if (! preg_match('/[a-zA-Z]/', $value)) {
            $fail('La contraseña debe contener al menos una letra.');

            return;
        }

        if (! preg_match('/[0-9]/', $value)) {
            $fail('La contraseña debe contener al menos un número.');

            return;
        }

        if (! preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?~`]/', $value)) {
            $fail('La contraseña debe contener al menos un símbolo especial (!@#$%^&*...).');

            return;
        }
    }
}
