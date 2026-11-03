<?php

namespace App\Console\Commands;

use App\Models\GasMeasurement;
use App\Services\AlertaService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Alerta preventiva de la medición diaria de gases.
 *
 * Se programa 3 veces al día (ver routes/console.php: 8 am, 11 am y 2 pm) y revisa:
 *  - No hay medición de hoy        → avisa y lleva a la pantalla de Gases.
 *  - El formato tiene campos vacíos → avisa qué falta y lleva directo al formato.
 *  - El formato está completo       → no hace nada.
 *
 * "Completo" significa que TODOS los campos están llenos: hora de ingreso, las dos
 * firmas y los 6 gases, el responsable y las observaciones de cada ubicación.
 */
class RecordarMedicionGases extends Command
{
    protected $signature = 'gases:recordar {aviso : 1 (8 am), 2 (11 am) o 3 (2 pm)}';

    protected $description = 'Avisa si falta la medición de gases del día o si el formato tiene campos sin llenar';

    /** Quienes pueden llenar el formato de gases. */
    private const ROLES = ['Jefe de mina', 'SST', 'Operario gases'];

    private const NIVELES = [1 => 'info', 2 => 'warning', 3 => 'danger'];

    private const PREFIJOS = [1 => 'Recordatorio', 2 => 'Segundo aviso', 3 => 'Último aviso'];

    public function handle(): int
    {
        $aviso = (int) $this->argument('aviso');

        if (!isset(self::NIVELES[$aviso])) {
            $this->error('El aviso debe ser 1, 2 o 3.');
            return self::INVALID;
        }

        // Mismo "hoy" que usa GasMeasurementController al crear la medición
        $hoy = Carbon::today()->toDateString();
        $medicion = GasMeasurement::whereDate('measurement_date', $hoy)->first();

        if (!$medicion) {
            $this->enviar($aviso, 'todavía no se ha registrado la medición de gases de hoy.', '/gases');
            $this->info("Aviso {$aviso} enviado: no hay medición de gases de {$hoy}.");
            return self::SUCCESS;
        }

        $pendientes = $medicion->pendientes();

        if (empty($pendientes['cabecera']) && empty($pendientes['ubicaciones'])) {
            $this->info("La medición de gases de {$hoy} está completa. No se envía el aviso {$aviso}.");
            return self::SUCCESS;
        }

        // El enlace lleva directo al formato para que el usuario complete lo que falta
        $this->enviar($aviso, $this->textoPendientes($pendientes), "/gases/gas_format/{$medicion->id}");
        $this->info("Aviso {$aviso} enviado: el formato de gases de {$hoy} tiene campos sin llenar.");
        return self::SUCCESS;
    }

    private function enviar(int $aviso, string $texto, string $enlace): void
    {
        AlertaService::porRol(
            self::ROLES,
            'gas',
            self::PREFIJOS[$aviso] . ': ' . $texto,
            self::NIVELES[$aviso],
            $enlace
        );
    }

    private function textoPendientes(array $pendientes): string
    {
        $faltan = [];

        if (!empty($pendientes['ubicaciones'])) {
            $nombres = array_slice($pendientes['ubicaciones'], 0, 3);
            $resto = count($pendientes['ubicaciones']) - count($nombres);
            $lista = implode(', ', $nombres) . ($resto > 0 ? " y {$resto} más" : '');

            $faltan[] = count($pendientes['ubicaciones']) . " de {$pendientes['total_ubicaciones']} ubicaciones sin diligenciar ({$lista})";
        }

        foreach ($pendientes['cabecera'] as $campo) {
            $faltan[] = $campo;
        }

        return 'la medición de gases de hoy está incompleta. Falta: ' . implode('; ', $faltan) . '. Entra al formato y complétalo.';
    }
}