<?php

namespace App\Support;

use App\Rules\ContrasenaSegura;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Reglas de validación de los datos de una persona (registro, perfil y creación de usuarios).
 * El frontend aplica las mismas reglas en src/utils/validacionesUsuario.js: si cambias algo aquí,
 * cámbialo también allá.
 */
class ReglasUsuario
{
    // Tipo de documento => [patrón, mensaje]
    public const DOCUMENTOS = [
        'CC' => ['/^[1-9][0-9]{5,9}$/', 'La cédula de ciudadanía debe tener solo números, entre 6 y 10 dígitos.'],
        'CE' => ['/^[0-9]{6,10}$/', 'La cédula de extranjería debe tener solo números, entre 6 y 10 dígitos.'],
        'TI' => ['/^[0-9]{10,11}$/', 'La tarjeta de identidad debe tener solo números, entre 10 y 11 dígitos.'],
        'PA' => ['/^[A-Z0-9]{6,12}$/', 'El pasaporte debe tener entre 6 y 12 caracteres, solo letras y números.'],
    ];

    // Nombre o apellido: solo letras (con tildes y ñ) y espacios simples
    public const PATRON_NOMBRE = '/^\p{L}+( \p{L}+)*$/u';

    /** Limpia los datos antes de validar: espacios, mayúsculas del pasaporte y correo en minúsculas. */
    public static function normalizar(Request $request): void
    {
        $limpio = [];

        foreach (['name', 'last_name'] as $campo) {
            if (is_string($request->input($campo))) {
                $limpio[$campo] = preg_replace('/\s+/u', ' ', trim($request->input($campo)));
            }
        }

        if (is_string($request->input('email'))) {
            $limpio['email'] = mb_strtolower(trim($request->input('email')));
        }

        if (is_string($request->input('document_type'))) {
            $limpio['document_type'] = strtoupper(trim($request->input('document_type')));
        }

        if (is_string($request->input('document_number'))) {
            $limpio['document_number'] = strtoupper(preg_replace('/\s+/', '', $request->input('document_number')));
        }

        $request->merge($limpio);
    }

    public static function nombre(): array
    {
        return ['required', 'string', 'min:2', 'max:50', 'regex:' . self::PATRON_NOMBRE];
    }

    public static function correo(?int $ignorarId = null): array
    {
        return [
            'required', 'string', 'max:100', 'email:rfc,strict',
            // Sin importar mayúsculas: Juan@correo.com y juan@correo.com son la misma cuenta
            function (string $atributo, mixed $valor, \Closure $fail) use ($ignorarId) {
                $existe = \App\Models\User::whereRaw('lower(email) = ?', [mb_strtolower((string) $valor)])
                    ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
                    ->exists();

                if ($existe) {
                    $fail('Ya existe una cuenta con este correo electrónico.');
                }
            },
        ];
    }

    public static function tipoDocumento(): array
    {
        return ['required', 'string', Rule::in(array_keys(self::DOCUMENTOS))];
    }

    public static function numeroDocumento(?string $tipo, ?int $ignorarId = null): array
    {
        $reglas = ['required', 'string', 'max:12', Rule::unique('users', 'document_number')->ignore($ignorarId)];

        if ($tipo && isset(self::DOCUMENTOS[$tipo])) {
            $reglas[] = 'regex:' . self::DOCUMENTOS[$tipo][0];
        }

        return $reglas;
    }

    public static function contrasena(string $nombre = 'La contraseña'): array
    {
        return ['required', 'string', new ContrasenaSegura($nombre)];
    }

    /** Mensajes en español para todos los campos de arriba. */
    public static function mensajes(?string $tipo = null): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.min' => 'El nombre debe tener al menos 2 letras.',
            'name.max' => 'El nombre no puede superar los 50 caracteres.',
            'name.regex' => 'El nombre solo puede tener letras y espacios.',
            'last_name.required' => 'El apellido es obligatorio.',
            'last_name.string' => 'El apellido debe ser texto.',
            'last_name.min' => 'El apellido debe tener al menos 2 letras.',
            'last_name.max' => 'El apellido no puede superar los 50 caracteres.',
            'last_name.regex' => 'El apellido solo puede tener letras y espacios.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.string' => 'El correo electrónico debe ser texto.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.max' => 'El correo electrónico no puede superar los 100 caracteres.',
            'email.unique' => 'Ya existe una cuenta con este correo electrónico.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser texto.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'document_type.required' => 'El tipo de documento es obligatorio.',
            'document_type.in' => 'El tipo de documento seleccionado no es válido.',
            'document_number.required' => 'El número de documento es obligatorio.',
            'document_number.string' => 'El número de documento debe ser texto.',
            'document_number.max' => 'El número de documento no puede superar los 12 caracteres.',
            'document_number.unique' => 'Ya existe una cuenta con este número de documento.',
            'document_number.regex' => self::DOCUMENTOS[$tipo][1] ?? 'El número de documento no es válido.',
        ];
    }
}
