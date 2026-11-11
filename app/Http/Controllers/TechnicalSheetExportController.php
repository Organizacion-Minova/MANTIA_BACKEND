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
        return $pdf->download("Ficha técnica {$equipment->name}.pdf");
    }

    public function equipmentExcel($id)
    {
        $equipment=Equipment::with(['ubicacion'])->findOrFail($id);
        return Excel::download(new EquipmentExcelExport($equipment), "Ficha técnica {$equipment->name}.xlsx");
    }

    public function machinePdf($id)
    {
        $machine=Machine::with(['ubicacion', 'consumables'])->findOrFail($id);
        $pdf = Pdf::loadView('exports.MachineTechnicalPdf', compact('machine'));
        return $pdf->download("Ficha técnica {$machine->name}.pdf");
    }

    public function machineExcel($id)
    {
        $machine=Machine::with(['ubicacion', 'consumables'])->findOrFail($id);
        return Excel::download(new MachineExcelExport($machine), "Ficha técnica {$machine->name}.xlsx");
    }
}
