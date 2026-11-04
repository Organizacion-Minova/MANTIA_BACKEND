<?php

namespace Database\Seeders;

use App\Models\CategoryTools;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoryToolsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CategoryTools::firstOrCreate(
            ['name' => 'Manuales'],
            ['description' => 'Herramientas manuales',
            'status' => "Activo",
            'category_group_id' => 1]
        );

                    CategoryTools::firstOrCreate(
    ['name' => 'Abrasivos'],
    [
        'description' => 'Materiales abrasivos utilizados para desgastar, pulir y acondicionar superficies.',
        'status' => 'Activo',
        'category_group_id' => 1
    ]
);

CategoryTools::firstOrCreate(
    ['name' => 'Lubricantes'],
    [
        'description' => 'Productos destinados a reducir la fricción y el desgaste de componentes mecánicos.',
        'status' => 'Activo',
        'category_group_id' => 1
    ]
);

CategoryTools::firstOrCreate(
    ['name' => 'Adhesivos'],
    [
        'description' => 'Productos utilizados para unir, fijar y ensamblar diferentes materiales.',
        'status' => 'Activo',
        'category_group_id' => 1
    ]
);

CategoryTools::firstOrCreate(
    ['name' => 'Sellantes'],
    [
        'description' => 'Materiales empleados para sellar uniones, juntas y conexiones.',
        'status' => 'Activo',
        'category_group_id' => 1
    ]
);

CategoryTools::firstOrCreate(
    ['name' => 'Soldadura'],
    [
        'description' => 'Electrodos y materiales consumibles utilizados en procesos de soldadura.',
        'status' => 'Activo',
        'category_group_id' => 1
    ]
);

CategoryTools::firstOrCreate(
    ['name' => 'Fijaciones'],
    [
        'description' => 'Elementos de unión como tornillos, tuercas, arandelas y pernos.',
        'status' => 'Activo',
        'category_group_id' => 1
    ]
);

CategoryTools::firstOrCreate(
    ['name' => 'Filtros'],
    [
        'description' => 'Elementos filtrantes para sistemas de aire, aceite y combustible.',
        'status' => 'Activo',
        'category_group_id' => 1
    ]
);

CategoryTools::firstOrCreate(
    ['name' => 'Material eléctrico'],
    [
        'description' => 'Insumos eléctricos como cables, conectores, terminales y cinta aislante.',
        'status' => 'Activo',
        'category_group_id' => 1
    ]
);

CategoryTools::firstOrCreate(
    ['name' => 'Refrigerantes'],
    [
        'description' => 'Fluidos utilizados para controlar la temperatura de motores y sistemas industriales.',
        'status' => 'Activo',
        'category_group_id' => 1
    ]
);

CategoryTools::firstOrCreate(
    ['name' => 'Productos químicos'],
    [
        'description' => 'Sustancias utilizadas en procesos de mantenimiento, tratamiento y conservación de equipos.',
        'status' => 'Activo',
        'category_group_id' => 1
    ]
);
//categoria dos
        CategoryTools::firstOrCreate(
    ['name' => 'Eléctricas'],
    [   
        'description' => 'Herramientas eléctricas',
        'status' => 'Activo',
        'category_group_id' => 2
    ]
);

        
        CategoryTools::firstOrCreate(
            ['name' => 'Neumáticas'],
            [
                'description' => 'Herramientas accionadas mediante aire comprimido',
                'status' => 'Activo',
                'category_group_id' => 2
            ]
        );
        
        CategoryTools::firstOrCreate(
            ['name' => 'De medición'],
            [
                'description' => 'Instrumentos para medición y verificación de dimensiones',
                'status' => 'Activo',
                'category_group_id' => 2
            ]
        );
        
        CategoryTools::firstOrCreate(
            ['name' => 'De corte'],
            [
                'description' => 'Herramientas destinadas al corte de diferentes materiales',
                'status' => 'Activo',
                'category_group_id' => 2
            ]
        );
        
        CategoryTools::firstOrCreate(
            ['name' => 'De perforación'],
            [
                'description' => 'Herramientas utilizadas para perforar roca, metal y otros materiales',
                'status' => 'Activo',
                'category_group_id' => 2
            ]
        );
        
        CategoryTools::firstOrCreate(
            ['name' => 'De sujeción'],
            [
                'description' => 'Herramientas para sujetar, ajustar y asegurar piezas',
                'status' => 'Activo',
                'category_group_id' => 2
            ]
        );
        
        CategoryTools::firstOrCreate(
            ['name' => 'De mantenimiento'],
            [
                'description' => 'Herramientas destinadas a labores de mantenimiento preventivo y correctivo',
                'status' => 'Activo',
                'category_group_id' => 2
            ]
        );
        
        CategoryTools::firstOrCreate(
            ['name' => 'De excavación'],
            [
                'description' => 'Herramientas utilizadas en trabajos de excavación y remoción de material',
                'status' => 'Activo',
                'category_group_id' => 2
            ]
        );
        
        CategoryTools::firstOrCreate(
            ['name' => 'De limpieza'],
            [
                'description' => 'Herramientas destinadas a la limpieza de equipos y áreas de trabajo',
                'status' => 'Activo',
                'category_group_id' => 2
            ]
        );


    }
}
