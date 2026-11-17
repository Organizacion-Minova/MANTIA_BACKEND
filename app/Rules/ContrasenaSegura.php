<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Contraseña segura: 8 a 64 caracteres, con mayúscula, minúscula y número, sin espacios.
 * Las mismas reglas se revisan en el frontend (src/utils/validacionesUsuario.js).
 */
class ContrasenaSegura implements ValidationRule
{
    public function __construct(private string $nombre = 'La contraseña')
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail("{$this->nombre} no es válida.");

            return;
        }

        $largo = mb_strlen($value);

        if ($largo < 8) {
            $fail("{$this->nombre} debe tener mínimo 8 caracteres.");
        } elseif ($largo > 64) {
            $fail("{$this->nombre} no puede superar los 64 caracteres.");
        } elseif (preg_match('/\s/u', $value)) {
            $fail("{$this->nombre} no puede tener espacios.");
        } elseif (! preg_match('/\p{Lu}/u', $value)) {
            $fail("{$this->nombre} debe tener al menos una letra mayúscula.");
        } elseif (! preg_match('/\p{Ll}/u', $value)) {
            $fail("{$this->nombre} debe tener al menos una letra minúscula.");
        } elseif (! preg_match('/\d/', $value)) {
            $fail("{$this->nombre} debe tener al menos un número.");
        }
    }
}
