<?php

namespace App\Services;

use App\Models\Tools;

/**
 * Alertas de stock de herramientas.
 *
 * El stock de una herramienta está repartido en tool_stock_condition (bueno, regular y malo),
 * y esas filas se guardan una por una. Por eso la revisión se hace desde ToolsController
 * DESPUÉS de guardar las tres condiciones: así se compara el total real y no un valor a medias.
 */
class AlertaStockHerramienta
{
    /**
     * $antes = stock por condición antes de editar (['bueno' => 10, ...]); vacío al crear.
     */
    public static function revisar(Tools $herramienta, array $antes = []): void
    {
        $ahora = $herramienta->condiciones()->pluck('stock', 'condition')->map(fn ($n) => (int) $n)->all();
        $total = array_sum($ahora);
        $totalAntes = array_sum($antes);
        $minimo = (int) config('alertas.stock_minimo_herramienta');

        $grupo = $herramienta->categoria?->category_group_id;
        $enlace = (int) $grupo === 2 ? '/noconsumables/tools' : '/consumables/tools';

        // Stock bajo: al crear con poco stock, o al editar y bajar hasta el mínimo
        if ($total <= $minimo && (empty($antes) || $total < $totalAntes)) {
            AlertaService::todos(
                'herramienta',
                "Stock bajo de {$herramienta->name}: quedan {$total} unidades (mínimo {$minimo})",
                $total <= 0 ? 'danger' : 'warning',
                $enlace
            );
        }

        // Más unidades en mal estado que antes
        $malas = $ahora['malo'] ?? 0;
        if ($malas > ($antes['malo'] ?? 0)) {
            AlertaService::todos(
                'herramienta',
                "{$herramienta->name}: {$malas} unidades en mal estado",
                'warning',
                $enlace
            );
        }
    }
}