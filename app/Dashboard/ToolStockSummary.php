<?php

namespace App\Dashboard;

use Illuminate\Support\Facades\DB;

class ToolStockSummary
{
    /** Condición en BD => [key del contrato, etiqueta, status semántico] */
    private const STATES = [
        'bueno'   => ['good', 'Buenas', 'success'],
        'regular' => ['regular', 'Regulares', 'warning'],
        'malo'    => ['bad', 'Malas', 'critical'],
    ];

    public static function forGroup(string $groupName): SectionResult
    {
        $usable = config('dashboard.tools.usable_conditions');
        $placeholders = implode(', ', array_fill(0, count($usable), '?'));

        // 1) Por herramienta: unidades por condición y unidades utilizables.
        $perTool = DB::table('tool as t')
            ->join('category as c', 'c.id', '=', 't.category_id')
            ->join('category_group as g', 'g.id', '=', 'c.category_group_id')
            ->leftJoin('tool_stock_condition as s', 's.tool_id', '=', 't.id')
            ->where('g.name', $groupName)
            ->where('c.status', config('dashboard.tools.active_category_status'))
            ->groupBy('t.id')
            ->selectRaw('t.id');

        foreach (array_keys(self::STATES) as $condition) {
            $perTool->selectRaw(
                "coalesce(sum(s.stock) filter (where s.\"condition\" = ?), 0) as units_{$condition}",
                [$condition],
            );
        }

        $perTool->selectRaw(
            "coalesce(sum(s.stock) filter (where s.\"condition\" in ($placeholders)), 0) as usable_units",
            $usable,
        );

        // 2) Totales: unidades por condición y herramientas con stock bajo.
        $outer = DB::query()
            ->fromSub($perTool, 'per_tool')
            ->selectRaw('count(*) as tools')
            ->selectRaw(
                'count(*) filter (where usable_units < ?) as low_stock_tools',
                [config('dashboard.tools.low_stock_threshold')],
            );

        foreach (array_keys(self::STATES) as $condition) {
            $outer->selectRaw("coalesce(sum(units_{$condition}), 0) as sum_{$condition}");
        }

        $row = $outer->first();

        $counts = [];
        foreach (array_keys(self::STATES) as $condition) {
            $counts[$condition] = (int) $row->{"sum_{$condition}"};
        }

        // El aviso solo aparece cuando hay algo que resaltar.
        $lowStockTools = (int) $row->low_stock_tools;
        $highlights = $lowStockTools > 0
            ? [['key' => 'low_stock_tools', 'value' => $lowStockTools]]
            : [];

        $result = SummaryCard::result($counts, self::STATES, $highlights);

        // Hay herramientas pero cero unidades: no está vacío (aplica el aviso de stock bajo).
        if ($result->state === SectionState::Empty && (int) $row->tools > 0) {
            return SectionResult::ok($result->data);
        }

        return $result;
    }
}