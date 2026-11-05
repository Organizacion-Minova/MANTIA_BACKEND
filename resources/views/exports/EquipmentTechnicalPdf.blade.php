<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; margin: 0; padding: 0; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table th, .table td { border: 1px solid #000; padding: 8px; vertical-align: middle; text-align: center;}
        .table th { font-weight: bold; }
        .logo-sena { height: 55px; }
        .image-container { text-align: center; padding: 10px; }
        .image-container img { max-height: 180px; max-width: 100%; object-fit: contain; }
    </style>
</head>
<body>
    <table class="table">
        <tr>
            <td rowspan="3" colspan="2">
                <img class="logo-sena" src="{{ public_path('images/sena-logo-naranja.png') }}" alt="Logo SENA">
            </td>
            <th colspan="2">
                SISTEMA DE GESTIÓN DE SEGURIDAD Y SALUD EN EL TRABAJO
            </th>
            <th colspan="1">
                CÓDIGO:
            </th>
            <td colspan="1">
                {{ $equipment->code }}
            </td>
        </tr>
        <tr>
            <th colspan="2">
                PROGRAMA DE MANTENIMIENTO
            </th>
            <th colspan="1">
                VERSIÓN:
            </th>
            <td colspan="1">
                1.0
            </td>
        </tr>
        <tr>
            <th colspan="2">
                FICHA TÉCNICA {{ mb_strtoupper($equipment->name) }}
            </th>
            <th colspan="1">
                FECHA:
            </th>
            <td colspan="1">
                {{ $equipment->start_up_date }}
            </td>
        </tr>
        <tr>
            <td colspan="6">
                IMAGEN:
            </td>
        </tr>
        <tr>
            <td colspan="6" class="image-container">
                @if($equipment->image && file_exists(public_path('storage/' . $equipment->image)))
                    <img src="{{ public_path('storage/' . $equipment->image) }}" alt="Imagen Equipo">
                @endif
            </td>
        </tr>
        <tr>
            <th>CÓDIGO:</th>
            <td>{{ $equipment->code ?? 'N/A' }}</td>
            <th>NOMBRE:</th>
            <td>{{ $equipment->name }}</td>
            <th>PLACA:</th>
            <td>{{ $equipment->plate }}</td>
        </tr>
        <tr>
            <th>MODELO:</th>
            <td>{{ $equipment->model }}</td>
            <th>NÚMERO DE SERIE:</th>
            <td>{{ $equipment->serial_number }}</td>
            <th>FECHA DE INICIO:</th>
            <td>{{ $equipment->start_up_date }}</td>
        </tr>
        <tr>
            <th>UBICACIÓN:</th>
            <td>{{ $equipment->ubicacion->name }}</td>
            <th>RESPONSABLE:</th>
            <td>{{ $equipment->responsible }}</td>
            <th>ESTADO:</th>
            <td>{{ $equipment->status === 'active' ? 'Activo' : 'Inactivo' }}</td>
        </tr>
        <tr>
            <th>CONSUMIBLE:</th>
            <td>{{ $equipment->fuel}}</td>
            <th>POTENCIA:</th>
            <td>{{ $equipment->power }}</td>
            <th>VOLTAJE:</th>
            <td>{{ $equipment->voltage }}</td>
        </tr>
        <tr>
            <th colspan="6">
                DESCRIPCIÓN:
            </th>
        </tr>
        <tr>
            <td colspan="6">
                {{ $equipment->description }}
            </td>
        </tr>
    </table>
</body>
</html>
