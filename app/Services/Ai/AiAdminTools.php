<?php

namespace App\Services\Ai;

use App\Models\AiPendingAction;
use App\Models\User;
use App\Services\AdminActions;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Herramientas de MANTIA IA que SOLO se le ofrecen a un Administrador (ver AiTools).
 *
 * - admin_users: consulta usuarios y roles (para saber el correo exacto).
 * - propose_admin_action: PROPONE una acción. No cambia nada: guarda la propuesta
 *   y el chat muestra los botones "Confirmar" y "Cancelar" (AiActionController).
 */
class AiAdminTools
{
    private const LIMITE_MAXIMO = 20;
    private const MINUTOS_PARA_CONFIRMAR = 10;

    /** Propuestas creadas en esta petición; AiController se las envía al frontend. */
    private array $propuestas = [];

    public function __construct(private AdminActions $acciones)
    {
    }

    public function definitions(): array
    {
        return [
            [
                'name' => 'admin_users',
                'description' => 'Solo administradores. Busca usuarios por nombre, apellido o correo y muestra su estado (pending, approved, rejected, inactive) y sus roles. También lista los roles que existen. Úsala para conocer el correo exacto antes de proponer una acción sobre un usuario.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'buscar' => ['type' => 'string', 'description' => 'Nombre, apellido o correo a buscar.'],
                    ],
                ],
            ],
            [
                'name' => 'propose_admin_action',
                'description' => 'Solo administradores. PREPARA una acción para que el administrador la confirme con un botón en el chat; NO la ejecuta. '
                    . 'Tipos y campos: crear_rol (nombre); asignar_rol (correo, rol); desactivar_usuario (correo); activar_usuario (correo); '
                    . 'crear_ubicacion (nombre, descripcion, categoria = nombre exacto de la categoría de ubicación, consúltalo con catalogs); ',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'tipo' => ['type' => 'string', 'enum' => AdminActions::tipos(), 'description' => 'Acción a proponer.'],
                        'nombre' => ['type' => 'string'],
                        'correo' => ['type' => 'string'],
                        'rol' => ['type' => 'string'],
                        'descripcion' => ['type' => 'string'],
                        'categoria' => ['type' => 'string'],
                    ],
                ],
            ],
        ];
    }

    /** El permiso ya lo revisó AiTools; aquí se ejecuta la herramienta. */
    public function execute(string $nombre, array $entrada, User $admin): array
    {
        return match ($nombre) {
            'admin_users' => $this->usuarios($entrada),
            'propose_admin_action' => $this->proponer($entrada, $admin),
            default => ['error' => "Herramienta desconocida: {$nombre}"],
        };
    }

    public function propuestas(): array
    {
        return $this->propuestas;
    }

    private function usuarios(array $in): array
    {
        $consulta = User::query()->with('roles:id,name')->orderBy('name')->limit(self::LIMITE_MAXIMO);

        $texto = trim((string) ($in['buscar'] ?? ''));
        if ($texto !== '') {
            $like = '%' . mb_strtolower($texto) . '%';
            $consulta->where(fn ($q) => $q
                ->whereRaw('LOWER(name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(email) LIKE ?', [$like]));
        }

        return [
            'roles_existentes' => Role::orderBy('name')->pluck('name')->all(),
            'usuarios' => $consulta->get(['id', 'name', 'last_name', 'email', 'status'])->map(fn ($u) => [
                'nombre' => trim("{$u->name} {$u->last_name}"),
                'correo' => $u->email,
                'estado' => $u->status,
                'roles' => $u->roles->pluck('name')->all(),
            ])->all(),
        ];
    }

    private function proponer(array $in, User $admin): array
    {
        $tipo = (string) ($in['tipo'] ?? '');
        $datos = $this->acciones->limpiar($tipo, $in);

        try {
            $resumen = $this->acciones->resumen($tipo, $datos, $admin);
        } catch (ValidationException $e) {
            // Se le devuelve a la IA para que se lo explique al administrador
            return ['error' => collect($e->errors())->flatten()->first()];
        }

        $accion = AiPendingAction::create([
            'user_id' => $admin->id,
            'type' => $tipo,
            'payload' => $datos,
            'summary' => mb_substr($resumen, 0, 255),
            'expires_at' => now()->addMinutes(self::MINUTOS_PARA_CONFIRMAR),
        ]);

        $this->propuestas[] = ['id' => $accion->id, 'resumen' => $accion->summary];

        return [
            'estado' => 'pendiente_de_confirmacion',
            'resumen' => $accion->summary,
            'nota' => 'La acción aún NO se ha hecho. Dile al administrador que la confirme con el botón "Confirmar" que aparece en el chat (tiene '
                . self::MINUTOS_PARA_CONFIRMAR . ' minutos).',
        ];
    }
}