<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GasMeasurementExportService;
use App\Exports\GasMeasurementExcelExport;
use App\Exports\GasMeasurementMultipleSheetExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class GasMeasurementExportController extends Controller
{
    protected $service;

    public function __construct(GasMeasurementExportService $service)
    {
        $this->service = $service;
    }

    public function unitaryGasExcel($id)
    {
        $medicion = $this->service->obtenerDatosUnitarios($id);
        return Excel::download(new GasMeasurementExcelExport($medicion), "Medición gases {$id}.xlsx");
    }

    public function rangeGasExcel(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ], [
            'fecha_inicio.date' => 'La fecha inicio no es válida.',
            'fecha_fin.date' => 'La fecha fin no es válida.',
            'fecha_fin.after_or_equal' => 'La fecha fin no puede ser antes de la fecha de inicio.',
        ]);

        try {
            $mediciones = $this->service->obtenerDatosRango($request->fecha_inicio, $request->fecha_fin);
        
            if ($mediciones->isEmpty()) {
                return response()->json(['message' => 'No hay registros en el rango seleccionado.'], 404);
            }

            return Excel::download(new GasMeasurementMultipleSheetExport($mediciones), "Medición gases {$request->fecha_inicio} al {$request->fecha_fin}.xlsx");
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar Excel por rango', 'message' => $e->getMessage()], 500);
        }
    }

    public function unitaryGasPdf($id)
    {
        $medicion = $this->service->obtenerDatosUnitarios($id);
        $pdf = Pdf::loadView('exports.GasMeasurementPdf', ['medicion' => $medicion]);
        
        return $pdf->download("Medición gases {$id}.pdf");
    }

    public function rangeGasPdf(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ], [
            'fecha_inicio.date' => 'La fecha inicio no es válida.',
            'fecha_fin.date' => 'La fecha fin no es válida.',
            'fecha_fin.after_or_equal' => 'La fecha fin no puede ser antes de la fecha de inicio.',
        ]);

        try {
            $mediciones = $this->service->obtenerDatosRango($request->fecha_inicio, $request->fecha_fin);

            if ($mediciones->isEmpty()) {
                return response()->json(['message' => 'No hay registros en el rango seleccionado.'], 404);
            }

            $pdf = Pdf::loadView('exports.GasMeasurementMultipleSheet', ['mediciones' => $mediciones]);
           
            return $pdf->download("Medición gases {$request->fecha_inicio} al {$request->fecha_fin}.pdf");
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar PDF por rango', 'message' => $e->getMessage()], 500);
        }
    }
}