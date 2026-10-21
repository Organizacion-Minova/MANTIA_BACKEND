<?php

namespace App\Dashboard;
//el corazon de los conteos, de las cards princiales del dashboard. Se encarga de armar la forma de salida de cada sección, con su total, breakdown y highlights.
class SummaryCard
{
    /**
     * @param  array<string, int|string>  $counts  conteo por estado (clave = valor de origen)
     * @param  array<string, array{0: string, 1: string, 2: string}>  $states  origen => [key, etiqueta, status]
     * @param  array<int, array{key: string, value: int}>  $highlights
     */
    public static function result(
        array $counts,
        array $states,
        array $highlights = [],
        ?int $total = null,
    ): SectionResult {
        $breakdown = [];
        foreach ($states as $source => [$key, $label, $status]) {
            $breakdown[] = [
                'key'    => $key,
                'label'  => $label,
                'value'  => (int) ($counts[$source] ?? 0),
                'status' => $status,
            ];
        }

        $data = [
            'total'      => $total ?? (int) array_sum($counts),
            'breakdown'  => $breakdown,
            'highlights' => $highlights,
        ];

        return $data['total'] === 0
            ? SectionResult::empty($data)
            : SectionResult::ok($data);
    }
}