<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EquipmentExcelExport implements FromArray, WithTitle, WithEvents
{
    use Exportable;

    protected $equipment;

    private const column_width_chars = 18;
    private const column_width_px = 131;
    private const columns = 6;
    private const logo_height_px = 55;
    private const image_max_width_px = 350;
    private const image_max_height_px = 300;
    private const header_row_height_pt = 24;
    private const image_row_height_pt = 240;
    private const data_row_height_pt = 24;
    private const description_row_height_pt = 60;

    public function __construct($equipment)
    {
        $this->equipment = $equipment;
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Ficha Equipo';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $equipment = $this->equipment;

                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(8);
                $sheet->setShowGridlines(false);

                foreach (range('A', 'F') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(self::column_width_chars);
                }

                $sheet->mergeCells('A1:B3');
                $sheet->mergeCells('C1:D1');
                $sheet->mergeCells('C2:D2');
                $sheet->mergeCells('C3:D3');
                $sheet->mergeCells('A4:F4');
                $sheet->mergeCells('A5:F5');
                $sheet->mergeCells('A10:F10');
                $sheet->mergeCells('A11:F11');

                $sheet->setCellValue('C1', 'SISTEMA DE GESTIÓN DE SEGURIDAD Y SALUD EN EL TRABAJO');
                $sheet->setCellValue('E1', 'CÓDIGO:');
                $sheet->setCellValueExplicit('F1', (string) ($equipment->code ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                $sheet->setCellValue('C2', 'PROGRAMA DE MANTENIMIENTO');
                $sheet->setCellValue('E2', 'VERSIÓN:');
                $sheet->setCellValue('F2', '1.0');

                $sheet->setCellValue('C3', 'FICHA TÉCNICA ' . ($equipment->name ?? ''));
                $sheet->setCellValue('E3', 'FECHA:');
                $sheet->setCellValueExplicit('F3', (string) ($equipment->start_up_date ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                $sheet->setCellValue('A4', 'IMAGEN:');

                $rows = [
                    6 => [
                        ['CÓDIGO:', $equipment->code ?? 'N/A'],
                        ['NOMBRE:', $equipment->name ?? ''],
                        ['PLACA:', $equipment->plate ?? ''],
                    ],
                    7 => [
                        ['MODELO:', $equipment->model ?? ''],
                        ['NÚMERO DE SERIE:', $equipment->serial_number ?? ''],
                        ['FECHA DE INICIO:', $equipment->start_up_date ?? ''],
                    ],
                    8 => [
                        ['UBICACIÓN:', $equipment->ubicacion->name ?? ''],
                        ['RESPONSABLE:', $equipment->responsible ?? ''],
                        ['ESTADO:', $equipment->status === 'active' ? 'Activo' : 'Inactivo'],
                    ],
                    9 => [
                        ['CONSUMIBLE:', $equipment->fuel ?? ''],
                        ['POTENCIA:', $equipment->power ?? ''],
                        ['VOLTAJE:', $equipment->voltage ?? ''],
                    ],
                ];

                foreach ($rows as $row => $pairs) {
                    $columnIndex = 1;
                    foreach ($pairs as [$label, $value]) {
                        $labelCell = Coordinate::stringFromColumnIndex($columnIndex) . $row;
                        $valueCell = Coordinate::stringFromColumnIndex($columnIndex + 1) . $row;
                        $sheet->setCellValueExplicit($labelCell, (string) $label, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        $sheet->setCellValueExplicit($valueCell, (string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                        $columnIndex += 2;
                    }
                }

                $sheet->setCellValue('A10', 'DESCRIPCIÓN:');
                $sheet->setCellValueExplicit('A11', (string) ($equipment->description ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                $sheet->getStyle('A1:F11')->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'size' => 8,
                        'bold' => false,
                        'color' => ['rgb' => '000000'],
                    ],
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

                foreach ([1, 2, 3] as $row) {
                    $sheet->getRowDimension($row)->setRowHeight(self::header_row_height_pt);
                }
                $sheet->getRowDimension(4)->setRowHeight(self::data_row_height_pt);
                $sheet->getRowDimension(5)->setRowHeight(self::image_row_height_pt);
                foreach ([6, 7, 8, 9] as $row) {
                    $sheet->getRowDimension($row)->setRowHeight(self::data_row_height_pt);
                }
                $sheet->getRowDimension(10)->setRowHeight(self::data_row_height_pt);
                $sheet->getRowDimension(11)->setRowHeight(self::description_row_height_pt);

                $logoPath = public_path('images/sena-logo-naranja.png');
                if (is_file($logoPath)) {
                    $this->placeImage(
                        $sheet,
                        $logoPath,
                        1.65 * self::column_width_px,
                        (int) round(3 * self::header_row_height_pt / 0.75),
                        1,
                        1,
                        null,
                        self::logo_height_px,
                        'Logo SENA'
                    );
                }

                $imagePath = $this->resolveImagePath($equipment->image ?? null);
                if ($imagePath) {
                    $this->placeImage(
                        $sheet,
                        $imagePath,
                        0.9 * self::columns * self::column_width_px,
                        (int) round(self::image_row_height_pt / 0.75),
                        1,
                        5,
                        self::image_max_width_px,
                        self::image_max_height_px,
                        'Imagen Equipo'
                    );
                }

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getSheetView()->setZoomScale(100);
                $sheet->getPageSetup()->setHorizontalCentered(true);
                $sheet->getPageSetup()->setPrintArea('A1:F11');
                $sheet->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.4)->setRight(0.4);
                $sheet->getParent()->getActiveSheet()->getPageSetup()->setFitToPage(true);
                $sheet->getSheetView()->setView(\PhpOffice\PhpSpreadsheet\Worksheet\SheetView::SHEETVIEW_NORMAL);
                $sheet->getParent()->setActiveSheetIndex(0);
                $sheet->setSelectedCell('A1');
            },
        ];
    }

    private function placeImage(
        Worksheet $sheet,
        string $path,
        int $areaWidthPx,
        int $areaHeightPx,
        int $startColumnIndex,
        int $startRow,
        ?int $maxWidthPx,
        int $maxHeightPx,
        string $name
    ): void {
        $size = @getimagesize($path);
        if (!$size || $size[0] <= 0 || $size[1] <= 0) {
            return;
        }

        $ratio = $size[0] / $size[1];
        $height = $maxHeightPx;
        $width = (int) round($height * $ratio);

        if ($maxWidthPx !== null && $width > $maxWidthPx) {
            $width = $maxWidthPx;
            $height = (int) round($width / $ratio);
        }

        $offsetX = (int) max(0, floor(($areaWidthPx - $width) / 2));
        $offsetY = (int) max(0, floor(($areaHeightPx - $height) / 2));

        $columnShift = intdiv($offsetX, self::column_width_px);
        $offsetX -= $columnShift * self::column_width_px;

        $rowHeightPx = (int) round(
            ($startRow <= 3 ? self::header_row_height_pt : self::image_row_height_pt) / 0.75
        );
        $rowShift = 0;
        if ($startRow <= 3) {
            $rowShift = intdiv($offsetY, $rowHeightPx);
            $offsetY -= $rowShift * $rowHeightPx;
        }

        $drawing = new Drawing();
        $drawing->setName($name);
        $drawing->setDescription($name);
        $drawing->setPath($path);
        $drawing->setResizeProportional(false);
        $drawing->setWidth($width);
        $drawing->setHeight($height);
        $drawing->setCoordinates(
            Coordinate::stringFromColumnIndex($startColumnIndex + $columnShift) . ($startRow + $rowShift)
        );
        $drawing->setOffsetX($offsetX);
        $drawing->setOffsetY($offsetY);
        $drawing->setWorksheet($sheet);
    }

    private function resolveImagePath($path): ?string
    {
        if (!$path || !is_string($path)) {
            return null;
        }

        $candidates = [$path, public_path($path), public_path('storage/' . ltrim(str_replace('storage/', '', $path), '/'))];

        $isUrl = filter_var($path, FILTER_VALIDATE_URL) !== false;

        if ($isUrl) {
            $urlPath = ltrim((string) parse_url($path, PHP_URL_PATH), '/');
            $candidates[] = public_path($urlPath);
            $candidates[] = public_path('storage/' . ltrim(str_replace('storage/', '', $urlPath), '/'));
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        if ($isUrl) {
            $content = @file_get_contents($path);
            if ($content !== false && $content !== '') {
                $extension = strtolower(pathinfo((string) parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));
                if (!in_array($extension, ['png', 'jpg', 'jpeg', 'gif'], true)) {
                    $extension = 'png';
                }
                $temporary = tempnam(sys_get_temp_dir(), 'eq_') . '.' . $extension;
                file_put_contents($temporary, $content);
                return $temporary;
            }
        }

        return null;
    }
}
