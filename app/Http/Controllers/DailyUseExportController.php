<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\DailyUse;
use App\Exports\DailyUseExcelExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class DailyUseExportController extends Controller
{
    public function dailyUsePdf($id)
    {
        $machine = Machine::findOrFail($id);
        $records = DailyUse::where('machine_id', $id)->get();

        $pdf = Pdf::loadView('exports.DailyUsePdf', compact('machine', 'records'));
        return $pdf->download("Uso_diario_{$machine->name}.pdf");
    }

    public function dailyUseExcel($id)
    {
        $machine = Machine::findOrFail($id);
        $records = DailyUse::where('machine_id', $id)->get();

        return Excel::download(new DailyUseExcelExport($machine, $records), "Uso_diario_{$machine->name}.xlsx");
    }
}