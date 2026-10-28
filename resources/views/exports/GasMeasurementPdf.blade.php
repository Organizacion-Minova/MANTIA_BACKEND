<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #000; margin: 0; padding: 0; }
        .page-break { page-break-after: always; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; vertical-align: middle; }
        .text-left { text-align: left; }
        .header-title { font-size: 12px; font-weight: bold; text-align: center; margin-top: 0px; }
        .header-container { width: 100%; text-align: center; margin-bottom: 2px; }
        .logo-sena { max-height: 45px; display: block; margin-bottom: 2px; }
        .subrayado { text-decoration: underline; }
        .signatures-table { width: 100%; margin-top: 15px; border: none; }
        .signatures-table td { border: none; text-align: center; vertical-align: middle; width: 50%; padding: 10px; }
        .signature-wrapper { display: inline-block; vertical-align: middle; }
        .signature-img { max-height: 50px; max-width: 150px; object-fit: contain; vertical-align: middle; margin-left: 10px; }
        .signature-text { vertical-align: middle; font-size: 10px; }
    </style>
</head>
<body>
    @php
        $fecha = $medicion->measurement_date ? explode('-', $medicion->measurement_date) : ['', '', ''];
        $anio = $fecha[0] ?? '----';
        $mes = $fecha[1] ?? '--';
        $dia = $fecha[2] ?? '--';

        if ($medicion->measurement_time) {
            $horaCarbon = \Carbon\Carbon::createFromFormat('H:i:s', $medicion->measurement_time);
            $hora = $horaCarbon->format('h');
            $minutos = $horaCarbon->format('i');
            $periodo = $horaCarbon->format('A');
        } else {
            $hora = '--';
            $minutos = '--';
            $periodo = '--';
        }

        $n = 0;
    @endphp

    <div class="header-container">
        <img src="{{ public_path('images/sena-logo.png') }}" class="logo-sena" alt="SENA" />
        <div class="header-title">FORMATO MEDICIÓN DE GASES MINA DIDÁCTICA</div>
    </div>

    <table>
        <tr>
            <th colspan="1" rowspan="4">No</th>
            <th colspan="3" rowspan="2">Mina Didáctica Sena</th>
            <th colspan="3">Fecha de medición:</th>
            <td>{{ $dia }}</td>
            <td>{{ $mes }}</td>
            <td>{{ $anio }}</td>
        </tr>
        <tr>
            <th colspan="3">Hora Ingreso a Monitoreo:</th>
            <td>{{ $hora }}</td>
            <td>{{ $minutos }}</td>
            <td>{{ $periodo }}</td>
        </tr>
        <tr>
            <th rowspan="2" class="subrayado">Labor/Hora</th>
            <th colspan="8" class="subrayado">REPORTE REGISTRO Y MEDICIÓN DE GASES</th>
        </tr>
        <tr>
            <th class="subrayado">%<br>O2</th>
            <th class="subrayado">%<br>CH4</th>
            <th class="subrayado">%<br>CO2</th>
            <th class="subrayado">PPM<br>CO</th>
            <th class="subrayado">PPM<br>H2S</th>
            <th class="subrayado">PPM<br>NO<sub>2</sub></th>
            <th class="subrayado">Responsable de la<br>medición</th>
            <th class="subrayado">Observaciones</th>
        </tr>
        @foreach($medicion->detail as $d)
        <tr>
            <th rowspan="2">{{ $n=$n+1 }}</th>
            <th rowspan="1" class="text-left">{{ $d->location->name ?? '' }}</th>
            <td rowspan="2">{{ $d->oxygen }}</td>
            <td rowspan="2">{{ $d->methane }}</td>
            <td rowspan="2">{{ $d->carbon_dioxide }}</td>
            <td rowspan="2">{{ $d->carbon_monoxide }}</td>
            <td rowspan="2">{{ $d->hydrogen_sulfide }}</td>
            <td rowspan="2">{{ $d->nitrogen_dioxide }}</td>
            <td rowspan="2" class="text-left">{{ $d->responsible_name }}</td>
            <td rowspan="2" class="text-left">{{ $d->observation }}</td>
        </tr>
        <tr>
            <td rowspan="1"></td>
        </tr>
        @endforeach
        <tr>
            <th colspan="2" rowspan="3"><u>VLP</u> valores límites permisibles para gases en PPM y %</th>
            <th>CH4</th>
            <th>CO2</th>
            <th>CO</th>
            <th>H2S</th>
            <th>NO<sub>2</sub></th>
            <th colspan="3" rowspan="3">La concentración de oxígeno en el aire debe ser mínimo O2 = 19.5% y no mayor a O2 = 23.5% en volumen.</th>
        </tr>
        <tr>
            <th>%</th>
            <th>%</th>
            <th>PPM</th>
            <th>PPM</th>
            <th>PPM</th>
        </tr>
        <tr>
            <th>1</th>
            <th>0.5</th>
            <th>25</th>
            <th>1</th>
            <th>0.2</th>
        </tr>
    </table>
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-wrapper">
                    <span class="signature-text"><strong>FIRMA DE JEFE DE MINA:</strong></span>
                    @if($medicion->chief_signature)
                        <img src="{{ $medicion->chief_signature }}" class="signature-img" alt="Firma Jefe de Mina">
                    @endif
                </div>
            </td>
            <td>
                <div class="signature-wrapper">
                    <span class="signature-text"><strong>Vo. Bo. LIDER SST:</strong></span>
                    @if($medicion->sst_signature)
                        <img src="{{ $medicion->sst_signature }}" class="signature-img" alt="Firma Líder SST">
                    @endif
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
