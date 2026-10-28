<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Machine;
use App\Exports\EquipmentExcelExport;
use App\Exports\MachineExcelExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class TechnicalSheetExportController extends Controller
{

    public function equipmentPdf($id)
    {
        $equipment=Equipment::with(['ubicacion'])->findOrFail($id);
        $pdf = Pdf::loadView('exports.EquipmentTechnicalPdf', compact('equipment'));
        return $pdf->download("Ficha_técnica_{$equipment->name}.pdf");
    }

    public function equipmentExcel($id)
    {
        $equipment=Equipment::with(['ubicacion'])->findOrFail($id);
        return Excel::download(new EquipmentExcelExport($equipment), "Ficha_técnica_{$equipment->name}.xlsx");
    }

    public function machinePdf($id)
    {
        $machine=Machine::with(['ubicacion','categorymaquina'])->findOrFail($id);
        $pdf = Pdf::loadView('exports.MachineTechnicalPdf', compact('machine'));
        return $pdf->download("Ficha_técnica_{$machine->name}.pdf");
    }

    public function machineExcel($id)
    {
        $machine=Machine::with(['ubicacion','categorymaquina'])->findOrFail($id);
        return Excel::download(new MachineExcelExport($machine), "Ficha_técnica_{$machine->name}.xlsx");
    }
}
