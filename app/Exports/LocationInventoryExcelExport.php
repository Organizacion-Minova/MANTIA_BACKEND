<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Excel del inventario de una ubicación: una hoja de resumen y una hoja por cada
 * sección a la que el usuario tiene acceso (máquinas, equipos, herramientas y gases).
 */
class LocationInventoryExcelExport implements WithMultipleSheets
{
    public function __construct(protected array $datos, protected $usuario)
    {
    }

    public function sheets(): array
    {
        $u = $this->datos['ubicacion'];
        $r = $this->datos['resumen'];
        $generadoPor = trim($this->usuario->name . ' ' . ($this->usuario->last_name ?? ''))
            . ' - ' . Carbon::now(config('alertas.zona_horaria', 'America/Bogota'))->format('d/m/Y H:i');

        // Color de fondo según el texto de la celda
        $color = fn (string $valor): ?string => match (true) {
            in_array($valor, ['Activo', 'Normal'], true) => 'D4EDDA',
            in_array($valor, ['Mantenimiento', 'Stock bajo'], true) => 'FFF3CD',
            $valor === 'Inactivo' || str_starts_with($valor, 'Fuera de rango') => 'F8D7DA',
            default => null,
        };

        $hoja = fn (string $nombre, array $columnas, array $filas, string $vacio, $colorear = null) => new LocationInventorySheetExport(
            $nombre, $u['nombre'], $generadoPor, $columnas, $filas, $vacio, $colorear
        );

        // --- Resumen ---
        $filasResumen = [
            ['Ubicación', $u['nombre']],
            ['Categoría', $u['categoria'] ?? 'Sin categoría'],
            ['Estado', $u['estado']],
            ['Descripción', $u['descripcion'] ?? ''],
        ];
        foreach ([
            'maquinas' => 'Máquinas',
            'equipos' => 'Equipos',
            'herramientas' => 'Herramientas',
            'mediciones_gases' => 'Mediciones de gases',
        ] as $clave => $etiqueta) {
            if ($r[$clave] !== null) {
                $filasResumen[] = [$etiqueta, (string) $r[$clave]];
            }
        }
        if ($this->datos['maquinas'] !== null || $this->datos['equipos'] !== null) {
            $filasResumen[] = ['Activos', (string) $r['activos']];
            $filasResumen[] = ['En mantenimiento', (string) $r['en_mantenimiento']];
            $filasResumen[] = ['Inactivos', (string) $r['inactivos']];
        }
        if ($r['stock_bajo'] !== null) {
            $filasResumen[] = ['Herramientas con stock bajo', (string) $r['stock_bajo']];
        }

        $hojas = [$hoja('Resumen', ['Dato' => 32, 'Valor' => 70], $filasResumen, 'Sin datos', $color)];

        // --- Máquinas ---
        if ($this->datos['maquinas'] !== null) {
            $hojas[] = $hoja(
                'Máquinas',
                ['Código' => 16, 'Nombre' => 34, 'Categoría' => 24, 'Responsable' => 28, 'Estado' => 16],
                array_map(fn ($m) => [$m['codigo'], $m['nombre'], $m['categoria'] ?? 'Sin categoría', $m['responsable'], $m['estado']], $this->datos['maquinas']),
                'Esta ubicación no tiene máquinas.',
                $color
            );
        }

        // --- Equipos ---
        if ($this->datos['equipos'] !== null) {
            $hojas[] = $hoja(
                'Equipos',
                ['Código' => 16, 'Nombre' => 38, 'Responsable' => 28, 'Estado' => 16],
                array_map(fn ($e) => [$e['codigo'], $e['nombre'], $e['responsable'], $e['estado']], $this->datos['equipos']),
                'Esta ubicación no tiene equipos.',
                $color
            );
        }

        // --- Herramientas ---
        if ($this->datos['herramientas'] !== null) {
            $hojas[] = $hoja(
                'Herramientas',
                ['Nombre' => 34, 'Categoría' => 22, 'Tipo' => 16, 'Stock total' => 12, 'Bueno' => 10, 'Regular' => 10, 'Malo' => 10, 'Stock' => 14],
                array_map(fn ($h) => [
                    $h['nombre'],
                    $h['categoria'] ?? 'Sin categoría',
                    $h['consumible'] ? 'Consumible' : 'No consumible',
                    (string) $h['stock_total'],
                    (string) $h['bueno'],
                    (string) $h['regular'],
                    (string) $h['malo'],
                    $h['stock_bajo'] ? 'Stock bajo' : 'Normal',
                ], $this->datos['herramientas']),
                'Esta ubicación no tiene herramientas.',
                $color
            );
        }

        // --- Mediciones de gases ---
        if ($this->datos['gases'] !== null) {
            $hojas[] = $hoja(
                'Mediciones de gases',
                ['Fecha' => 14, 'Hora' => 10, 'Responsable' => 28, 'Resultado' => 46],
                array_map(fn ($g) => [
                    $g['fecha'] ? Carbon::parse($g['fecha'])->format('d/m/Y') : '',
                    $g['hora'] ?? '',
                    $g['responsable'] ?: '',
                    $g['fuera_de_rango'] ? 'Fuera de rango: ' . implode(', ', $g['fuera_de_rango']) : 'Dentro de los límites',
                ], $this->datos['gases']),
                'Esta ubicación no tiene mediciones de gases.',
                $color
            );
        }

        return $hojas;
    }
}