<?php

namespace App\Http\Controllers;

use App\Exports\LocationInventoryExcelExport;
use App\Models\Location;
use App\Services\LocationInventoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Exporta a Excel y PDF el inventario de una ubicación (lo mismo que muestra su ficha).
 * Cada usuario solo recibe las secciones de los módulos a los que tiene acceso.
 */
class LocationExportController extends Controller
{
    /** Mediciones de gases incluidas en los archivos (la ficha en pantalla muestra solo las últimas 5). */
    private const MAX_MEDICIONES_GAS = 50;

    protected $service;

    public function __construct(LocationInventoryService $service)
    {
        $this->service = $service;
    }

    public function inventarioExcel(Request $request, $id)
    {
        $ubicacion = Location::findOrFail($id);

        try {
            $datos = $this->service->obtener($request->user(), $ubicacion, self::MAX_MEDICIONES_GAS);

            return Excel::download(
                new LocationInventoryExcelExport($datos, $request->user()),
                $this->nombreArchivo($ubicacion, 'xlsx')
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar Excel del inventario', 'message' => $e->getMessage()], 500);
        }
    }

    public function inventarioPdf(Request $request, $id)
    {
        $ubicacion = Location::findOrFail($id);

        try {
            $datos = $this->service->obtener($request->user(), $ubicacion, self::MAX_MEDICIONES_GAS);
            $usuario = $request->user();

            $pdf = Pdf::loadView('exports.LocationInventoryPdf', [
                'datos' => $datos,
                'generadoPor' => trim($usuario->name . ' ' . ($usuario->last_name ?? ''))
                    . ' - ' . Carbon::now(config('alertas.zona_horaria', 'America/Bogota'))->format('d/m/Y H:i'),
            ])->setPaper('letter', 'landscape');

            return $pdf->download($this->nombreArchivo($ubicacion, 'pdf'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar PDF del inventario', 'message' => $e->getMessage()], 500);
        }
    }

    private function nombreArchivo(Location $ubicacion, string $extension): string
    {
        return 'Inventario_' . Str::slug($ubicacion->name, '_') . ".{$extension}";
    }
}