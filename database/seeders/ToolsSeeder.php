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
            ['name' => 'Disco de corte 7"'],
            [
                'location_id' => 1,
                'category_id' => 1,
            ]
        );

        Tools::firstOrCreate(
            ['name' => 'Disco de desbaste 4.5"'],
            [
                'location_id' => 1,
                'category_id' => 1,
            ]
        );

        Tools::firstOrCreate(
            ['name' => 'Broca para metal 1/2"'],
            [
                'location_id' => 1,
                'category_id' => 1,
            ]
        );

        Tools::firstOrCreate(
            ['name' => 'Broca para concreto 3/8"'],
            [
                'location_id' => 1,
                'category_id' => 1,
            ]
        );

        Tools::firstOrCreate(
            ['name' => 'Martillo de bola'],
            [
                'location_id' => 1,
                'category_id' => 1,
            ]
        );

        Tools::firstOrCreate(
            ['name' => 'Llave ajustable 12"'],
            [
                'location_id' => 1,
                'category_id' => 1,
            ]
        );

        Tools::firstOrCreate(
            ['name' => 'Alicate universal 8"'],
            [
                'location_id' => 1,
                'category_id' => 1,
            ]
        );

        Tools::firstOrCreate(
            ['name' => 'Destornillador de pala'],
            [
                'location_id' => 1,
                'category_id' => 1,
            ]
        );

        Tools::firstOrCreate(
            ['name' => 'Sierra manual para metal'],
            [
                'location_id' => 1,
                'category_id' => 1,
            ]
        );
        //segunda categoria
        Tools::firstOrCreate(
            ['name' => 'Guantes de nitrilo'],
            [
                'location_id' => 1,
                'category_id' => 2,
            ]
        );

        Tools::firstOrCreate(
        ['name' => 'Casco de seguridad'],
        [
            'location_id' => 1,
            'category_id' => 2,
        ]
    );

    Tools::firstOrCreate(
        ['name' => 'Gafas de protección'],
        [
            'location_id' => 1,
            'category_id' => 2,
        ]
    );

    Tools::firstOrCreate(
        ['name' => 'Tapones auditivos'],
        [
            'location_id' => 1,
            'category_id' => 2,
        ]
    );

    Tools::firstOrCreate(
        ['name' => 'Protector auditivo de copa'],
        [
            'location_id' => 1,
            'category_id' => 2,
        ]
    );

    Tools::firstOrCreate(
        ['name' => 'Respirador de media cara'],
        [
            'location_id' => 1,
            'category_id' => 2,
        ]
    );

    Tools::firstOrCreate(
        ['name' => 'Botas de seguridad'],
        [
            'location_id' => 1,
            'category_id' => 2,
        ]
    );

    Tools::firstOrCreate(
        ['name' => 'Chaleco reflectivo'],
        [
            'location_id' => 1,
            'category_id' => 2,
        ]
    );

    Tools::firstOrCreate(
        ['name' => 'Overol de trabajo'],
        [
            'location_id' => 1,
            'category_id' => 2,
        ]
    );

    Tools::firstOrCreate(
        ['name' => 'Careta de protección facial'],
        [
            'location_id' => 1,
            'category_id' => 2,
        ]
    );
    }
}
