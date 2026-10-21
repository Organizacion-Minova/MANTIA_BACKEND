<?php

namespace App\Dashboard\Sections;

use App\Dashboard\DashboardSection;
use App\Dashboard\SectionResult;
use Illuminate\Support\Facades\DB;

class LocationsSection implements DashboardSection
{
    public function key(): string
    {
        return 'locations';
    }

    public function resolve(): SectionResult
    {
        // Un elemento por fila: máquinas, equipos y herramientas con su ubicación.
        $items = DB::table('machine')->select('location_id')
            ->unionAll(DB::table('equipment')->select('location_id'))
            ->unionAll(
                DB::table('tool as t')
                    ->join('category as c', 'c.id', '=', 't.category_id')
                    ->where('c.status', config('dashboard.tools.active_category_status'))
                    ->select('t.location_id')
            );

        $locations = DB::table('location as l')
            ->leftJoinSub($items, 'items', 'items.location_id', '=', 'l.id')
            ->when(
                config('dashboard.locations.only_active'),
                fn ($q) => $q->where('l.status', config('dashboard.locations.active_status')),
            )
            ->when(
                config('dashboard.locations.hide_empty'),
                fn ($q) => $q->havingRaw('count(items.location_id) > 0'),
            )
            ->groupBy('l.id', 'l.name')
            ->selectRaw('l.id, l.name, count(items.location_id) as total')
            ->orderByDesc('total')
            ->orderBy('l.name')
            ->get()
            ->map(fn ($row) => [
                'id'    => (int) $row->id,
                'name'  => $row->name,
                'total' => (int) $row->total,
            ])
            ->all();

        return $locations === []
            ? SectionResult::empty([])
            : SectionResult::ok($locations);
    }
}