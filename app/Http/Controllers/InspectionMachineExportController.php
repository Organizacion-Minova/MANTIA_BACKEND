<?php

namespace App\Http\Controllers;

use App\Services\InspectionMachineExportService;
use App\Exports\InspectionMachineExcelExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

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

        return $pdf->download("Inspección_{$machineName}_{$data['inspection']->inspection_date}.pdf");
    }

    public function inspectionMachineExcel(int $id)
    {
        $data = $this->exportService->getExportData($id);
        $machineName = $data['machine']->name ?? 'Maquina';

        return Excel::download(
            new InspectionMachineExcelExport($data),
            "Inspección_{$machineName}_{$data['inspection']->inspection_date}.xlsx"
        );
    }
}
