<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Export;

class PreoperationalMachineMultipleSheetExport implements WithMultipleSheets, Export
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
            $sheets[] = new PreoperationalMachineExcelExport($inspeccion);
        }

        return $sheets;
    }
}