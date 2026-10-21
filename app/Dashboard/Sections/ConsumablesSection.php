<?php

namespace App\Dashboard\Sections;

use App\Dashboard\DashboardSection;
use App\Dashboard\SectionResult;
use App\Dashboard\ToolStockSummary;

class ConsumablesSection implements DashboardSection
{
    public function key(): string
    {
        return 'consumables';
    }

    
    public function resolve(): SectionResult
    {
        return ToolStockSummary::forGroup(config('dashboard.tools.groups.consumables'));
    }
}