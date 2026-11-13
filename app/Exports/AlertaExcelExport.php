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
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AlertaExcelExport implements FromArray, WithEvents, WithColumnWidths, WithDrawings, WithTitle
{
    protected $usuario;
    protected $alertas;
    protected string $fechaInicio;
    protected string $fechaFin;
    protected ?string $nivel;

    private const COLOR_NIVEL = [
        'danger' => 'F8D7DA',
        'warning' => 'FFF3CD',
        'success' => 'D4EDDA',
        'info' => 'D5F3EE',
    ];

    public function __construct($usuario, $alertas, string $fechaInicio, string $fechaFin, ?string $nivel = null)
    {
        $this->usuario = $usuario;
        $this->alertas = $alertas;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->nivel = $nivel;
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Alertas';
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 10, 'C' => 22, 'D' => 16, 'E' => 70, 'F' => 14];
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
        $drawing->setCoordinates('E1');
        $drawing->setOffsetX(230);
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
                $sheet->setCellValueExplicit('A2', 'REPORTE DE ALERTAS DEL SISTEMA MANTIA', DataType::TYPE_STRING);

                $sheet->mergeCells('A3:B3');
                $sheet->mergeCells('C3:F3');
                $sheet->setCellValueExplicit('A3', 'USUARIO:', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('C3', trim($this->usuario->name . ' ' . ($this->usuario->last_name ?? '')), DataType::TYPE_STRING);

                $sheet->mergeCells('A4:B4');
                $sheet->mergeCells('C4:F4');
                $sheet->setCellValueExplicit('A4', 'PERIODO:', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('C4', $this->formatearFecha($this->fechaInicio) . ' al ' . $this->formatearFecha($this->fechaFin), DataType::TYPE_STRING);

                $sheet->mergeCells('A5:B5');
                $sheet->mergeCells('C5:F5');
                $sheet->setCellValueExplicit('A5', 'NIVEL:', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('C5', $this->nivel ? (\App\Services\AlertaExportService::NIVELES[$this->nivel] ?? $this->nivel) : 'Todos', DataType::TYPE_STRING);

                $headers = ['A' => 'Fecha', 'B' => 'Hora', 'C' => 'Categoría', 'D' => 'Nivel', 'E' => 'Mensaje', 'F' => 'Estado'];
                foreach ($headers as $col => $text) {
                    $sheet->setCellValueExplicit("{$col}6", $text, DataType::TYPE_STRING);
                }

                $sheet->getRowDimension(2)->setRowHeight(21);
                foreach ([3, 4, 5] as $fila) {
                    $sheet->getRowDimension($fila)->setRowHeight(18);
                }
                $sheet->getRowDimension(6)->setRowHeight(24);

                $row = 7;
                if ($this->alertas->isEmpty()) {
                    $sheet->mergeCells('A7:F7');
                    $sheet->setCellValueExplicit('A7', 'No hay alertas en el periodo seleccionado.', DataType::TYPE_STRING);
                    $sheet->getRowDimension(7)->setRowHeight(22);
                    $row = 8;
                } else {
                    foreach ($this->alertas as $a) {
                        $sheet->setCellValueExplicit("A{$row}", (string) $a['fecha'], DataType::TYPE_STRING);
                        $sheet->setCellValueExplicit("B{$row}", (string) $a['hora'], DataType::TYPE_STRING);
                        $sheet->setCellValueExplicit("C{$row}", (string) $a['categoria'], DataType::TYPE_STRING);
                        $sheet->setCellValueExplicit("D{$row}", (string) $a['nivel'], DataType::TYPE_STRING);
                        $sheet->setCellValueExplicit("E{$row}", (string) $a['mensaje'], DataType::TYPE_STRING);
                        $sheet->setCellValueExplicit("F{$row}", (string) $a['estado'], DataType::TYPE_STRING);

                        $sheet->getStyle("D{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB(self::COLOR_NIVEL[$a['nivel_clave']] ?? 'FFFFFF');

                        $sheet->getRowDimension($row)->setRowHeight(22);
                        $row++;
                    }
                }

                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->setCellValueExplicit("A{$row}", 'TOTAL DE ALERTAS:', DataType::TYPE_STRING);
                $sheet->mergeCells("E{$row}:F{$row}");
                $sheet->setCellValueExplicit("E{$row}", (string) $this->alertas->count(), DataType::TYPE_STRING);
                $sheet->getRowDimension($row)->setRowHeight(20);
                $last_row = $row;

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

                if ($this->alertas->isNotEmpty()) {
                    $sheet->getStyle('E7:E' . ($last_row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                $sheet->getStyle('A2:F2')->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('A3:A5')->getFont()->setBold(true);
                $sheet->getStyle('A6:F6')->getFont()->setBold(true);
                $sheet->getStyle("A{$last_row}:F{$last_row}")->getFont()->setBold(true);

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToPage(true)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(6, 6);
            },
        ];
    }

    private function formatearFecha(string $fecha): string
    {
        return \Carbon\Carbon::parse($fecha)->format('d/m/Y');
    }
}