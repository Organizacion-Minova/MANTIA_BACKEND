<?php

namespace App\Dashboard\Sections;

use App\Dashboard\DashboardSection;
use App\Dashboard\SectionResult;

class GasesSection implements DashboardSection
{
    public function key(): string
    {
        return 'gases';
    }

    public function resolve(): SectionResult
    {
        // Etapa 5: aquí irá la consulta real.
        return SectionResult::unavailable();
    }
}