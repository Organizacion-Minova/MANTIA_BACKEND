<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Export;

class GasMeasurementMultipleSheetExport implements WithMultipleSheets, Export
{
    protected $mediciones;

    public function __construct($mediciones)
    {
        $this->mediciones = $mediciones;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->mediciones as $medicion) {
            $sheets[] = new GasMeasurementExcelExport($medicion);
        }

        return $sheets;
    }
}