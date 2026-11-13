<?php

return [
    'gas' => [
        'oxygen'            => ['min' => 19.5, 'max' => 23.5],
        'methane'           => ['max' => 1.0],
        'carbon_dioxide'    => ['max' => 0.5],
        'hydrogen_sulfide'  => ['max' => 10.0],
        'carbon_monoxide'   => ['max' => 25.0],
        'nitrogen_dioxide'  => ['max' => 3.0],
    ],
    'stock_minimo_herramienta' => 2,

    // Zona horaria con la que se muestran las fechas y horas en los archivos exportados
    // (la aplicación guarda las alertas en UTC)
    'zona_horaria' => env('ALERTAS_ZONA_HORARIA', 'America/Bogota'),

    // Máximo de alertas que admite una exportación (Excel o PDF)
    'limite_exportacion' => 2000,
];