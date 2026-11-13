<?php

namespace App\Http\Controllers;

use App\Services\PreoperationalMachineExportService;
use App\Exports\PreoperationalMachineExcelExport;
use App\Exports\PreoperationalMachineMultipleSheetExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class PreoperationalMachineExportController extends Controller
{
    protected $exportService;

    public function __construct(PreoperationalMachineExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function preoperationalMachinePdf(int $id)
    {
        $data = $this->exportService->getExportData($id);
        $machineName = $data['machine']->name ?? 'Maquina';
    
        $pdf = Pdf::loadView('exports.PreoperationalMachinePdf', $data);

        return $pdf->download("Preoperacional {$machineName} {$data['inspection']->week_start}.pdf");
    }

    public function preoperationalMachineExcel(int $id)
    {
        $data = $this->exportService->getExportData($id);
        $machineName = $data['machine']->name ?? 'Maquina';

        return Excel::download(
            new PreoperationalMachineExcelExport($data),
            "Preoperacional {$machineName} {$data['inspection']->week_start}.xlsx"
        );
    }

    public function rangePreoperationalMachineExcel(Request $request, int $machineId)
    {
        $request->validate([
            'week_start' => 'required|date',
            'week_end' => 'required|date|after_or_equal:week_start',
        ]);

        $diasDiferencia = \Carbon\Carbon::parse($request->week_start)->diffInDays(\Carbon\Carbon::parse($request->week_end));
        if ($diasDiferencia > 28) {
            return response()->json(['message' => 'El rango de fechas no puede superar las 4 semanas.'], 422);
        }

        $inspecciones = $this->exportService->getExportDataRange($machineId, $request->week_start, $request->week_end);

        if ($inspecciones->isEmpty()) {
            return response()->json(['message' => 'No hay registros en el rango seleccionado.'], 404);
        }

        return Excel::download(
            new PreoperationalMachineMultipleSheetExport($inspecciones),
            "Preoperacionales {$request->week_start} al {$request->week_end}.xlsx"
        );
    }

    public function rangePreoperationalMachinePdf(Request $request, int $machineId)
    {
        $request->validate([
            'week_start' => 'required|date',
            'week_end' => 'required|date|after_or_equal:week_start',
        ]);

        $diasDiferencia = \Carbon\Carbon::parse($request->week_start)->diffInDays(\Carbon\Carbon::parse($request->week_end));
        if ($diasDiferencia > 28) {
            return response()->json(['message' => 'El rango de fechas no puede superar las 4 semanas.'], 422);
        }

        $inspecciones = $this->exportService->getExportDataRange($machineId, $request->week_start, $request->week_end);

        if ($inspecciones->isEmpty()) {
            return response()->json(['message' => 'No hay registros en el rango seleccionado.'], 404);
        }

        $pdf = Pdf::loadView('exports.PreoperationalMachineMultiplePdf', ['inspecciones' => $inspecciones]);

        return $pdf->download("Preoperacionales {$request->week_start} al {$request->week_end}.pdf");
    }
}
