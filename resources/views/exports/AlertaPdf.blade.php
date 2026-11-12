<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        h1 { text-align: center; font-size: 15px; margin-bottom: 4px; }
        .meta { width: 100%; margin-bottom: 12px; }
        .meta td { padding: 3px 6px; }
        table.alertas { width: 100%; border-collapse: collapse; }
        .alertas th, .alertas td { border: 1px solid #444; padding: 5px; vertical-align: top; }
        .alertas th { background: #e8eef5; text-align: center; }
        .centro { text-align: center; }
        .danger { background: #f8d7da; }
        .warning { background: #fff3cd; }
        .success { background: #d4edda; }
        .info { background: #d5f3ee; }
    </style>
</head>
<body>
    <h1>REPORTE DE ALERTAS DEL SISTEMA MANTIA</h1>

    <table class="meta">
        <tr>
            <td><strong>Usuario:</strong> {{ trim($usuario->name . ' ' . ($usuario->last_name ?? '')) }}</td>
            <td><strong>Periodo:</strong> {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</td>
            <td><strong>Nivel:</strong> {{ $nivel ? (\App\Services\AlertaExportService::NIVELES[$nivel] ?? $nivel) : 'Todos' }}</td>
        </tr>
    </table>

    <table class="alertas">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Categoría</th>
                <th>Nivel</th>
                <th>Mensaje</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($alertas as $alerta)
                <tr>
                    <td class="centro">{{ $alerta['fecha'] }}</td>
                    <td class="centro">{{ $alerta['hora'] }}</td>
                    <td>{{ $alerta['categoria'] }}</td>
                    <td class="centro {{ $alerta['nivel_clave'] }}">{{ $alerta['nivel'] }}</td>
                    <td>{{ $alerta['mensaje'] }}</td>
                    <td class="centro">{{ $alerta['estado'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="centro">No hay alertas en el periodo seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
