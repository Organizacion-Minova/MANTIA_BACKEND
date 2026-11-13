<?php

namespace App\Dashboard\Sections;

use App\Dashboard\DashboardSection;
use App\Dashboard\SectionResult;
use App\Models\Equipment;

class EquipmentsNonConsumablesSection implements DashboardSection
{
    public function key(): string
    {
        return 'equipmentsNonConsumables';
    }

    public function resolve(): SectionResult
    {
        return EquipmentSummary::forCategory(Equipment::CATEGORY_NON_CONSUMABLE);
    }
}