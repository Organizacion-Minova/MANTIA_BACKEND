<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; page-break-inside: avoid; border-spacing: 0; margin-bottom: 10px; }
        th, td { border: 1px solid #000; padding: 3px 4px; text-align: center; vertical-align: middle; }
        th { font-weight: bold; }
        .logo-sena { height: 38px; }
        .signature-img { max-height: 40px; max-width: 120px; }
        .section-title { font-weight: bold; padding-left: 6px; }
        #tabla-encabezado { border-bottom: none; margin-bottom: 0; }
        #tabla-contenido { border-top: none; margin-top: -1px; border-bottom: none; margin-bottom: 0; }
        #tabla-observaciones { border-top: none; margin-top: -1px; border-bottom: none; margin-bottom: 0; }
        #tabla-responsable { border-top: none; margin-top: -1px; }
    </style>
</head>
<body>

    <table id="tabla-encabezado">
        <tr>
            <td rowspan="3" colspan="2" style="width: 15%;">
                <img class="logo-sena" src="{{ public_path('images/sena-logo-naranja.png') }}" alt="Logo SENA">
            </td>
            <th colspan="2" rowspan="3" style="width: 50%;">
                FORMATO PREOPERACIONAL DE INSPECCIÓN<br>{{ mb_strtoupper($machine->name ?? '') }}
            </th>
            <th style="width: 15%;">Código</th>
            <td style="width: 20%;">{{ $format->code ?? '' }}</td>
        </tr>
        <tr>
            <th>Versión</th>
            <td>{{ $format->version ?? '' }}</td>
        </tr>
        <tr>
            <th>Fecha</th>
            <td>{{ $format->valid_from ?? '' }}</td>
        </tr>
        <tr>
            <th>Semana</th>
            <td>del {{ $inspection->week_start ?? '' }} al {{ $inspection->week_end ?? '' }}</td>
            <th>Persona que inspecciona</th>
            <td>{{ $inspection->inspector ?? '' }}</td>
            <th>Nombre del equipo</th>
            <td>{{ $machine->name ?? '' }}</td>
        </tr>
        <tr>
            <th>Duración</th>
            <td>{{ $inspection->duration_minutes ? $inspection->duration_minutes . ' min' : '' }}</td>
            <th>Área</th>
            <td>{{ $inspection->area ?? '' }}</td>
            <th>Revisó</th>
            <td>{{ $inspection->reviewed_by ?? '' }} @if($inspection->review_date) ({{ $inspection->review_date }}) @endif</td>
        </tr>
    </table>

    @foreach($format->sections as $section)
        @php
            $letraMayuscula = chr(64 + $loop->iteration); 
            $tieneGrupos = $section->questions->contains('type', 'group');
        @endphp

        <table id="tabla-contenido"> 
            <tr>
                <th colspan="23" class="section-title">
                    {{ $letraMayuscula }}. {{ $section->title }}
                </th>
            </tr>
            <tr>
                <th style="width: 5%;" rowspan="2">N.°</th>
                <th style="width: 32%;" rowspan="2">Aspectos a verificar</th>
                <th style="width: 9%;" colspan="3">LU</th>
                <th style="width: 9%;" colspan="3">MA</th>
                <th style="width: 9%;" colspan="3">MI</th>
                <th style="width: 9%;" colspan="3">JU</th>
                <th style="width: 9%;" colspan="3">VI</th>
                <th style="width: 9%;" colspan="3">SA</th>
                <th style="width: 9%;" colspan="3">DO</th>
            </tr>
            <tr>
                @foreach(['LU', 'MA', 'MI', 'JU', 'VI', 'SA', 'DO'] as $dia)
                    <th style="width: 3%;">SI</th>
                    <th style="width: 3%;">NO</th>
                    <th style="width: 3%;">N/A</th>
                @endforeach
            </tr>
            @php $numero = 0;  
            $subNumero = 0; @endphp
            @foreach($section->questions as $index => $question)
                @php
                    $ancho = ($question->type === "group") ? 22 : 1;
                    $numero = ($question->type === "group") ? ($numero + 1) : ($numero);
                    $subNumero = ($question->type === "group") ? 0 : ($subNumero + 1);
                    $numeracion = !$tieneGrupos 
                        ? $loop->iteration 
                        : (($question->type === "group") ? $numero . '.' : $numero . '.' . $subNumero);
                @endphp
                <tr>
                    <td>{{ $numeracion }}</td>
                    <td colspan="{{ $ancho }}">{{ $question->text }}</td>
                    
                    @if($question->type !== "group")
                        @foreach(['LU', 'MA', 'MI', 'JU', 'VI', 'SA', 'DO'] as $indexDia => $dia)
                            @php
                                $numeroDia = $indexDia + 1; 
                                $valorDia = $answersMap[$question->id][$numeroDia]['value'] ?? '';
                                $valorDia = strtoupper(trim($valorDia));
                            @endphp
                            <td style="text-align: center;">{{ $valorDia === 'SI' ? 'X' : '' }}</td>
                            <td style="text-align: center;">{{ $valorDia === 'NO' ? 'X' : '' }}</td>
                            <td style="text-align: center;">{{ $valorDia === 'N/A' ? 'X' : '' }}</td>
                        @endforeach
                    @endif
                </tr>
            @endforeach
        </table>
    @endforeach

    <table id="tabla-observaciones">
        <tr>
            <th>OBSERVACIONES</th>
        </tr>
        <tr>
            <td>
                {{ $inspection->general_observations ?? '' }}
            </td>
        </tr>
    </table>

    <table id="tabla-responsable">
        <tr>
            <th>FIRMA RESPONSABLE</th>
            <th>NOMBRE</th>
        </tr>
        <tr>
            <td style="width: 50%; height: 55px; vertical-align: middle;">
                @if(!empty($signatureBase64))
                    <img class="signature-img" src="{{ $signatureBase64 }}" alt="Firma"><br>
                @endif
            </td>
            <td style="width: 50%; vertical-align: middle;">
                {{ $inspection->inspector }}
            </td>
        </tr>
    </table>

</body>
</html>
