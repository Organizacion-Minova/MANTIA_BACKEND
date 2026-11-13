<?php

namespace App\Dashboard\Sections;

use App\Dashboard\SectionResult;
use App\Dashboard\SummaryCard;
use App\Models\Equipment;

final class EquipmentSummary
{
    private const STATES = [
        'activo' => ['active', 'Activos', 'success'],
        'mantenimiento' => ['maintenance', 'Mantenimiento', 'warning'],
        'inactivo' => ['inactive', 'Inactivos', 'critical'],
    ];

    public static function forCategory(string $category): SectionResult
    {
        $counts = Equipment::query()
            ->where('category', $category)
            ->selectRaw('LOWER(status) as status, COUNT(*) as total')
            ->groupByRaw('LOWER(status)')
            ->pluck('total', 'status')
            ->all();

        return SummaryCard::result($counts, self::STATES);
    }
}
