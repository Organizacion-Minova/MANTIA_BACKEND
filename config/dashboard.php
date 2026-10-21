<?php

return [
    'tools' => [
        // E1: los grupos se identifican por nombre exacto.
        'groups' => [
            'consumables'     => 'Herramientas consumibles',
            'non_consumables' => 'Herramientas no consumibles',
        ],

        // D2: solo cuentan herramientas de categorías con este estado.
        'active_category_status' => 'Activo',

        // Condiciones cuyas unidades se consideran utilizables.
        'usable_conditions' => ['bueno', 'regular'],

        // C4: umbral global de stock bajo (unidades utilizables). Más adelante,
        // un min_stock por herramienta podrá tener prioridad sobre este valor.
        'low_stock_threshold' => 5,
    ],

    'locations' => [
    // Solo ubicaciones con este estado (cuando only_active es true).
    'only_active'   => true,
    'active_status' => 'active',

    // Oculta las ubicaciones que no tienen ningún elemento.
    'hide_empty'    => true,
    ],
];