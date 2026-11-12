<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * Una hoja del Excel de inventario de una ubicación (Resumen, Máquinas, Equipos, ...).
 * Todas las hojas comparten el mismo encabezado (logo, título, ubicación, quién y cuándo).
 */
class LocationInventorySheetExport implements FromArray, WithEvents, WithColumnWidths, WithDrawings, WithTitle
{
    /**
     * @param  string  $hoja  nombre de la pestaña (máx. 31 caracteres)
     * @param  array<string,int>  $columnas  encabezado => ancho
     * @param  array<int,array<int,string>>  $filas
     * @param  callable|null  $colorCelda  fn(string $valor): ?string  color de fondo (RGB) según el texto
     */
    public function __construct(
        protected string $hoja,
        protected string $ubicacion,
        protected string $generadoPor,
        protected array $columnas,
        protected array $filas,
        protected string $mensajeVacio,
        protected $colorCelda = null,
    ) {
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return $this->hoja;
    }

    public function columnWidths(): array
    {
        $anchos = [];
        foreach (array_values($this->columnas) as $i => $ancho) {
            $anchos[Coordinate::stringFromColumnIndex($i + 1)] = $ancho;
        }

        return $anchos;
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
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(0);
        return [$drawing];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $total = count($this->columnas);
                $ultima = Coordinate::stringFromColumnIndex($total);

                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(8)->getColor()->setRGB('000000');
                $sheet->setShowGridlines(false);
                $sheet->getRowDimension(1)->setRowHeight(36);

                // Título y datos del encabezado: la etiqueta va en A y el valor en el resto de la fila
                $sheet->mergeCells("A2:{$ultima}2");
                $sheet->setCellValueExplicit('A2', 'INVENTARIO DE UBICACIÓN - ' . mb_strtoupper($this->hoja), DataType::TYPE_STRING);

                foreach ([3 => ['UBICACIÓN:', $this->ubicacion], 4 => ['GENERADO POR:', $this->generadoPor]] as $fila => [$etiqueta, $valor]) {
                    $sheet->setCellValueExplicit("A{$fila}", $etiqueta, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("B{$fila}", $valor, DataType::TYPE_STRING);
                    if ($total > 2) {
                        $sheet->mergeCells("B{$fila}:{$ultima}{$fila}");
                    }
                    $sheet->getRowDimension($fila)->setRowHeight(18);
                }

                // Encabezados de la tabla
                foreach (array_keys($this->columnas) as $i => $texto) {
                    $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($i + 1) . '5', $texto, DataType::TYPE_STRING);
                }
                $sheet->getRowDimension(2)->setRowHeight(21);
                $sheet->getRowDimension(5)->setRowHeight(24);

                // Filas de datos
                $fila = 6;
                if (empty($this->filas)) {
                    $sheet->mergeCells("A6:{$ultima}6");
                    $sheet->setCellValueExplicit('A6', $this->mensajeVacio, DataType::TYPE_STRING);
                    $sheet->getRowDimension(6)->setRowHeight(22);
                    $fila = 7;
                } else {
                    $anchos = array_values($this->columnas);
                    foreach ($this->filas as $datos) {
                        $lineas = 1;
                        foreach (array_values($datos) as $i => $valor) {
                            // Textos largos (ej. la descripción) ocupan varias líneas: la fila crece para que se lean completos
                            $lineas = max($lineas, (int) ceil(mb_strlen((string) $valor) / max(1, ($anchos[$i] ?? 20) * 1.1)));
                            $celda = Coordinate::stringFromColumnIndex($i + 1) . $fila;
                            $sheet->setCellValueExplicit($celda, (string) $valor, DataType::TYPE_STRING);

                            $color = $this->colorCelda ? ($this->colorCelda)((string) $valor) : null;
                            if ($color) {
                                $sheet->getStyle($celda)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
                            }
                        }
                        $sheet->getRowDimension($fila)->setRowHeight(max(22, 11 * $lineas + 6));
                        $fila++;
                    }
                }
                $ultimaFila = $fila - 1;

                $sheet->getStyle("A2:{$ultima}{$ultimaFila}")->applyFromArray([
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

                $sheet->getStyle("A2:{$ultima}2")->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('A3:A4')->getFont()->setBold(true);
                $sheet->getStyle("A5:{$ultima}5")->getFont()->setBold(true);

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToPage(true)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(5, 5);
            },
        ];
    }
}