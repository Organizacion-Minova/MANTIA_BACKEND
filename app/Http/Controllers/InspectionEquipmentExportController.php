<?php

namespace App\Http\Controllers;

use App\Services\InspectionEquipmentExportService;
use App\Exports\InspectionEquipmentExcelExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

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

        return $pdf->download("Inspección_{$equipmentName}_{$data['inspection']->week_start}.pdf");
    }

    public function inspectionEquipmentExcel(int $id)
    {
        $data = $this->exportService->getExportData($id);
        $equipmentName = $data['equipment']->name ?? 'Equipo';

        return Excel::download(
            new InspectionEquipmentExcelExport($data),
            "Inspección_{$equipmentName}_{$data['inspection']->week_start}.xlsx"
        );
    }
}
