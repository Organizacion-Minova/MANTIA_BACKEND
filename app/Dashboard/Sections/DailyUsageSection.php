<?php

namespace App\Dashboard\Sections;

use App\Dashboard\DashboardSection;
use App\Dashboard\SectionResult;
use Illuminate\Support\Facades\DB;

class DailyUsageSection implements DashboardSection
{
    public function key(): string
    {
        return 'dailyUsage';
    }

    public function resolve(): SectionResult

    
    {
        $datesByMachine = DB::table('daily_use')
            ->select('machine_id')
            ->selectRaw('MIN(date) as first_date, MAX(date) as last_date, COUNT(*) as record_count')
            ->groupBy('machine_id');

        $machines = DB::table('machine as m')
            ->leftJoinSub($datesByMachine, 'usage_dates', 'usage_dates.machine_id', '=', 'm.id')
            ->leftJoin('daily_use as first_usage', function ($join) {
                $join->on('first_usage.machine_id', '=', 'm.id')
                    ->on('first_usage.date', '=', 'usage_dates.first_date');
            })
            ->leftJoin('daily_use as last_usage', function ($join) {
                $join->on('last_usage.machine_id', '=', 'm.id')
                    ->on('last_usage.date', '=', 'usage_dates.last_date');
            })
            ->select([
                'm.id as machine_id',
                'm.name as machine_name',
                'first_usage.date as first_date',
                'last_usage.date as last_date',
                'usage_dates.record_count',
            ])
            ->orderBy('m.name')
            ->get()
            ->map(fn ($machine) => [
                'id' => (int) $machine->machine_id,
                'name' => $machine->machine_name,
                'recordCount' => (int) ($machine->record_count ?? 0),
                'firstRecord' => $machine->first_date === null ? null : [
                    'date' => $machine->first_date,
                ],
                'lastRecord' => $machine->last_date === null ? null : [
                    'date' => $machine->last_date,
                ],
            ])
            ->all();

        return $machines === []
            ? SectionResult::empty([])
            : SectionResult::ok($machines);
    }
}
