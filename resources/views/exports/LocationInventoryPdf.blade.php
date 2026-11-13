<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; color: #000; margin: 0; padding: 0; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table th, .table td { border: 1px solid #000; padding: 6px; vertical-align: middle; text-align: center; }
        .table th { font-weight: bold; }
        .table td.izquierda { text-align: left; }
        .header-container { width: 100%; text-align: center; margin-bottom: 2px; }
        .logo-sena { max-height: 45px; display: block; margin: 0 auto 2px auto; }
        .estado-ok { background: #d4edda; }
        .estado-aviso { background: #fff3cd; }
        .estado-mal { background: #f8d7da; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .seccion { page-break-inside: auto; }
    </style>
</head>
<body>
    @php
        $u = $datos['ubicacion'];
        $r = $datos['resumen'];
        $claseEstado = fn ($estado) => match ($estado) {
            'Activo' => 'estado-ok',
            'Mantenimiento' => 'estado-aviso',
            'Inactivo' => 'estado-mal',
            default => '',
        };
    @endphp

    <div class="header-container">
        <img src="{{ public_path('images/sena-logo.png') }}" class="logo-sena" alt="SENA" />
    </div>

    {{-- Encabezado y resumen --}}
    <table class="table">
        <thead>
            <tr>
                <th colspan="4">INVENTARIO DE UBICACIÓN</th>
            </tr>
            <tr>
                <th>UBICACIÓN:</th>
                <td class="izquierda" colspan="3">{{ $u['nombre'] }}</td>
            </tr>
            <tr>
                <th>CATEGORÍA:</th>
                <td class="izquierda">{{ $u['categoria'] ?? 'Sin categoría' }}</td>
                <th>ESTADO:</th>
                <td class="{{ $claseEstado($u['estado']) }}">{{ $u['estado'] }}</td>
            </tr>
            <tr>
                <th>DESCRIPCIÓN:</th>
                <td class="izquierda" colspan="3">{{ $u['descripcion'] ?? '' }}</td>
            </tr>
            <tr>
                <th>GENERADO POR:</th>
                <td class="izquierda" colspan="3">{{ $generadoPor }}</td>
            </tr>
        </thead>
        <tbody>
            @if($r['maquinas'] !== null || $r['equipos'] !== null || $r['herramientas'] !== null || $r['mediciones_gases'] !== null)
            <tr>
                <th colspan="4">RESUMEN</th>
            </tr>
            @if($r['maquinas'] !== null)
            <tr><th>Máquinas</th><td>{{ $r['maquinas'] }}</td><th>Equipos</th><td>{{ $r['equipos'] ?? '—' }}</td></tr>
            @elseif($r['equipos'] !== null)
            <tr><th>Equipos</th><td colspan="3">{{ $r['equipos'] }}</td></tr>
            @endif
            @if($r['herramientas'] !== null || $r['mediciones_gases'] !== null)
            <tr><th>Herramientas</th><td>{{ $r['herramientas'] ?? '—' }}</td><th>Mediciones de gases</th><td>{{ $r['mediciones_gases'] ?? '—' }}</td></tr>
            @endif
            @if($datos['maquinas'] !== null || $datos['equipos'] !== null)
            <tr><th>Activos</th><td>{{ $r['activos'] }}</td><th>En mantenimiento</th><td>{{ $r['en_mantenimiento'] }}</td></tr>
            <tr><th>Inactivos</th><td colspan="3">{{ $r['inactivos'] }}</td></tr>
            @endif
            @if($r['stock_bajo'] !== null)
            <tr><th>Herramientas con stock bajo</th><td colspan="3">{{ $r['stock_bajo'] }}</td></tr>
            @endif
            @endif
        </tbody>
    </table>

    {{-- Máquinas --}}
    @if($datos['maquinas'] !== null)
    <table class="table seccion">
        <thead>
            <tr><th colspan="5">MÁQUINAS ({{ count($datos['maquinas']) }})</th></tr>
            <tr><th>Código</th><th>Nombre</th><th>Categoría</th><th>Responsable</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @forelse($datos['maquinas'] as $m)
            <tr>
                <td>{{ $m['codigo'] }}</td>
                <td class="izquierda">{{ $m['nombre'] }}</td>
                <td>{{ $m['categoria'] ?? 'Sin categoría' }}</td>
                <td>{{ $m['responsable'] }}</td>
                <td class="{{ $claseEstado($m['estado']) }}">{{ $m['estado'] }}</td>
            </tr>
            @empty
            <tr><td colspan="5">Esta ubicación no tiene máquinas.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

    {{-- Equipos --}}
    @if($datos['equipos'] !== null)
    <table class="table seccion">
        <thead>
            <tr><th colspan="4">EQUIPOS ({{ count($datos['equipos']) }})</th></tr>
            <tr><th>Código</th><th>Nombre</th><th>Responsable</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @forelse($datos['equipos'] as $e)
            <tr>
                <td>{{ $e['codigo'] }}</td>
                <td class="izquierda">{{ $e['nombre'] }}</td>
                <td>{{ $e['responsable'] }}</td>
                <td class="{{ $claseEstado($e['estado']) }}">{{ $e['estado'] }}</td>
            </tr>
            @empty
            <tr><td colspan="4">Esta ubicación no tiene equipos.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

    {{-- Herramientas --}}
    @if($datos['herramientas'] !== null)
    <table class="table seccion">
        <thead>
            <tr><th colspan="8">HERRAMIENTAS ({{ count($datos['herramientas']) }})</th></tr>
            <tr><th>Nombre</th><th>Categoría</th><th>Tipo</th><th>Stock total</th><th>Bueno</th><th>Regular</th><th>Malo</th><th>Stock</th></tr>
        </thead>
        <tbody>
            @forelse($datos['herramientas'] as $h)
            <tr>
                <td class="izquierda">{{ $h['nombre'] }}</td>
                <td>{{ $h['categoria'] ?? 'Sin categoría' }}</td>
                <td>{{ $h['consumible'] ? 'Consumible' : 'No consumible' }}</td>
                <td>{{ $h['stock_total'] }}</td>
                <td>{{ $h['bueno'] }}</td>
                <td>{{ $h['regular'] }}</td>
                <td>{{ $h['malo'] }}</td>
                <td class="{{ $h['stock_bajo'] ? 'estado-aviso' : 'estado-ok' }}">{{ $h['stock_bajo'] ? 'Stock bajo' : 'Normal' }}</td>
            </tr>
            @empty
            <tr><td colspan="8">Esta ubicación no tiene herramientas.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

    {{-- Mediciones de gases --}}
    @if($datos['gases'] !== null)
    <table class="table seccion">
        <thead>
            <tr><th colspan="4">ÚLTIMAS MEDICIONES DE GASES ({{ count($datos['gases']) }})</th></tr>
            <tr><th>Fecha</th><th>Hora</th><th>Responsable</th><th>Resultado</th></tr>
        </thead>
        <tbody>
            @forelse($datos['gases'] as $g)
            <tr>
                <td>{{ $g['fecha'] ? \Carbon\Carbon::parse($g['fecha'])->format('d/m/Y') : '' }}</td>
                <td>{{ $g['hora'] }}</td>
                <td>{{ $g['responsable'] ?: '' }}</td>
                <td class="{{ count($g['fuera_de_rango']) ? 'estado-mal' : '' }}">
                    {{ count($g['fuera_de_rango']) ? 'Fuera de rango: ' . implode(', ', $g['fuera_de_rango']) : 'Dentro de los límites' }}
                </td>
            </tr>
            @empty
            <tr><td colspan="4">Esta ubicación no tiene mediciones de gases.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif
</body>
</html>