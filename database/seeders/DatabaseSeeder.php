<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            DevUsersSeeder::class,
            RoleAndAdminSeeder::class,
            CategoryLocationSeeder::class,
            LocationSeeder::class,
            CompaniesSeeder::class,
            CategoryGroupToolsSeeder::class,
            CategoryMachineSeeder::class,
            CategoryToolsSeeder::class,
            ToolsSeeder::class,
            ToolStockConditionSeeder::class,
            MachinesSeeder::class,
            EquipmentsSeeder::class,
            GasMeasurementSeeder::class,
            GasMeasurementDetailSeeder::class,
            DailyUseSeeder::class,
            InspectionQuestionSeeder::class,
            InspectionQuestionEquipmentSeeder::class,
            EquipmentInspectionSeeder::class,
        ]);
    }
}
