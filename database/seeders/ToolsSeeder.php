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
            ['code'=>'4ft-p','name' => 'Disco de corte 4.5"'],
            [
                'location_id' => 1, 
                'category_id' => 1, 
            ]
        );

       Tools::firstOrCreate(
    ['code'=>'lij-80','name' => 'Lija de banda grano 80'],
    [
        'location_id' => 1,
        'category_id' => 1,
    ]
);

// CATEGORÍA 2: Lubricantes
Tools::firstOrCreate(
    ['code'=>'lub-1','name' => 'Grasa multipropósito industrial'],
    [
        'location_id' => 1,
        'category_id' => 2,
    ]
);

Tools::firstOrCreate(
    ['code'=>'lub-2','name' => 'Aceite lubricante para cadenas'],
    [
        'location_id' => 1,
        'category_id' => 2,
    ]
);

// CATEGORÍA 3: Adhesivos
Tools::firstOrCreate(
    ['code'=>'adh-1','name' => 'Pegante epóxico de dos componentes'],
    [
        'location_id' => 1,
        'category_id' => 3,
    ]
);

Tools::firstOrCreate(
    ['code'=>'adh-2','name' => 'Adhesivo instantáneo industrial'],
    [
        'location_id' => 1,
        'category_id' => 3,
    ]
);

// CATEGORÍA 4: Sellantes
Tools::firstOrCreate(
    ['code'=>'sel-1','name' => 'Silicona sellante industrial'],
    [
        'location_id' => 1,
        'category_id' => 4,
    ]
);

Tools::firstOrCreate(
    ['code'=>'sel-2','name' => 'Cinta selladora de teflón'],
    [
        'location_id' => 1,
        'category_id' => 4,
    ]
);

// CATEGORÍA 5: Soldadura
Tools::firstOrCreate(
    ['code'=>'sol-1','name' => 'Electrodo revestido E6013'],
    [
        'location_id' => 1,
        'category_id' => 5,
    ]
);

Tools::firstOrCreate(
    ['code'=>'sol-2','name' => 'Alambre para soldadura MIG'],
    [
        'location_id' => 1,
        'category_id' => 5,
    ]
);

// CATEGORÍA 6: Fijaciones
Tools::firstOrCreate(
    ['code'=>'fij-1','name' => 'Pernos hexagonales galvanizados'],
    [
        'location_id' => 1,
        'category_id' => 6,
    ]
);

Tools::firstOrCreate(
    ['code'=>'fij-2','name' => 'Tuercas de seguridad industriales'],
    [
        'location_id' => 1,
        'category_id' => 6,
    ]
);

// CATEGORÍA 7: Filtros
Tools::firstOrCreate(
    ['code'=>'fil-1','name' => 'Filtro de aceite para motor'],
    [
        'location_id' => 1,
        'category_id' => 7,
    ]
);

Tools::firstOrCreate(
    ['code'=>'fil-2','name' => 'Filtro de aire industrial'],
    [
        'location_id' => 1,
        'category_id' => 7,
    ]
);

// CATEGORÍA 8: Material eléctrico
Tools::firstOrCreate(
    ['code'=>'ele-1','name' => 'Cable eléctrico calibre 12 AWG'],
    [
        'location_id' => 1,
        'category_id' => 8,
    ]
);

Tools::firstOrCreate(
    ['code'=>'ele-2','name' => 'Terminales eléctricos de cobre'],
    [
        'location_id' => 1,
        'category_id' => 8,
    ]
);

// CATEGORÍA 9: Refrigerantes
Tools::firstOrCreate(
    ['code'=>'ref-1','name' => 'Refrigerante para motor'],
    [
        'location_id' => 1,
        'category_id' => 9,
    ]
);

Tools::firstOrCreate(
    ['code'=>'ref-2','name' => 'Líquido refrigerante concentrado'],
    [
        'location_id' => 1,
        'category_id' => 9,
    ]
);

// CATEGORÍA 10: Productos químicos
Tools::firstOrCreate(
    ['code'=>'quim-1','name' => 'Desengrasante industrial'],
    [
        'location_id' => 1,
        'category_id' => 10,
    ]
);

