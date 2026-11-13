<?php

namespace App\Dashboard\Sections;

use App\Dashboard\DashboardSection;
use App\Dashboard\SectionResult;
use App\Models\Equipment;

class EquipmentsConsumablesSection implements DashboardSection
{
    public function key(): string
    {
        return 'equipmentsConsumables';
    }

    public function resolve(): SectionResult
    {
        return EquipmentSummary::forCategory(Equipment::CATEGORY_CONSUMABLE);
    }
}