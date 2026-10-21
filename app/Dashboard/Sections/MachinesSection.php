<?php

namespace App\Dashboard\Sections;

use App\Dashboard\DashboardSection;
use App\Dashboard\SectionResult;
use App\Models\Machine;
use App\Dashboard\SummaryCard;

class MachinesSection implements DashboardSection
{
    /** Estado en BD => [key del contrato, etiqueta, status semántico] */
    private const STATES = [
        'active'      => ['active', 'Activas', 'success'],
        'maintenance' => ['maintenance', 'Mantenimiento', 'warning'],
        'inactive'    => ['out_of_service', 'Fuera de servicio', 'critical'],
    ];

    public function key(): string
    {
        return 'machines';
    }

    public function resolve(): SectionResult
{
    $counts = Machine::query()
        ->selectRaw('status, count(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status')
        ->all();

    // highlights pendientes: preoperacionales próximos
    return SummaryCard::result($counts, self::STATES);
}
}