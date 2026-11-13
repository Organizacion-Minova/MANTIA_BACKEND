<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Export;

class InspectionEquipmentMultipleSheetExport implements WithMultipleSheets, Export
{
    protected $inspecciones;

    public function __construct($inspecciones)
    {
        $this->inspecciones = $inspecciones;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->inspecciones as $inspeccion) {
            $sheets[] = new InspectionEquipmentExcelExport($inspeccion);
        }

        return $sheets;
    }
}
