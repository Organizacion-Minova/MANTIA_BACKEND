<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Spatie\Permission\Models\Role;

class RolResponsable implements ValidationRule
{
    public function __construct(private ?string $valorActual = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->valorActual !== null && $value === $this->valorActual) {
            return;
        }

        if (! is_string($value)) {
            $fail('Selecciona un rol válido como responsable.');

            return;
        }

        $existe = Role::where('name', $value)
            ->where('is_active', true)
            ->where('name', '!=', 'Visitante')
            ->exists();

        if (! $existe) {
            $fail('Selecciona un rol válido como responsable.');
        }
    }
}
