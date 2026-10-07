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
            ['name' => 'Eléctricas'],
            ['description' => 'Herramientas eléctricas', 
            'status' => "Activo",
            'category_group_id' => 2]
        );


    }
}
