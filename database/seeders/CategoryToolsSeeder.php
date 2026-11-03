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
    [
        'description' => 'Herramientas manuales',
        'status' => 'Activo',
        'category_group_id' => 1
    ]
    );
    
    CategoryTools::firstOrCreate(
        ['name' => 'De golpe'],
        [
            'description' => 'Herramientas manuales utilizadas para golpear y ajustar materiales',
            'status' => 'Activo',
            'category_group_id' => 1
        ]
    );
    
    CategoryTools::firstOrCreate(
        ['name' => 'De ajuste'],
        [
            'description' => 'Herramientas destinadas al ajuste y apriete de elementos mecánicos',
            'status' => 'Activo',
            'category_group_id' => 1
        ]
    );
    
    CategoryTools::firstOrCreate(
        ['name' => 'De sujeción'],
        [
            'description' => 'Herramientas utilizadas para sujetar y mantener piezas firmes',
            'status' => 'Activo',
            'category_group_id' => 1
        ]
    );
    
    CategoryTools::firstOrCreate(
        ['name' => 'De corte manual'],
        [
            'description' => 'Herramientas para realizar cortes manuales en diferentes materiales',
            'status' => 'Activo',
            'category_group_id' => 1
        ]
    );
    
    CategoryTools::firstOrCreate(
        ['name' => 'De medición manual'],
        [
            'description' => 'Instrumentos manuales para medir longitudes y dimensiones',
            'status' => 'Activo',
            'category_group_id' => 1
        ]
    );
    
    CategoryTools::firstOrCreate(
        ['name' => 'De excavación manual'],
        [
            'description' => 'Herramientas utilizadas para remover y extraer material',
            'status' => 'Activo',
            'category_group_id' => 1
        ]
    );
    
    CategoryTools::firstOrCreate(
        ['name' => 'De perforación manual'],
        [
            'description' => 'Herramientas manuales destinadas a realizar perforaciones',
            'status' => 'Activo',
            'category_group_id' => 1
        ]
    );
    
    CategoryTools::firstOrCreate(
        ['name' => 'De limpieza manual'],
        [
            'description' => 'Herramientas para limpieza de superficies y áreas de trabajo',
            'status' => 'Activo',
            'category_group_id' => 1
        ]
    );
    
    CategoryTools::firstOrCreate(
        ['name' => 'De albañilería'],
        [
            'description' => 'Herramientas manuales utilizadas en trabajos de construcción y adecuación',
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
            ['name' => 'Manuales'],
            [
                'description' => 'Herramientas de accionamiento manual',
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
