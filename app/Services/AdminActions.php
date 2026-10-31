<?php

namespace App\Services;

use App\Http\Controllers\LocationController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Acciones de administración que se pueden pedir desde MANTIA IA.
 *
 * Flujo:
 *  1. La IA llama a resumen(): valida y arma el texto de la propuesta. NO cambia nada.
 *  2. El administrador pulsa "Confirmar" en el chat (AiActionController).
 *  3. Se llama a ejecutar(), que vuelve a validar porque los datos pudieron cambiar.
 *
 * Las acciones que ya tienen pantalla (ubicaciones) se ejecutan con el MISMO
 * controlador de esa pantalla: así las validaciones y las alertas son las mismas
 * y si alguien cambia el controlador, el asistente usa el cambio automáticamente.
 *
 * Para agregar una acción nueva: súmala a CAMPOS y a reglas(), resumen() y ejecutar().
 */
class AdminActions
{
    /** Tipo de acción => campos que recibe. */
    public const CAMPOS = [
        'crear_rol' => ['nombre'],
        'asignar_rol' => ['correo', 'rol'],
        'desactivar_usuario' => ['correo'],
        'activar_usuario' => ['correo'],
        'crear_ubicacion' => ['nombre', 'descripcion', 'categoria'],
    ];

    public static function tipos(): array
    {
        return array_keys(self::CAMPOS);
    }

    /** Deja solo los campos de esa acción (la IA a veces manda campos de más). */
    public function limpiar(string $tipo, array $datos): array
    {
        return array_intersect_key($datos, array_flip(self::CAMPOS[$tipo] ?? []));
    }

    /** Valida y devuelve el texto que verá el administrador antes de confirmar. */
    public function resumen(string $tipo, array $datos, User $admin): string
    {
        $this->validar($tipo, $datos, $admin);

        return match ($tipo) {
            'crear_rol' => "Crear el rol \"{$datos['nombre']}\"",
            'asignar_rol' => "Asignar el rol \"{$datos['rol']}\" a {$this->nombreDe($datos['correo'])}",
            'desactivar_usuario' => "Desactivar la cuenta de {$this->nombreDe($datos['correo'])}",
            'activar_usuario' => "Activar la cuenta de {$this->nombreDe($datos['correo'])}",
            'crear_ubicacion' => "Crear la ubicación \"{$datos['nombre']}\" en la categoría \"{$datos['categoria']}\"",
        };
    }

    /** Solo se llama cuando el administrador pulsa "Confirmar". Devuelve el mensaje para el chat. */
    public function ejecutar(string $tipo, array $datos, User $admin): string
    {
        $this->validar($tipo, $datos, $admin);

        switch ($tipo) {
            case 'crear_rol':
                Role::create(['name' => $datos['nombre']]);
                return "Rol \"{$datos['nombre']}\" creado.";

            case 'asignar_rol':
                $this->usuario($datos['correo'])->syncRoles([$datos['rol']]);
                return "Rol \"{$datos['rol']}\" asignado a {$this->nombreDe($datos['correo'])}.";

            case 'desactivar_usuario':
                $usuario = $this->usuario($datos['correo']);
                $usuario->update(['status' => 'inactive', 'is_active' => false]);
                // Cierra sus sesiones abiertas para que salga de inmediato (SESSION_DRIVER=database)
                DB::table('sessions')->where('user_id', $usuario->id)->delete();
                return "Cuenta de {$this->nombreDe($datos['correo'])} desactivada.";

            case 'activar_usuario':
                $this->usuario($datos['correo'])->update(['status' => 'approved', 'is_active' => true]);
                return "Cuenta de {$this->nombreDe($datos['correo'])} activada.";

            case 'crear_ubicacion':
                // La pantalla envía el id de la categoría; la IA la nombra
                $datos['categoria'] = DB::table('location_category')->where('name', $datos['categoria'])->value('id');
                return $this->mensajeDe(
                    app(LocationController::class)->store($this->peticion($datos)),
                    'Ubicación registrada correctamente.'
                );
        }

        throw ValidationException::withMessages(['tipo' => 'Esa acción no está permitida desde el asistente.']);
    }

    private function validar(string $tipo, array $datos, User $admin): void
    {
        Validator::make($datos, $this->reglas($tipo), ['rol.exists' => 'Ese rol no existe o está desactivado.'])->validate();

        if (!in_array($tipo, ['asignar_rol', 'desactivar_usuario', 'activar_usuario'], true)) {
            return;
        }

        $usuario = $this->usuario($datos['correo']);

        // Un administrador no puede cambiarse a sí mismo: así el sistema nunca se queda sin administradores
        if ($usuario->is($admin)) {
            throw ValidationException::withMessages(['correo' => 'No puedes modificar tu propia cuenta desde el asistente.']);
        }

        // Las solicitudes nuevas se aprueban o rechazan en Perfil, no desde aquí
        if ($tipo === 'desactivar_usuario' && $usuario->status !== 'approved') {
            throw ValidationException::withMessages(['correo' => 'Solo se pueden desactivar cuentas aprobadas.']);
        }

        if ($tipo === 'activar_usuario' && $usuario->status !== 'inactive') {
            throw ValidationException::withMessages(['correo' => 'Solo se pueden activar cuentas desactivadas. Las solicitudes nuevas se aprueban en Perfil.']);
        }
    }

    private function reglas(string $tipo): array
    {
        return match ($tipo) {
            'crear_rol' => ['nombre' => ['required', 'string', 'max:50', 'regex:/^[\pL ]+$/u', 'unique:roles,name']],
            'asignar_rol' => ['correo' => 'required|email|exists:users,email', 'rol' => ['required', 'string', Rule::exists('roles', 'name')->where('is_active', true)]],
            'desactivar_usuario', 'activar_usuario' => ['correo' => 'required|email|exists:users,email'],
            // Revisión previa para armar la propuesta; la validación completa la hace
            // el controlador de cada pantalla al confirmar
            'crear_ubicacion' => [
                'nombre' => 'required|string|max:100|unique:location,name',
                'descripcion' => 'required|string',
                'categoria' => 'required|string|exists:location_category,name',
            ],
            default => throw ValidationException::withMessages(['tipo' => 'Esa acción no está permitida desde el asistente.']),
        };
    }

    private function usuario(string $correo): User
    {
        return User::where('email', $correo)->firstOrFail();
    }

    private function nombreDe(string $correo): string
    {
        $usuario = $this->usuario($correo);
        return trim("{$usuario->name} {$usuario->last_name}") . " ({$correo})";
    }

    /** Arma una petición con los mismos campos que envía el formulario de la pantalla. */
    private function peticion(array $datos): Request
    {
        return Request::create('/', 'POST', $datos);
    }

    private function mensajeDe(JsonResponse $respuesta, string $porDefecto): string
    {
        return $respuesta->getData(true)['message'] ?? $porDefecto;
    }
}