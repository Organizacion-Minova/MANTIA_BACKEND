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
        // Un registro por elemento; el tipo permite mostrar el desglose por ubicación.
        $items = DB::table('machine')
            ->select('location_id')
            ->selectRaw("'machines' as item_type")
            ->unionAll(
                DB::table('equipment')
                    ->select('location_id')
                    ->selectRaw("'equipments' as item_type")
            )
            ->unionAll(
                DB::table('tool as t')
                    ->join('category as c', 'c.id', '=', 't.category_id')
                    ->where('c.status', config('dashboard.tools.active_category_status'))
                    ->select('t.location_id')
                    ->selectRaw("'tools' as item_type")
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
            ->selectRaw(
                'l.id, l.name, count(items.location_id) as total, '.
                "sum(case when items.item_type = 'machines' then 1 else 0 end) as machines, ".
                "sum(case when items.item_type = 'equipments' then 1 else 0 end) as equipments, ".
                "sum(case when items.item_type = 'tools' then 1 else 0 end) as tools"
            )
            ->orderByDesc('total')
            ->orderBy('l.name')
            ->get()
            ->map(fn ($row) => [
                'id'    => (int) $row->id,
                'name'  => $row->name,
                'total' => (int) $row->total,
                'items' => [
                    ['key' => 'machines', 'label' => 'Máquinas', 'quantity' => (int) $row->machines],
                    ['key' => 'equipments', 'label' => 'Equipos', 'quantity' => (int) $row->equipments],
                    ['key' => 'tools', 'label' => 'Herramientas', 'quantity' => (int) $row->tools],
                ],
            ])
            ->all();

        return $locations === []
            ? SectionResult::empty([])
            : SectionResult::ok($locations);
    }
}