Tools::firstOrCreate(
    ['code'=>'quim-2','name' => 'Limpiador de contactos eléctricos'],
    [
        'location_id' => 1,
        'category_id' => 10,
    ]
);
        //segunda categoria
        // CATEGORÍA 12: Eléctricas
Tools::firstOrCreate(
    ['code'=>'ele-3','name' => 'Taladro eléctrico industrial'],
    [
        'location_id' => 1,
        'category_id' => 12,
    ]
);

Tools::firstOrCreate(
    ['code'=>'ele-4','name' => 'Esmeril angular eléctrico'],
    [
        'location_id' => 1,
        'category_id' => 12,
    ]
);

// CATEGORÍA 13: Neumáticas
Tools::firstOrCreate(
    ['code'=>'neu-1','name' => 'Llave de impacto neumática'],
    [
        'location_id' => 1,
        'category_id' => 13,
    ]
);

Tools::firstOrCreate(
    ['code'=>'neu-2','name' => 'Martillo neumático'],
    [
        'location_id' => 1,
        'category_id' => 13,
    ]
);

// CATEGORÍA 14: De medición
Tools::firstOrCreate(
    ['code'=>'med-1','name' => 'Calibrador Vernier'],
    [
        'location_id' => 1,
        'category_id' => 14,
    ]
);

Tools::firstOrCreate(
    ['code'=>'med-2','name' => 'Multímetro digital'],
    [
        'location_id' => 1,
        'category_id' => 14,
    ]
);

// CATEGORÍA 15: De corte
Tools::firstOrCreate(
    ['code'=>'cort-1',  'name' => 'Sierra circular portátil'],
    [
        'location_id' => 1,
        'category_id' => 15,
    ]
);

Tools::firstOrCreate(
    ['code'=>'cort-2','name' => 'Cizalla manual para metal'],
    [
        'location_id' => 1,
        'category_id' => 15,
    ]
);

// CATEGORÍA 16: De perforación
Tools::firstOrCreate(
    ['code'=>'perf-1','name' => 'Rotomartillo industrial'],
    [
        'location_id' => 1,
        'category_id' => 16,
    ]
);

Tools::firstOrCreate(
    ['code'=>'perf-2','name' => 'Perforadora manual para roca'],
    [
        'location_id' => 1,
        'category_id' => 16,
    ]
);

// CATEGORÍA 17: De sujeción
Tools::firstOrCreate(
    ['code'=>'suj-1','name' => 'Prensa de banco'],
    [
        'location_id' => 1,
        'category_id' => 17,
    ]
);

Tools::firstOrCreate(
    ['code'=>'suj-2','name' => 'Pinza de presión'],
    [
        'location_id' => 1,
        'category_id' => 17,
    ]
);

// CATEGORÍA 18: De mantenimiento
Tools::firstOrCreate(
    ['code'=>'mant-1','name' => 'Extractor de rodamientos'],
    [
        'location_id' => 1,
        'category_id' => 18,
    ]
);

Tools::firstOrCreate(
    ['code'=>'mant-2','name' => 'Llave dinamométrica'],
    [
        'location_id' => 1,
        'category_id' => 18,
    ]
);

// CATEGORÍA 19: De excavación
Tools::firstOrCreate(
    ['code'=>'exc-1','name' => 'Pico minero'],
    [
        'location_id' => 1,
        'category_id' => 19,
    ]
);

Tools::firstOrCreate(
    ['code'=>'exc-2','name' => 'Pala de punta reforzada'],
    [
        'location_id' => 1,
        'category_id' => 19,
    ]
);

// CATEGORÍA 20: De limpieza
Tools::firstOrCreate(
    ['code'=>'lim-1','name' => 'Cepillo de alambre industrial'],
    [
        'location_id' => 1,
        'category_id' => 20,
    ]
);

Tools::firstOrCreate(
    ['code'=>'lim-2','name' => 'Aspiradora industrial'],
    [
        'location_id' => 1,
        'category_id' => 20,
    ]
);
}
}
