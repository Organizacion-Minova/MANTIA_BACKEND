<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; margin: 0; padding: 0; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .table th, .table td { border: 1px solid #000; padding: 8px; vertical-align: middle; text-align: center;}
        .table img { height: 55px; }
        .image-container { text-align: center; padding: 10px; }
        .image-container img { max-height: 180px; max-width: 100%; object-fit: contain; }
    </style>
</head>
<body>
    <table class="table">
        <tr>
            <td rowspan="3" colspan="2">
                <img src="{{ public_path('images/sena-logo-naranja.png') }}" alt="Logo SENA">
            </td>
            <td colspan="2">
                SISTEMA DE GESTIÓN DE SEGURIDAD Y SALUD EN EL TRABAJO
            </td>
            <td colspan="1">
                CÓDIGO:
            </td>
            <td colspan="1">
                {{ $equipment->code }}
            </td>
        </tr>
        <tr>
            <td colspan="2">
                PROGRAMA DE MANTENIMIENTO
            </td>
            <td colspan="1">
                VERSIÓN:
            </td>
            <td colspan="1">
                1.0
            </td>
        </tr>
        <tr>
            <td colspan="2">
                FICHA TÉCNICA {{ $equipment->name }}
            </td>
            <td colspan="1">
                FECHA:
            </td>
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
                <img src="{{ $equipment->image }}" style="max-height: 300px; max-width: 350px;" alt="Imagen Equipo">
            </td>
        </tr>
        <tr>
            <td>CÓDIGO:</td>
            <td>{{ $equipment->code ?? 'N/A' }}</td>
            <td>NOMBRE:</td>
            <td>{{ $equipment->name }}</td>
            <td>PLACA:</td>
            <td>{{ $equipment->plate }}</td>
        </tr>
        <tr>
            <td>MODELO:</td>
            <td>{{ $equipment->model }}</td>
            <td>NÚMERO DE SERIE:</td>
            <td>{{ $equipment->serial_number }}</td>
            <td>FECHA DE INICIO:</td>
            <td>{{ $equipment->start_up_date }}</td>
        </tr>
        <tr>
            <td>UBICACIÓN:</td>
            <td>{{ $equipment->ubicacion->name }}</td>
            <td>RESPONSABLE:</td>
            <td>{{ $equipment->responsible }}</td>
            <td>ESTADO:</td>
            <td>{{ $equipment->status === 'active' ? 'Activo' : 'Inactivo' }}</td>
        </tr>
        <tr>
            <td>CONSUMIBLE:</td>
            <td>{{ $equipment->fuel}}</td>
            <td>POTENCIA:</td>
            <td>{{ $equipment->power }}</td>
            <td>VOLTAJE:</td>
            <td>{{ $equipment->voltage }}</td>
        </tr>
        <tr>
            <td colspan="6">
                DESCRIPCIÓN:
            </td>
        </tr>
        <tr>
            <td colspan="6">
                {{ $equipment->description }}
            </td>
        </tr>
    </table>
</body>
</html>
