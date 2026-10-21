<?php

namespace App\Dashboard;

use Illuminate\Support\Facades\DB;

class ToolStockSummary
{
    /** bucket => [key del contrato, etiqueta, status semántico] */
    private const STATES = [
        'available'     => ['available', 'Disponible', 'success'],
        'low_stock'     => ['low_stock', 'Stock bajo', 'warning'],
        'bad_condition' => ['bad_condition', 'Mal estado', 'critical'],
    ];

    public static function forGroup(string $groupName): SectionResult
    {
        $usable = config('dashboard.tools.usable_conditions');
        $placeholders = implode(', ', array_fill(0, count($usable), '?'));

        // 1) Por herramienta: unidades totales y unidades utilizables.
        $perTool = DB::table('tool as t')
            ->join('category as c', 'c.id', '=', 't.category_id')
            ->join('category_group as g', 'g.id', '=', 'c.category_group_id')
            ->leftJoin('tool_stock_condition as s', 's.tool_id', '=', 't.id')
            ->where('g.name', $groupName)
            ->where('c.status', config('dashboard.tools.active_category_status'))
            ->groupBy('t.id')
            ->selectRaw('t.id, coalesce(sum(s.stock), 0) as total_units')
            ->selectRaw(
                "coalesce(sum(s.stock) filter (where s.\"condition\" in ($placeholders)), 0) as usable_units",
                $usable,
            );

        // 2) Cada herramienta cae en un solo grupo; se cuenta por grupo.
        $counts = DB::query()
            ->fromSub($perTool, 'per_tool')
            ->selectRaw(
                "case
                    when total_units = 0 then 'out_of_stock'
                    when usable_units = 0 then 'bad_condition'
                    when usable_units < ? then 'low_stock'
                    else 'available'
                end as bucket",
                [config('dashboard.tools.low_stock_threshold')],
            )
            ->selectRaw('count(*) as total')
            ->groupBy('bucket')
            ->pluck('total', 'bucket')
            ->all();

        // "Sin stock" se muestra hoy como stock bajo; queda separado aquí por si
        // más adelante se quiere exponer como estado propio.
        $counts['low_stock'] = ($counts['low_stock'] ?? 0) + ($counts['out_of_stock'] ?? 0);
        unset($counts['out_of_stock']);

        return SummaryCard::result($counts, self::STATES);
    }
}