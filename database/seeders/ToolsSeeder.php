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
            ['name' => 'Guantes de nitrilo'],
            [
                'location_id' => 1,
                'category_id' => 2,
            ]
        );
    }
}
