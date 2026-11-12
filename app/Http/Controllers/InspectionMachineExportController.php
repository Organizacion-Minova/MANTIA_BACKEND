<?php

namespace App\Http\Controllers;

use App\Services\InspectionMachineExportService;
use App\Exports\InspectionMachineExcelExport;
use App\Exports\InspectionMachineMultipleSheetExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class InspectionMachineExportController extends Controller
{
    protected $exportService;

    public function __construct(InspectionMachineExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function inspectionMachinePdf(int $id)
    {
        $data = $this->exportService->getExportData($id);
        $machineName = $data['machine']->name ?? 'Maquina';

        $pdf = Pdf::loadView('exports.InspectionMachinePdf', $data);

        return $pdf->download("Inspección {$machineName} {$data['inspection']->inspection_date}.pdf");
    }

    public function inspectionMachineExcel(int $id)
    {
        $data = $this->exportService->getExportData($id);
        $machineName = $data['machine']->name ?? 'Maquina';

        return Excel::download(
            new InspectionMachineExcelExport($data),
            "Inspección {$machineName} {$data['inspection']->inspection_date}.xlsx"
        );
    }

    public function rangeInspectionMachineExcel(Request $request, int $machineId)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $diasDiferencia = \Carbon\Carbon::parse($request->fecha_inicio)->diffInDays(\Carbon\Carbon::parse($request->fecha_fin));
        if ($diasDiferencia > 31) {
            return response()->json(['message' => 'El rango de fechas no puede superar los 31 días.'], 422);
        }

        $inspecciones = $this->exportService->getExportDataRange($machineId, $request->fecha_inicio, $request->fecha_fin);

        if ($inspecciones->isEmpty()) {
            return response()->json(['message' => 'No hay registros en el rango seleccionado.'], 404);
        }

        return Excel::download(
            new InspectionMachineMultipleSheetExport($inspecciones),
            "Inspecciones {$request->fecha_inicio} al {$request->fecha_fin}.xlsx"
        );
    }

    public function rangeInspectionMachinePdf(Request $request, int $machineId)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);
        $diasDiferencia = \Carbon\Carbon::parse($request->fecha_inicio)->diffInDays(\Carbon\Carbon::parse($request->fecha_fin));
        if ($diasDiferencia > 31) {
            return response()->json(['message' => 'El rango de fechas no puede superar los 31 días.'], 422);
        }

        $inspecciones = $this->exportService->getExportDataRange($machineId, $request->fecha_inicio, $request->fecha_fin);

        if ($inspecciones->isEmpty()) {
            return response()->json(['message' => 'No hay registros en el rango seleccionado.'], 404);
        }

        $pdf = Pdf::loadView('exports.InspectionMachineMultiplePdf', ['inspecciones' => $inspecciones]);

        return $pdf->download("Inspecciones {$request->fecha_inicio} al {$request->fecha_fin}.pdf");
    }

}
