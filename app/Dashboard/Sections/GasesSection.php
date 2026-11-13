<?php

namespace App\Dashboard\Sections;

use App\Dashboard\DashboardSection;
use App\Dashboard\SectionResult;
use Illuminate\Support\Facades\DB;

class GasesSection implements DashboardSection
{//lista para el orden del carrusel
    private const LOCATION_ORDER = [
        'tunel 1',
        'tunel 2',
        'tunel 3',
        'cruzada de galeria 1',
        'galeria 2 centro',
        'galeria 2 norte',
        'galeria 2 sur',
        'transversal norte',
        'transversal sur',
        'galeria 3 centro',
        'galeria 3 sur',
        'galeria 3 norte',
        'cruzada de galeria 33',
        'diagonal norte',
        'diagonal sur',
        'galeria 4',
    ];

    public function key(): string
    {
        return 'gases';
    }

    public function resolve(): SectionResult
    {
        $fields = [
            'oxygen',
            'methane',
            'carbon_dioxide',
            'hydrogen_sulfide',
            'carbon_monoxide',
            'nitrogen_dioxide',
            'responsible_name',
            'observation',
        ];

        $locationOrder = array_flip(self::LOCATION_ORDER);

        $latestByLocation = DB::table('gas_measurement_detail as detail')
            ->join('gas_measurement as measurement', 'measurement.id', '=', 'detail.gas_measurement_id')
            ->join('location', 'location.id', '=', 'detail.location_id')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('gas_measurement_detail as newer_detail')
                    ->join('gas_measurement as newer_measurement', 'newer_measurement.id', '=', 'newer_detail.gas_measurement_id')
                    ->whereColumn('newer_detail.location_id', 'detail.location_id')
                    ->where(function ($query) {
                        $query->whereColumn('newer_measurement.measurement_date', '>', 'measurement.measurement_date')
                            ->orWhere(function ($query) {
                                $query->whereColumn('newer_measurement.measurement_date', '=', 'measurement.measurement_date')
                                    ->where(function ($query) {
                                        $query->where(function ($query) {
                                            $query->whereNotNull('newer_measurement.measurement_time')
                                                ->whereNull('measurement.measurement_time');
                                        })
                                            ->orWhereColumn('newer_measurement.measurement_time', '>', 'measurement.measurement_time')
                                            ->orWhere(function ($query) {
                                                $query->where(function ($query) {
                                                    $query->whereColumn('newer_measurement.measurement_time', '=', 'measurement.measurement_time')
                                                        ->orWhere(function ($query) {
                                                            $query->whereNull('newer_measurement.measurement_time')
                                                                ->whereNull('measurement.measurement_time');
                                                        });
                                                })
                                                    ->whereColumn('newer_measurement.id', '>', 'measurement.id');
                                            });
                                    });
                            });
                    });
            })
            ->select([
                'location.id as location_id',
                'location.name as location_name',
                'measurement.id as measurement_id',
                'measurement.measurement_date',
                'measurement.measurement_time',
                ...array_map(fn (string $field) => "detail.{$field}", $fields),
            ])
            ->orderBy('location.name')
            ->get()
            ->map(function ($row) use ($fields) {
                $detail = [];
                foreach ($fields as $field) {
                    $detail[$field] = $row->{$field};
                }

                return [
                    'id' => (int) $row->location_id,
                    'name' => $row->location_name,
                    'measurement' => [
                        'id' => (int) $row->measurement_id,
                        'date' => $row->measurement_date,
                        'time' => $row->measurement_time,
                    ],
                    'detail' => $detail,
                ];
            })
            ->all();

        usort($latestByLocation, function (array $left, array $right) use ($locationOrder) {
            $leftOrder = $locationOrder[strtolower($left['name'])] ?? count($locationOrder);
            $rightOrder = $locationOrder[strtolower($right['name'])] ?? count($locationOrder);

            return ($leftOrder <=> $rightOrder) ?: strcasecmp($left['name'], $right['name']);
        });

        return $latestByLocation === []
            ? SectionResult::empty([])
            : SectionResult::ok($latestByLocation);
    }
}
