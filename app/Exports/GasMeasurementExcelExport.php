<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GasMeasurementExcelExport implements FromArray, WithTitle, WithEvents
{
    protected $medicion;

    protected $anchos = [
        'A' => 5,
        'B' => 24,
        'C' => 8,
        'D' => 8,
        'E' => 8,
        'F' => 8,
        'G' => 8,
        'H' => 8,
        'I' => 26,
        'J' => 30,
    ];

    public function __construct($medicion)
    {
        $this->medicion = $medicion;
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        if (!$this->medicion->measurement_date) {
            return 'Medición';
        }

        return Carbon::parse($this->medicion->measurement_date)->format('Y-m-d');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->construirHoja($event->sheet->getDelegate());
            },
        ];
    }

    private function construirHoja(Worksheet $sheet): void
    {
        $fecha = $this->medicion->measurement_date ? Carbon::parse($this->medicion->measurement_date) : null;
        $dia = $fecha ? $fecha->format('d') : '--';
        $mes = $fecha ? $fecha->format('m') : '--';
        $anio = $fecha ? $fecha->format('Y') : '----';

        $hora = '--';
        $minutos = '--';
        $periodo = '--';
        if ($this->medicion->measurement_time) {
            $momento = Carbon::parse($this->medicion->measurement_time);
            $hora = $momento->format('h');
            $minutos = $momento->format('i');
            $periodo = $momento->format('A');
        }

        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $sheet->setShowGridlines(false);

        foreach ($this->anchos as $columna => $ancho) {
            $sheet->getColumnDimension($columna)->setWidth($ancho);
        }

        $sheet->getRowDimension(1)->setRowHeight(36);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $this->insertarLogo($sheet);

        $sheet->mergeCells('A2:J2');
        $sheet->setCellValue('A2', 'FORMATO MEDICIÓN DE GASES MINA DIDÁCTICA');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->mergeCells('A3:A6');
        $sheet->mergeCells('B3:D4');
        $sheet->mergeCells('E3:G3');
        $sheet->mergeCells('E4:G4');
        $sheet->mergeCells('B5:B6');
        $sheet->mergeCells('C5:J5');

        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'Mina Didáctica Sena');
        $sheet->setCellValue('E3', 'Fecha de medición:');
        $sheet->setCellValueExplicit('H3', $dia, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('I3', $mes, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('J3', $anio, DataType::TYPE_STRING);
        $sheet->setCellValue('E4', 'Hora Ingreso a Monitoreo:');
        $sheet->setCellValueExplicit('H4', $hora, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('I4', $minutos, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('J4', $periodo, DataType::TYPE_STRING);
        $sheet->setCellValue('B5', 'Labor/Hora');
        $sheet->setCellValue('C5', 'REPORTE REGISTRO Y MEDICIÓN DE GASES');

        $sheet->getCell('C6')->setValue($this->texto([['%' . "\n", ['u' => true]], ['O2', ['u' => true]]]));
        $sheet->getCell('D6')->setValue($this->texto([['%' . "\n", ['u' => true]], ['CH4', ['u' => true]]]));
        $sheet->getCell('E6')->setValue($this->texto([['%' . "\n", ['u' => true]], ['CO2', ['u' => true]]]));
        $sheet->getCell('F6')->setValue($this->texto([['PPM' . "\n", ['u' => true]], ['CO', ['u' => true]]]));
        $sheet->getCell('G6')->setValue($this->texto([['PPM' . "\n", ['u' => true]], ['H2S', ['u' => true]]]));
        $sheet->getCell('H6')->setValue($this->texto([['PPM' . "\n" . 'NO', ['u' => true]], ['2', ['u' => true, 'sub' => true]]]));
        $sheet->getCell('I6')->setValue($this->texto([['Responsable de la' . "\n" . 'medición', ['u' => true]]]));
        $sheet->getCell('J6')->setValue($this->texto([['Observaciones', ['u' => true]]]));

        $sheet->getStyle('B5:C5')->getFont()->setUnderline(Font::UNDERLINE_SINGLE);

        $sheet->getRowDimension(3)->setRowHeight(16.5);
        $sheet->getRowDimension(4)->setRowHeight(26);
        $sheet->getRowDimension(5)->setRowHeight(16.5);
        $sheet->getRowDimension(6)->setRowHeight(28);

        $fila = 7;
        $n = 0;
        $detalles = $this->medicion->detail ?? collect();

        foreach ($detalles as $d) {
            $n++;
            $f1 = $fila;
            $f2 = $fila + 1;

            $sheet->mergeCells("A{$f1}:A{$f2}");
            $sheet->setCellValue("A{$f1}", $n);
            $sheet->setCellValueExplicit("B{$f1}", (string) ($d->location->name ?? ''), DataType::TYPE_STRING);

            $valores = [
                'C' => $d->oxygen,
                'D' => $d->methane,
                'E' => $d->carbon_dioxide,
                'F' => $d->carbon_monoxide,
                'G' => $d->hydrogen_sulfide,
                'H' => $d->nitrogen_dioxide,
            ];

            foreach ($valores as $columna => $valor) {
                $sheet->mergeCells("{$columna}{$f1}:{$columna}{$f2}");
                $this->escribirValor($sheet, "{$columna}{$f1}", $valor);
            }

            $sheet->mergeCells("I{$f1}:I{$f2}");
            $sheet->mergeCells("J{$f1}:J{$f2}");
            $sheet->setCellValueExplicit("I{$f1}", (string) ($d->responsible_name ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("J{$f1}", (string) ($d->observation ?? ''), DataType::TYPE_STRING);

            $lineas = max(
                $this->contarLineas((string) ($d->responsible_name ?? ''), $this->anchos['I']),
                $this->contarLineas((string) ($d->observation ?? ''), $this->anchos['J']),
                $this->contarLineas((string) ($d->location->name ?? ''), $this->anchos['B'])
            );

            $alturaTotal = max(24, ($lineas * 12.75) + 6);
            $sheet->getRowDimension($f1)->setRowHeight(15);
            $sheet->getRowDimension($f2)->setRowHeight($alturaTotal - 15);

            $fila += 2;
        }

        $ultimaDetalle = $fila - 1;

        $v1 = $fila;
        $v2 = $fila + 1;
        $v3 = $fila + 2;

        $sheet->mergeCells("A{$v1}:B{$v3}");
        $sheet->mergeCells("H{$v1}:J{$v3}");

        $sheet->getCell("A{$v1}")->setValue($this->texto([
            ['VLP', ['u' => true]],
            [' valores límites permisibles para gases en PPM y %', []],
        ]));

        $sheet->setCellValue("C{$v1}", 'CH4');
        $sheet->setCellValue("D{$v1}", 'CO2');
        $sheet->setCellValue("E{$v1}", 'CO');
        $sheet->setCellValue("F{$v1}", 'H2S');
        $sheet->getCell("G{$v1}")->setValue($this->texto([['NO', []], ['2', ['sub' => true]]]));

        $sheet->setCellValue("C{$v2}", '%');
        $sheet->setCellValue("D{$v2}", '%');
        $sheet->setCellValue("E{$v2}", 'PPM');
        $sheet->setCellValue("F{$v2}", 'PPM');
        $sheet->setCellValue("G{$v2}", 'PPM');

        $sheet->setCellValue("C{$v3}", 1);
        $sheet->setCellValue("D{$v3}", 0.5);
        $sheet->setCellValue("E{$v3}", 25);
        $sheet->setCellValue("F{$v3}", 1);
        $sheet->setCellValue("G{$v3}", 0.2);

        $sheet->setCellValue("H{$v1}", 'La concentración de oxígeno en el aire debe ser mínimo O2 = 19.5% y no mayor a O2 = 23.5% en volumen.');

        $sheet->getRowDimension($v1)->setRowHeight(16.5);
        $sheet->getRowDimension($v2)->setRowHeight(16.5);
        $sheet->getRowDimension($v3)->setRowHeight(16.5);

        $rangoTabla = "A3:J{$v3}";

        $sheet->getStyle($rangoTabla)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);

        $sheet->getStyle("A3:J6")->getFont()->setBold(true);
        $sheet->getStyle("H3:J4")->getFont()->setBold(false);
        $sheet->getStyle("A{$v1}:J{$v3}")->getFont()->setBold(true);

        if ($ultimaDetalle >= 7) {
            $sheet->getStyle("A7:B{$ultimaDetalle}")->getFont()->setBold(true);
            $sheet->getStyle("B7:B{$ultimaDetalle}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("I7:J{$ultimaDetalle}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        $filaEspacio = $v3 + 1;
        $filaFirmas = $v3 + 2;
        $sheet->getRowDimension($filaEspacio)->setRowHeight(20);
        $sheet->getRowDimension($filaFirmas)->setRowHeight(54);

        $sheet->mergeCells("A{$filaFirmas}:C{$filaFirmas}");
        $sheet->mergeCells("H{$filaFirmas}:I{$filaFirmas}");
        $sheet->setCellValue("A{$filaFirmas}", 'FIRMA DE JEFE DE MINA:');
        $sheet->setCellValue("H{$filaFirmas}", 'Vo. Bo. LIDER SST:');

        $sheet->getStyle("A{$filaFirmas}:J{$filaFirmas}")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $this->insertarFirma($sheet, $this->medicion->chief_signature, "D{$filaFirmas}", 'Firma Jefe de Mina', 54);
        $this->insertarFirma($sheet, $this->medicion->sst_signature, "J{$filaFirmas}", 'Firma Líder SST', 54);

        $sheet->getStyle("A1:J{$filaFirmas}")->getFont()->setName('Arial');

        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_LETTER);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageSetup()->setHorizontalCentered(true);
        $sheet->getPageMargins()->setTop(0.4);
        $sheet->getPageMargins()->setBottom(0.4);
        $sheet->getPageMargins()->setLeft(0.4);
        $sheet->getPageMargins()->setRight(0.4);
        $sheet->getPageSetup()->setPrintArea("A1:J{$filaFirmas}");
    }

    private function texto(array $partes): RichText
    {
        $rich = new RichText();

        foreach ($partes as [$texto, $opciones]) {
            $run = $rich->createTextRun($texto);
            $fuente = $run->getFont();
            $fuente->setName('Arial');
            $fuente->setSize(10);
            $fuente->setBold(true);

            if (!empty($opciones['u'])) {
                $fuente->setUnderline(Font::UNDERLINE_SINGLE);
            }

            if (!empty($opciones['sub'])) {
                $fuente->setSubScript(true);
            }
        }

        return $rich;
    }

    private function escribirValor(Worksheet $sheet, string $celda, $valor): void
    {
        if ($valor === null || $valor === '') {
            return;
        }

        if (is_numeric($valor)) {
            $texto = (string) $valor;
            $posicion = strpos($texto, '.');
            $decimales = $posicion === false ? 0 : strlen($texto) - $posicion - 1;

            $sheet->setCellValueExplicit($celda, (float) $valor, DataType::TYPE_NUMERIC);
            $sheet->getStyle($celda)->getNumberFormat()->setFormatCode(
                $decimales > 0 ? '0.' . str_repeat('0', $decimales) : '0'
            );

            return;
        }

        $sheet->setCellValueExplicit($celda, (string) $valor, DataType::TYPE_STRING);
    }

    private function contarLineas(string $texto, float $ancho): int
    {
        if ($texto === '') {
            return 1;
        }

        $caracteresPorLinea = max(1, (int) floor($ancho * 1.1));
        $total = 0;

        foreach (explode("\n", $texto) as $segmento) {
            $total += max(1, (int) ceil(mb_strlen($segmento) / $caracteresPorLinea));
        }

        return $total;
    }

    private function insertarLogo(Worksheet $sheet): void
    {
        $ruta = public_path('images/sena-logo.png');

        if (!is_file($ruta)) {
            return;
        }

        $dimensiones = @getimagesize($ruta);
        $alto = 45;

        if ($dimensiones && $dimensiones[1] < $alto) {
            $alto = $dimensiones[1];
        }

        $logo = new Drawing();
        $logo->setName('Logo SENA');
        $logo->setPath($ruta);
        $logo->setHeight($alto);
        $logo->setCoordinates('A1');
        $logo->setOffsetX(2);
        $logo->setOffsetY(2);
        $logo->setWorksheet($sheet);
    }

    private function insertarFirma(Worksheet $sheet, ?string $firma, string $celda, string $nombre, float $alturaFilaPt): void
    {
        if (!$firma || !preg_match('#^data:image/[a-zA-Z0-9.+-]+;base64,(.+)$#s', $firma, $coincidencias)) {
            return;
        }

        $binario = base64_decode($coincidencias[1], true);

        if ($binario === false) {
            return;
        }

        $recurso = @imagecreatefromstring($binario);

        if ($recurso === false) {
            return;
        }

        imagealphablending($recurso, true);
        imagesavealpha($recurso, true);

        $ancho = imagesx($recurso);
        $alto = imagesy($recurso);
        $escala = min(1, 150 / max(1, $ancho), 50 / max(1, $alto));
        $anchoFinal = max(1, (int) round($ancho * $escala));
        $altoFinal = max(1, (int) round($alto * $escala));
        $alturaFilaPx = $alturaFilaPt * 4 / 3;

        $dibujo = new MemoryDrawing();
        $dibujo->setName($nombre);
        $dibujo->setImageResource($recurso);
        $dibujo->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
        $dibujo->setMimeType(MemoryDrawing::MIMETYPE_DEFAULT);
        $dibujo->setResizeProportional(false);
        $dibujo->setWidth($anchoFinal);
        $dibujo->setHeight($altoFinal);
        $dibujo->setCoordinates($celda);
        $dibujo->setOffsetX(8);
        $dibujo->setOffsetY((int) max(0, round(($alturaFilaPx - $altoFinal) / 2)));
        $dibujo->setWorksheet($sheet);
    }
}