<?php

namespace App\Dashboard\Sections;

use App\Dashboard\DashboardSection;
use App\Dashboard\SectionResult;
use App\Dashboard\SummaryCard;
use App\Models\Equipment;

class EquipmentsSection implements DashboardSection
{
    /** Estado en BD => [key del contrato, etiqueta, status semántico] */
    private const STATES = [
        'active'      => ['active', 'Activos', 'success'],
        'maintenance' => ['maintenance', 'Mantenimiento', 'warning'],
        'inactive'    => ['inactive', 'Inactivos', 'critical'],
    ];

    public function key(): string
    {
        return 'equipments';
    }

    public function resolve(): SectionResult
    {
        $counts = Equipment::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        // highlights pendientes: próximos diagnósticos y calibraciones
        return SummaryCard::result($counts, self::STATES);
    }
}