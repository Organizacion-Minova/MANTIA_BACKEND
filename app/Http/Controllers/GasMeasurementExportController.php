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

    public function exportarUnitarioExcel($id)
    {
        try {
            $medicion = $this->service->obtenerDatosUnitarios($id);
            return Excel::download(new GasMeasurementExcelExport($medicion), "medicion_gases_{$id}.xlsx");
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
    }

    public function exportarRangoExcel(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        try {
            $mediciones = $this->service->obtenerDatosRango($request->fecha_inicio, $request->fecha_fin);
        
            if ($mediciones->isEmpty()) {
                return response()->json(['message' => 'No hay registros en el rango seleccionado.'], 404);
            }

            return Excel::download(new GasMeasurementMultipleSheetExport($mediciones), "medicion_gases_{$request->fecha_inicio}_al_{$request->fecha_fin}.xlsx");
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar Excel por rango', 'message' => $e->getMessage()], 500);
        }
    }

    public function exportarUnitarioPdf($id)
    {
        try {
            $medicion = $this->service->obtenerDatosUnitarios($id);
            $pdf = Pdf::loadView('exports.GasMeasurementPdf', ['medicion' => $medicion])
                      ->setPaper('letter', 'portrait');
           
            return $pdf->download("medicion_gases_{$id}.pdf");
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar PDF Unitario', 'message' => $e->getMessage()], 500);
        }
    }

    public function exportarRangoPdf(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        try {
            $mediciones = $this->service->obtenerDatosRango($request->fecha_inicio, $request->fecha_fin);

            if ($mediciones->isEmpty()) {
                return response()->json(['message' => 'No hay registros en el rango seleccionado.'], 404);
            }

            $pdf = Pdf::loadView('exports.GasMeasurementMultipleSheet', ['mediciones' => $mediciones])
                      ->setPaper('letter', 'portrait');
           
            return $pdf->download("medicion_gases_{$request->fecha_inicio}_al_{$request->fecha_fin}.pdf");
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar PDF por rango', 'message' => $e->getMessage()], 500);
        }
    }
}