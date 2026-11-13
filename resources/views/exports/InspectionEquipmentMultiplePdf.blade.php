<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; page-break-inside: avoid; border-spacing: 0; page-break-inside: auto; margin-bottom: 10px; }
        th, td { border: 1px solid #000; padding: 3px 4px; text-align: center; vertical-align: middle; }
        .table th { font-weight: bold; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        .header-title { font-weight: bold; font-size: 9px; }
        .section-header { font-weight: bold; padding-left: 6px; }
        .logo-sena { height: 40px; }
        .signature-img { max-height: 40px; max-width: 125px; }
        .day-col { width: 22px; }
        #tabla-principal { border-bottom: none; margin-bottom: 0; }
        #tabla-firmas { border-top: none; margin-top: -1px; }
    </style>
</head>
<body>
    @foreach($inspecciones as $index => $inspeccion)
        @php
            $inspection = $inspeccion['inspection'];
            $equipment = $inspeccion['equipment'];
            $format = $inspeccion['format'];
            $answersMap = $inspeccion['answersMap'];
            $signatureBase64 = $inspeccion['signatureBase64'];
        @endphp
        <table id="tabla-principal">
            <tr>
                <td rowspan="3" colspan="2" style="width: 15%;">
                    <img class="logo-sena" src="{{ public_path('images/sena-logo-naranja.png') }}" alt="Logo SENA">
                </td>
                <th colspan="6" rowspan="3" class="header-title" style="width: 55%;">
                    INSPECCIÓN PREOPERACIONAL<br>{{ mb_strtoupper($equipment->name ?? '') }}
                </th>
                <th colspan="3" style="width: 15%;">Código</th>
                <td colspan="2" style="width: 15%;">{{ $format->code ?? '' }}</td>
            </tr>
            <tr>
                <th colspan="3">Versión</th>
                <td colspan="2">{{ $format->version ?? '01' }}</td>
            </tr>
            <tr>
                <th colspan="3">Vigente desde</th>
                <td colspan="2">{{ $format->valid_from ?? '' }}</td>
            </tr>
            <tr>
                <th colspan="2">Equipo</th>
                <td colspan="1">{{ $equipment->name ?? '' }}</td>
                <th colspan="2">Marca</th>
                <td colspan="3">{{ $equipment->brand ?? '' }}</td>
                <th colspan="3">Modelo</th>
                <td colspan="2">{{ $equipment->model ?? '' }}</td>
            </tr>
            <tr>
                <th colspan="2">Serie</th>
                <td colspan="1">{{ $equipment->serial_number ?? ''}}</td>
                <th colspan="2">Ubicación</th>
                <td colspan="3">{{ $equipment->ubicacion->name ?? '' }}</td>
                <th colspan="3">Cód. equipo</th>
                <td colspan="2">{{ $equipment->code ?? '' }}</td>
            </tr>
            <tr>
                <th colspan="2">Semana</th>  
                <td colspan="11">del {{ $inspection->week_start }} al {{ $inspection->week_end }}</td>
            </tr>
            @if(!empty($format->header_fields))
                @foreach(array_chunk($format->header_fields, 3) as $chunk)
                        @foreach($chunk as $field)
                        <tr>
                            <th colspan="2">{{ $field['label'] }}</th>
                            <td colspan="11">{{ $inspection->header_data[$field['key']] ?? '' }}</td>
                        </tr>
                        @endforeach
                @endforeach
            @endif

            @php $index_section = 0; @endphp
            @foreach($format->sections as $section)
                @php $index_section++; @endphp
                <tr>
                    <th rowspan="2">N.°</th>
                    <td rowspan="2" colspan="3" class="section-header">
                        {{ $index_section }}. {{ $section->title }}
                        @if($section->instructions)
                            <span style="font-weight: normal;">({{ $section->instructions }})</span>
                        @endif
                    </td>
                    <th colspan="7">DÍAS</th>
                    <th rowspan="2" colspan="2">OBSERVACIONES</th>
                </tr>
                <tr>
                    <th class="day-col">L</th>
                    <th class="day-col">M</th>
                    <th class="day-col">M</th>
                    <th class="day-col">J</th>
                    <th class="day-col">V</th>
                    <th class="day-col">S</th>
                    <th class="day-col">D</th>
                </tr>
                @foreach($section->questions as $index => $question)
                    <tr>
                        <td>{{ $index_section }}.{{ $index + 1 }}</td>
                        <td colspan="3">{{ $question->text }}</td>
                        @if($section->mode === 'weekly')
                            @php
                                $val = $answersMap[$question->id][0]['value'] ?? '';
                                $obs = $answersMap[$question->id][0]['observation'] ?? '';
                            @endphp
                            <td colspan="7">{{ $val }}</td>
                            <td colspan="2">{{ $obs }}</td>
                        @else
                            @php
                                $diasNombres = [1 => 'L', 2 => 'M', 3 => 'M', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D', 0 => 'D0'];
                                $obsList = [];
                            @endphp
                            @for($d = 1; $d <= 7; $d++)
                                @php
                                    $val = $answersMap[$question->id][$d]['value'] ?? '';
                                    $o = $answersMap[$question->id][$d]['observation'] ?? '';
                                    if ($o) { $obsList[] = "D{$diasNombres[$d]}: {$o}"; }
                                @endphp
                                <td>{{ $val }}</td>
                            @endfor
                            @php
                                $obs0 = $answersMap[$question->id][0]['observation'] ?? '';
                                if ($obs0) { $obsList[] = $obs0; }
                            @endphp
                            <td colspan="2">{{ implode(' | ', $obsList) }}</td>
                        @endif
                    </tr>
                @endforeach
            @endforeach

            @if($inspection->general_observations)
                <tr>
                    <td colspan="13" class="section-header">OBSERVACIONES</td>
                </tr>
                <tr>
                    <td colspan="13">
                        {{ $inspection->general_observations ?? ''}}
                    </td>
                </tr>
            @endif
        </table>

        <table id="tabla-firmas">
            <tr>
                <td colspan="3" class="section-header">FIRMAS</td>
            </tr>
            <tr>
                <th style="width: 33%;">Realiza la inspección</th>
                <th style="width: 33%;">Colaborador</th>
                <th style="width: 34%;">Revisó (Vo.Bo.)</th>
            </tr>
            <tr>
                @foreach(['inspector', 'collaborator', 'reviewer'] as $role)
                    @php $sig = $signaturesMap[$role] ?? null; @endphp
                    <td style="height: 70px; vertical-align: bottom;">
                        @if(!empty($sig['signature_base64']))
                            <img class="signature-img" src="{{ $sig['signature_base64'] }}" alt="Firma"><br>
                        @endif
                        <strong>Nombre</strong> {{ $sig['name'] ?? '' }}<br>
                        <strong>C.C.</strong> {{ $sig['id_number'] ?? '' }}
                    </td>
                @endforeach
            </tr>
        </table>
        @if(!$loop->last)
            <div style="page-break-after: always;"></div>        
        @endif
    @endforeach
</body>
</html>
