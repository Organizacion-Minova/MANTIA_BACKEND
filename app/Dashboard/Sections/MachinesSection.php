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
        'activo'        => ['active', 'Activas', 'success'],
        'mantenimiento' => ['maintenance', 'Mantenimiento', 'warning'],
        'inactivo'      => ['out_of_service', 'Fuera de servicio', 'critical'],
    ];

    public function key(): string
    {
        return 'machines';
    }
    //compara el estado active del front end con activo de back y lo normaliza
    public function resolve(): SectionResult
    {
        $statusCounts = Machine::query()
            ->selectRaw('LOWER(status) as status, COUNT(*) as total')
            ->groupByRaw('LOWER(status)')
            ->pluck('total', 'status')
            ->all();

        $counts = [];
        foreach ($statusCounts as $status => $total) {
            $normalizedStatus = match ($status) {
                'active', 'activo' => 'activo',
                'maintenance', 'mantenimiento' => 'mantenimiento',
                'inactive', 'inactivo' => 'inactivo',
                default => $status,
            };

            $counts[$normalizedStatus] = ($counts[$normalizedStatus] ?? 0) + (int) $total;
        }

        return SummaryCard::result($counts, self::STATES);
    }
}