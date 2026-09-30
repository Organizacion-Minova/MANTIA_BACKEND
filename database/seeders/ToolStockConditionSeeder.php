<?php

namespace Database\Seeders;

use App\Models\Tools;
use App\Models\ToolStockCondition;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class ToolStockConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $herramientas = Tools::all();

        foreach ($herramientas as $herramienta) {
            ToolStockCondition::firstOrCreate(
                ['tool_id' => $herramienta->id, 'condition' => 'bueno'],
                ['stock' => 10]
            );

            ToolStockCondition::firstOrCreate(
                ['tool_id' => $herramienta->id, 'condition' => 'regular'],
                ['stock' => 5]
            );

            ToolStockCondition::firstOrCreate(
                ['tool_id' => $herramienta->id, 'condition' => 'malo'],
                ['stock' => 2]
            );
        }
    }
}
