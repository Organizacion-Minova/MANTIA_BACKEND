<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AlertaExportService
{
    public const CATEGORIAS = [
        'login' => 'Inicio de sesión',
        'logout' => 'Cierre de sesión',
        'maquina' => 'Máquina',
        'equipo' => 'Equipo',
        'herramienta' => 'Herramienta',
        'ubicacion' => 'Ubicación',
        'categoria' => 'Categoría',
        'inspeccion' => 'Inspección',
        'gas' => 'Medición de gas',
        'uso_diario' => 'Uso diario',
    ];

    public const NIVELES = [
        'danger' => 'Grave',
        'warning' => 'Advertencia',
        'info' => 'Informativa',
        'success' => 'Éxito',
    ];

    private const ESTADOS = [
        'active' => 'Activo',
        'inactive' => 'Inactivo',
        'maintenance' => 'En mantenimiento',
        'approved' => 'Aprobado',
        'pending' => 'Pendiente',
        'rejected' => 'Rechazado',
    ];

    public function obtenerDatosRango(User $usuario, string $fechaInicio, string $fechaFin, ?string $nivel = null): Collection
    {
        $zona = config('alertas.zona_horaria', 'America/Bogota');

        $desde = Carbon::parse($fechaInicio, $zona)->startOfDay()->utc();
        $hasta = Carbon::parse($fechaFin, $zona)->endOfDay()->utc();

        return $usuario->notifications()
            ->whereBetween('created_at', [$desde, $hasta])
            ->get(['id', 'data', 'read_at', 'created_at'])
            ->sortBy(fn ($notificacion) => Carbon::parse(
                $notificacion->getRawOriginal('created_at'),
                'UTC'
            )->timestamp)
            ->map(fn ($notificacion) => $this->normalizar($notificacion, $zona))
            ->when($nivel, fn (Collection $alertas) => $alertas->where('nivel_clave', $nivel))
            ->values();
    }

    private function normalizar($notificacion, string $zona): array
    {
        $datos = (array) ($notificacion->data ?? []);
        $categoria = (string) ($datos['tipo'] ?? '');
        $nivel = (string) ($datos['nivel'] ?? 'info');
        $momento = Carbon::parse($notificacion->getRawOriginal('created_at'), 'UTC')
            ->setTimezone($zona);

        return [
            'fecha' => $momento->format('d/m/Y'),
            'hora' => $momento->format('H:i'),
            'categoria' => self::CATEGORIAS[$categoria] ?? ($categoria !== '' ? $categoria : 'General'),
            'nivel' => self::NIVELES[$nivel] ?? ucfirst($nivel),
            'nivel_clave' => $nivel,
            'mensaje' => $this->traducirMensaje((string) ($datos['mensaje'] ?? '')),
            'estado' => $notificacion->read_at ? 'Leída' : 'Sin leer',
        ];
    }

    private function traducirMensaje(string $mensaje): string
    {
        return preg_replace_callback(
            '/(cambió a estado: )(\w+)/u',
            fn ($m) => $m[1] . (self::ESTADOS[$m[2]] ?? $m[2]),
            $mensaje
        ) ?? $mensaje;
    }
}