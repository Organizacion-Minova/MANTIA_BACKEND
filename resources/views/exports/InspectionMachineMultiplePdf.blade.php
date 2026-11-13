<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; page-break-inside: avoid; border-spacing: 0; page-break-inside: auto; margin-bottom: 10px; }
        th, td { border: 1px solid #000; padding: 3px 4px; text-align: center; vertical-align: middle; }
        th { font-weight: bold; }
        tr { page-break-inside: avoid; page-break-after: auto; }
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
    @foreach($inspecciones as $index => $inspeccion)
        @php
            $inspection = $inspeccion['inspection'];
            $machine = $inspeccion['machine'];
            $format = $inspeccion['format'];
            $answersMap = $inspeccion['answersMap'];
            $signatureBase64 = $inspeccion['signatureBase64'];
        @endphp
        <table id="tabla-encabezado">
            <tr>
                <td rowspan="3" colspan="2" style="width: 15%;">
                    <img class="logo-sena" src="{{ public_path('images/sena-logo-naranja.png') }}" alt="Logo SENA">
                </td>
                <th colspan="2" rowspan="3" style="width: 50%;">
                    FORMATO DE INSPECCIÓN<br>{{ mb_strtoupper($machine->name ?? '') }}
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
                <th>Fecha de inspección</th>
                <td>{{ $inspection->inspection_date ?? '' }}</td>
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
                    <th colspan="6" class="section-title">
                        {{ $letraMayuscula }}. {{ $section->title }}
                    </th>
                </tr>
                <tr>
                    <th style="width: 6%;">N.°</th>
                    <th style="width: 44%;">Aspectos a verificar</th>
                    <th style="width: 5%;">SI</th>
                    <th style="width: 5%;">NO</th>
                    <th style="width: 5%;">N/A</th>
                    <th style="width: 35%;">Observaciones</th>
                </tr>
                @php $numero = 0;  
                $subNumero = 0; @endphp
                @foreach($section->questions as $index => $question)
                    @php
                        $ancho = ($question->type === "group") ? 5 : 1;
                        $numero = ($question->type === "group") ? ($numero + 1) : ($numero);
                        $subNumero = ($question->type === "group") ? 0 : ($subNumero + 1);
                        $numeracion = !$tieneGrupos 
                            ? $loop->iteration 
                            : (($question->type === "group") ? $numero . '.' : $numero . '.' . $subNumero);
                        $valor = $answersMap[$question->id][0]['value'] ?? '';
                        $observaciones = $answersMap[$question->id][0]['observation'] ?? '';
                    @endphp
                    <tr>
                        <td>{{ $numeracion }}</td>
                        <td colspan="{{ $ancho }}">{{ $question->text }}</td>
                        
                        @if($question->type !== "group")
                            <td>{{ $valor === 'SI' ? 'X' : '' }}</td>
                            <td>{{ $valor === 'NO' ? 'X' : '' }}</td>
                            <td>{{ $valor === 'N/A' ? 'X' : '' }}</td>
                            <td>{{ $observaciones }}</td>
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

        @if(!$loop->last)
            <div style="page-break-after: always;"></div>        
        @endif
    @endforeach
</body>
</html>
