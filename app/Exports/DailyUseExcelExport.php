<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class DailyUseExcelExport implements FromArray, WithEvents, WithColumnWidths, WithDrawings, WithTitle
{
    protected $machine;
    protected $records;

    public function __construct($machine, $records)
    {
        $this->machine = $machine;
        $this->records = $records;
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Uso diario';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 26,
            'C' => 16,
            'D' => 16,
            'E' => 26,
            'F' => 34,
        ];
    }

    public function drawings(): array|BaseDrawing
    {
        $path = public_path('images/sena-logo.png');
        if (!is_file($path)) {
            return [];
        }
        $drawing = new Drawing();
        $drawing->setName('SENA');
        $drawing->setDescription('SENA');
        $drawing->setPath($path);
        $drawing->setResizeProportional(true);
        $drawing->setHeight(45);
        $drawing->setCoordinates('D1');
        $drawing->setOffsetX(40);
        $drawing->setOffsetY(0);
        return [$drawing];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(8)->getColor()->setRGB('000000');
                $sheet->setShowGridlines(false);
                $sheet->getRowDimension(1)->setRowHeight(36);

                $sheet->mergeCells('A2:F2');
                $sheet->setCellValueExplicit('A2', 'REGISTRO DIARIO DE USO DE MAQUINARIA Y EQUIPOS MINA DIDÁCTICA', DataType::TYPE_STRING);

                $sheet->mergeCells('A3:B3');
                $sheet->mergeCells('C3:F3');
                $sheet->setCellValueExplicit('A3', 'NOMBRE MÁQUINA:', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('C3', (string) $this->machine->name, DataType::TYPE_STRING);

                $headers = [
                    'A' => 'Fecha',
                    'B' => 'Verificación de estado de funcionamiento de la máquina',
                    'C' => 'Inicio de operación',
                    'D' => 'Fin de operación',
                    'E' => 'Responsable a cargo',
                    'F' => 'Observaciones',
                ];

                foreach ($headers as $col => $text) {
                    $sheet->setCellValueExplicit("{$col}4", $text, DataType::TYPE_STRING);
                }

                $sheet->getRowDimension(2)->setRowHeight(21);
                $sheet->getRowDimension(3)->setRowHeight(21);
                $sheet->getRowDimension(4)->setRowHeight(30);

                $row = 5;
                if ($this->records->isEmpty()) {
                    $sheet->mergeCells("A5:F5");
                    $sheet->setCellValueExplicit('A5', 'No hay registros de uso diario para esta máquina.', DataType::TYPE_STRING);
                    $sheet->getRowDimension(5)->setRowHeight(22);
                } else {
                    foreach ($this->records as $r) {
                        $sheet->setCellValueExplicit("A{$row}", (string) $r->date, DataType::TYPE_STRING);
                        $sheet->setCellValueExplicit("B{$row}", (string) $r->operating_status, DataType::TYPE_STRING);
                        $sheet->setCellValueExplicit("C{$row}", (string) $r->operation_start, DataType::TYPE_STRING);
                        $sheet->setCellValueExplicit("D{$row}", (string) $r->operation_end, DataType::TYPE_STRING);
                        $sheet->setCellValueExplicit("E{$row}", (string) $r->responsible, DataType::TYPE_STRING);
                        $sheet->setCellValueExplicit("F{$row}", (string) ($r->observations ?? 'Ninguna'), DataType::TYPE_STRING);
                        
                        $sheet->getRowDimension($row)->setRowHeight(22);
                        $row++;
                    }
                }

                $last_row = max(5, $row - 1);
                $sheet->getStyle("A2:F{$last_row}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT)->setPaperSize(PageSetup::PAPERSIZE_A4);
            },
        ];
    }
}