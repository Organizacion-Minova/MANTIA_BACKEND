<?php

namespace App\Http\Controllers;

use App\Services\InspectionEquipmentExportService;
use App\Exports\InspectionEquipmentExcelExport;
use App\Exports\InspectionEquipmentMultipleSheetExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class InspectionEquipmentExportController extends Controller
{
    protected $exportService;

    public function __construct(InspectionEquipmentExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function inspectionEquipmentPdf(int $id)
    {
        $data = $this->exportService->getExportData($id);
        $equipmentName = $data['equipment']->name ?? 'Equipo';

        $pdf = Pdf::loadView('exports.InspectionEquipmentPdf', $data);

        return $pdf->download("Inspección {$equipmentName} {$data['inspection']->week_start}.pdf");
    }

    public function inspectionEquipmentExcel(int $id)
    {
        $data = $this->exportService->getExportData($id);
        $equipmentName = $data['equipment']->name ?? 'Equipo';

        return Excel::download(
            new InspectionEquipmentExcelExport($data),
            "Inspección {$equipmentName} {$data['inspection']->week_start}.xlsx"
        );
    }

    public function rangeInspectionEquipmentExcel(Request $request, int $equipmentId)
    {
        $request->validate([
            'week_start' => 'required|date',
            'week_end' => 'required|date|after_or_equal:week_start',
        ]);

        $diasDiferencia = \Carbon\Carbon::parse($request->week_start)->diffInDays(\Carbon\Carbon::parse($request->week_end));
        if ($diasDiferencia > 28) {
            return response()->json(['message' => 'El rango de fechas no puede superar las 4 semanas.'], 422);
        }

        $inspecciones = $this->exportService->getExportDataRange($equipmentId, $request->week_start, $request->week_end);

        if ($inspecciones->isEmpty()) {
            return response()->json(['message' => 'No hay registros en el rango seleccionado.'], 404);
        }

        return Excel::download(
            new InspectionEquipmentMultipleSheetExport($inspecciones),
            "Inspecciones {$request->week_start} al {$request->week_end}.xlsx"
        );
    }

    public function rangeInspectionEquipmentPdf(Request $request, int $equipmentId)
    {
        $request->validate([
            'week_start' => 'required|date',
            'week_end' => 'required|date|after_or_equal:week_start',
        ]);
        $diasDiferencia = \Carbon\Carbon::parse($request->week_start)->diffInDays(\Carbon\Carbon::parse($request->week_end));
        if ($diasDiferencia > 28) {
            return response()->json(['message' => 'El rango de fechas no puede superar las 4 semanas.'], 422);
        }

        $inspecciones = $this->exportService->getExportDataRange($equipmentId, $request->week_start, $request->week_end);

        if ($inspecciones->isEmpty()) {
            return response()->json(['message' => 'No hay registros en el rango seleccionado.'], 404);
        }

        $pdf = Pdf::loadView('exports.InspectionEquipmentMultiplePdf', ['inspecciones' => $inspecciones]);

        return $pdf->download("Inspecciones {$request->week_start} al {$request->week_end}.pdf");
    }
}
