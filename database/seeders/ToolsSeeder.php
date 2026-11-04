<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tools;

class ToolsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tools::firstOrCreate(
            ['name' => 'Disco de corte 4.5"'],
            [
                'location_id' => 1, 
                'category_id' => 1, 
            ]
        );

       Tools::firstOrCreate(
    ['name' => 'Lija de banda grano 80'],
    [
        'location_id' => 1,
        'category_id' => 1,
    ]
);

// CATEGORÍA 2: Lubricantes
Tools::firstOrCreate(
    ['name' => 'Grasa multipropósito industrial'],
    [
        'location_id' => 1,
        'category_id' => 2,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Aceite lubricante para cadenas'],
    [
        'location_id' => 1,
        'category_id' => 2,
    ]
);

// CATEGORÍA 3: Adhesivos
Tools::firstOrCreate(
    ['name' => 'Pegante epóxico de dos componentes'],
    [
        'location_id' => 1,
        'category_id' => 3,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Adhesivo instantáneo industrial'],
    [
        'location_id' => 1,
        'category_id' => 3,
    ]
);

// CATEGORÍA 4: Sellantes
Tools::firstOrCreate(
    ['name' => 'Silicona sellante industrial'],
    [
        'location_id' => 1,
        'category_id' => 4,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Cinta selladora de teflón'],
    [
        'location_id' => 1,
        'category_id' => 4,
    ]
);

// CATEGORÍA 5: Soldadura
Tools::firstOrCreate(
    ['name' => 'Electrodo revestido E6013'],
    [
        'location_id' => 1,
        'category_id' => 5,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Alambre para soldadura MIG'],
    [
        'location_id' => 1,
        'category_id' => 5,
    ]
);

// CATEGORÍA 6: Fijaciones
Tools::firstOrCreate(
    ['name' => 'Pernos hexagonales galvanizados'],
    [
        'location_id' => 1,
        'category_id' => 6,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Tuercas de seguridad industriales'],
    [
        'location_id' => 1,
        'category_id' => 6,
    ]
);

// CATEGORÍA 7: Filtros
Tools::firstOrCreate(
    ['name' => 'Filtro de aceite para motor'],
    [
        'location_id' => 1,
        'category_id' => 7,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Filtro de aire industrial'],
    [
        'location_id' => 1,
        'category_id' => 7,
    ]
);

// CATEGORÍA 8: Material eléctrico
Tools::firstOrCreate(
    ['name' => 'Cable eléctrico calibre 12 AWG'],
    [
        'location_id' => 1,
        'category_id' => 8,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Terminales eléctricos de cobre'],
    [
        'location_id' => 1,
        'category_id' => 8,
    ]
);

// CATEGORÍA 9: Refrigerantes
Tools::firstOrCreate(
    ['name' => 'Refrigerante para motor'],
    [
        'location_id' => 1,
        'category_id' => 9,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Líquido refrigerante concentrado'],
    [
        'location_id' => 1,
        'category_id' => 9,
    ]
);

// CATEGORÍA 10: Productos químicos
Tools::firstOrCreate(
    ['name' => 'Desengrasante industrial'],
    [
        'location_id' => 1,
        'category_id' => 10,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Limpiador de contactos eléctricos'],
    [
        'location_id' => 1,
        'category_id' => 10,
    ]
);
        //segunda categoria
        // CATEGORÍA 12: Eléctricas
Tools::firstOrCreate(
    ['name' => 'Taladro eléctrico industrial'],
    [
        'location_id' => 1,
        'category_id' => 12,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Esmeril angular eléctrico'],
    [
        'location_id' => 1,
        'category_id' => 12,
    ]
);

// CATEGORÍA 13: Neumáticas
Tools::firstOrCreate(
    ['name' => 'Llave de impacto neumática'],
    [
        'location_id' => 1,
        'category_id' => 13,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Martillo neumático'],
    [
        'location_id' => 1,
        'category_id' => 13,
    ]
);

// CATEGORÍA 14: De medición
Tools::firstOrCreate(
    ['name' => 'Calibrador Vernier'],
    [
        'location_id' => 1,
        'category_id' => 14,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Multímetro digital'],
    [
        'location_id' => 1,
        'category_id' => 14,
    ]
);

// CATEGORÍA 15: De corte
Tools::firstOrCreate(
    ['name' => 'Sierra circular portátil'],
    [
        'location_id' => 1,
        'category_id' => 15,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Cizalla manual para metal'],
    [
        'location_id' => 1,
        'category_id' => 15,
    ]
);

// CATEGORÍA 16: De perforación
Tools::firstOrCreate(
    ['name' => 'Rotomartillo industrial'],
    [
        'location_id' => 1,
        'category_id' => 16,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Perforadora manual para roca'],
    [
        'location_id' => 1,
        'category_id' => 16,
    ]
);

// CATEGORÍA 17: De sujeción
Tools::firstOrCreate(
    ['name' => 'Prensa de banco'],
    [
        'location_id' => 1,
        'category_id' => 17,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Pinza de presión'],
    [
        'location_id' => 1,
        'category_id' => 17,
    ]
);

// CATEGORÍA 18: De mantenimiento
Tools::firstOrCreate(
    ['name' => 'Extractor de rodamientos'],
    [
        'location_id' => 1,
        'category_id' => 18,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Llave dinamométrica'],
    [
        'location_id' => 1,
        'category_id' => 18,
    ]
);

// CATEGORÍA 19: De excavación
Tools::firstOrCreate(
    ['name' => 'Pico minero'],
    [
        'location_id' => 1,
        'category_id' => 19,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Pala de punta reforzada'],
    [
        'location_id' => 1,
        'category_id' => 19,
    ]
);

// CATEGORÍA 20: De limpieza
Tools::firstOrCreate(
    ['name' => 'Cepillo de alambre industrial'],
    [
        'location_id' => 1,
        'category_id' => 20,
    ]
);

Tools::firstOrCreate(
    ['name' => 'Aspiradora industrial'],
    [
        'location_id' => 1,
        'category_id' => 20,
    ]
);
}
}
