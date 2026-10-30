<?php

namespace App\Dashboard\Sections;

use App\Dashboard\DashboardSection;
use App\Dashboard\SectionResult;
use Illuminate\Support\Facades\DB;

class ActivitySection implements DashboardSection
{
    public function key(): string
    {
        return 'activity';
    }

    public function resolve(): SectionResult
    {
        $items = array_merge(
            $this->machineItems(),
            $this->equipmentItems(),
            $this->toolItems(),
        );

        usort($items, function (array $a, array $b) {
            $byPriority = $a['priority'] <=> $b['priority'];

            return $byPriority !== 0
                ? $byPriority
                : strcasecmp($a['item']['name'], $b['item']['name']);
        });

        $items = array_map(fn (array $entry) => $entry['item'], $items);

        return $items === []
            ? SectionResult::empty([])
            : SectionResult::ok($items);
    }

    private function machineItems(): array
    {
        return DB::table('machine')
            ->whereIn('status', ['inactive', 'maintenance'])
            ->orderByRaw("case status when 'inactive' then 0 else 1 end")
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status'])
            ->map(fn ($machine) => [
                'priority' => $machine->status === 'inactive' ? 0 : 1,
                'item' => [
                    'id' => "machine-{$machine->id}",
                    'type' => 'machine',
                    'name' => $machine->name,
                    'code' => $machine->code,
                    'status' => $machine->status,
                    'href' => "/machines/cardMachine/{$machine->id}",
                ],
            ])
            ->all();
    }

    private function equipmentItems(): array
    {
        return DB::table('equipment')
            ->whereIn('status', ['inactive', 'maintenance'])
            ->orderByRaw("case status when 'inactive' then 0 else 1 end")
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status'])
            ->map(fn ($equipment) => [
                'priority' => $equipment->status === 'inactive' ? 0 : 1,
                'item' => [
                    'id' => "equipment-{$equipment->id}",
                    'type' => 'equipment',
                    'name' => $equipment->name,
                    'code' => $equipment->code,
                    'status' => $equipment->status,
                    'href' => "/equipment/cardequipment/{$equipment->id}",
                ],
            ])
            ->all();
    }

    private function toolItems(): array
    {
        $groups = config('dashboard.tools.groups');
        $usableConditions = config('dashboard.tools.usable_conditions');
        $usablePlaceholders = implode(', ', array_fill(0, count($usableConditions), '?'));

        $stockByTool = DB::table('tool as t')
            ->join('category as c', 'c.id', '=', 't.category_id')
            ->join('category_group as g', 'g.id', '=', 'c.category_group_id')
            ->leftJoin('tool_stock_condition as s', 's.tool_id', '=', 't.id')
            ->where('c.status', config('dashboard.tools.active_category_status'))
            ->whereIn('g.name', array_values($groups))
            ->groupBy('t.id', 't.name', 'g.name')
            ->select('t.id', 't.name', 'g.name as group_name')
            ->selectRaw(
                'coalesce(sum(case when s."condition" = ? then s.stock else 0 end), 0) as bad_stock',
                ['malo'],
            )
            ->selectRaw(
                "coalesce(sum(case when s.\"condition\" in ($usablePlaceholders) then s.stock else 0 end), 0) as usable_stock",
                $usableConditions,
            );

        $tools = DB::query()
            ->fromSub($stockByTool, 'stock_by_tool')
            ->where(function ($query) {
                $query->where('bad_stock', '>', 0)
                    ->orWhere('usable_stock', '<', config('dashboard.tools.low_stock_threshold'));
            })
            ->orderByRaw('bad_stock desc')
            ->orderBy('usable_stock')
            ->orderBy('name')
            ->get();

        return $tools->map(function ($tool) use ($groups) {
            $isConsumable = $tool->group_name === $groups['consumables'];
            $lowStock = (int) $tool->usable_stock < config('dashboard.tools.low_stock_threshold');

            return [
                'priority' => (int) $tool->bad_stock > 0 ? 2 : 3,
                'item' => [
                    'id' => "tool-{$tool->id}",
                    'type' => $isConsumable ? 'consumable' : 'nonConsumable',
                    'name' => $tool->name,
                    'badStock' => (int) $tool->bad_stock,
                    'usableStock' => (int) $tool->usable_stock,
                    'lowStock' => $lowStock,
                    'href' => $isConsumable ? '/consumables/tools' : '/noconsumables/tools',
                ],
            ];
        })->all();
    }
}