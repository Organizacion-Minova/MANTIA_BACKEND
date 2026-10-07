<?php

namespace Database\Seeders;

use App\Models\CategoryGroupTools;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoryGroupToolsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CategoryGroupTools::firstOrCreate(
            ['name' => 'Herramientas consumibles'],
        );

        CategoryGroupTools::firstOrCreate(
            ['name' => 'Herramientas no consumibles'],
        );

    }
}
