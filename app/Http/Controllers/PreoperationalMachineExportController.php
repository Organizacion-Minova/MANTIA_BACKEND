<?php

namespace App\Http\Controllers;

use App\Services\PreoperationalMachineExportService;
use App\Exports\PreoperationalMachineExcelExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

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

        return $pdf->download("Preoperacional_{$machineName}_{$data['inspection']->week_start}.pdf");
    }

    public function preoperationalMachineExcel(int $id)
    {
        $data = $this->exportService->getExportData($id);
        $machineName = $data['machine']->name ?? 'Maquina';

        return Excel::download(
            new PreoperationalMachineExcelExport($data),
            "Preoperacional_{$machineName}_{$data['inspection']->week_start}.xlsx"
        );
    }
}
