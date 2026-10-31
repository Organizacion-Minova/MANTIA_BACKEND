<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; margin: 0; padding: 0; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table th, .table td { border: 1px solid #000; padding: 8px; vertical-align: middle; text-align: center;}
        .header-container { width: 100%; text-align: center; margin-bottom: 2px; }
        .logo-sena { max-height: 45px; display: block; margin-bottom: 2px; }
    </style>
</head>
<body>
    <div class="header-container">
        <img src="{{ public_path('images/sena-logo.png') }}" class="logo-sena" alt="SENA" />
    </div>
    <table class="table">
        <tr>
            <td colspan="6">
                REGISTRO DIARIO DE USO DE MAQUINARIA Y EQUIPOS MINA DIDÁCTICA
            </td>
        </tr>
        <tr>
            <td colspan="2">NOMBRE MÁQUINA:</td>
            <td colspan="4">{{ $machine->name }}</td>
        </tr>
        <tr>
            <td>Fecha</td>
            <td>Verificación de estado de<br>funcionamiento de la máquina</td>
            <td>Inicio de operación</td>
            <td>Fin de operación</td>
            <td>Responsable a cargo</td>
            <td>Observaciones</td>
        </tr>

        @forelse($records as $r)
        <tr>
            <td>{{ $r->date }}</td>
            <td>{{ $r->operating_status }}</td>
            <td>{{ $r->operation_start }}</td>
            <td>{{ $r->operation_end }}</td>
            <td>{{ $r->responsible }}</td>
            <td>{{ $r->observations ?? 'Ninguna' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6">No hay registros de uso diario para esta máquina.</td>
        </tr>
        @endforelse
    </table>
</body>
</html>