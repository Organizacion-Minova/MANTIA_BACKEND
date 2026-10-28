<?php

namespace App\Console\Commands;

use App\Models\GasMeasurement;
use App\Services\AlertaService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Alerta preventiva de la medición diaria de gases.
 *
 * Se programa 3 veces al día (ver routes/console.php: 8 am, 11 am y 2 pm).
 * Solo envía la alerta si la medición de HOY todavía no se hizo; si ya se hizo,
 * no hace nada.
 *
 * Se considera "hecha" cuando existe el formato del día y al menos una ubicación
 * tiene algún valor de gas registrado (abrir el formato vacío no cuenta como medir).
 */
class RecordarMedicionGases extends Command
{
    protected $signature = 'gases:recordar {aviso : 1 (8 am), 2 (11 am) o 3 (2 pm)}';

    protected $description = 'Avisa que falta la medición de gases del día (solo si aún no se ha hecho)';

    private const ZONA_HORARIA = 'America/Bogota';

    private const AVISOS = [
        1 => [
            'nivel' => 'info',
            'mensaje' => 'Recordatorio: todavía no se ha registrado la medición de gases de hoy.',
        ],
        2 => [
            'nivel' => 'warning',
            'mensaje' => 'Segundo aviso: la medición de gases de hoy sigue sin registrarse.',
        ],
        3 => [
            'nivel' => 'danger',
            'mensaje' => 'Último aviso: no se ha registrado la medición de gases de hoy. Realizarla es urgente.',
        ],
    ];

    public function handle(): int
    {
        $aviso = (int) $this->argument('aviso');

        if (!isset(self::AVISOS[$aviso])) {
            $this->error('El aviso debe ser 1, 2 o 3.');
            return self::INVALID;
        }

        $hoy = Carbon::now(self::ZONA_HORARIA)->toDateString();

        if ($this->medicionHecha($hoy)) {
            $this->info("La medición de gases de {$hoy} ya se hizo. No se envía el aviso {$aviso}.");
            return self::SUCCESS;
        }

        AlertaService::porRol(
            'Administrador',
            'gas',
            self::AVISOS[$aviso]['mensaje'],
            self::AVISOS[$aviso]['nivel'],
            '/gases'
        );

        $this->info("Aviso {$aviso} enviado: falta la medición de gases de {$hoy}.");
        return self::SUCCESS;
    }

    private function medicionHecha(string $fecha): bool
    {
        $gases = array_keys(config('alertas.gas'));

        return GasMeasurement::whereDate('measurement_date', $fecha)
            ->whereHas('detail', function ($detalle) use ($gases) {
                $detalle->where(function ($q) use ($gases) {
                    foreach ($gases as $gas) {
                        $q->orWhereNotNull($gas);
                    }
                });
            })
            ->exists();
    }
}