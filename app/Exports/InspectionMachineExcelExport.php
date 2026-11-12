<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InspectionMachineExcelExport implements FromArray, WithEvents, WithTitle
{
    protected $data;

    protected $anchos = [
        'A' => 10,
        'B' => 10,
        'C' => 30,
        'D' => 12,
        'E' => 6,
        'F' => 6,
        'G' => 6,
        'H' => 18,
        'I' => 24,
    ];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        $inspeccion = $this->data['inspection'];
        $maquina = $this->data['machine'];

        $fecha = $inspeccion->inspection_date instanceof \DateTimeInterface
            ? $inspeccion->inspection_date->format('Y-m-d')
            : mb_substr((string) ($inspeccion->inspection_date ?? ''), 0, 10);

        $nombre = preg_replace('/[\\\\\\/\\?\\*\\:\\[\\]]/', '', (string) ($maquina->name ?? 'Maquina'));
        $limitenombre = max(1, 31 - mb_strlen('INSP  ' . $fecha));
        $nombrecorto = mb_substr($nombre, 0, $limitenombre);

        return mb_strtoupper(mb_substr("INSP {$nombrecorto} {$fecha}", 0, 31));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->construir($event->sheet->getDelegate());
            },
        ];
    }

    private function construir(Worksheet $hoja): void
    {
        $hoja->getDefaultRowDimension()->setRowHeight(15);

        foreach ($this->anchos as $letra => $ancho) {
            $hoja->getColumnDimension($letra)->setWidth($ancho);
        }

        $hoja->setShowGridlines(false);

        $inspeccion = $this->data['inspection'];
        $formato = $this->data['format'];
        $maquina = $this->data['machine'];
        $respuestas = $this->data['answersMap'] ?? [];

        $duracion = $inspeccion->duration_minutes ? $inspeccion->duration_minutes . ' min' : '';
        $revision = trim(($inspeccion->reviewed_by ?? '') . ($inspeccion->review_date ? ' (' . $inspeccion->review_date . ')' : ''));

        $this->celda($hoja, 'A1:B3', '');
        $this->celda($hoja, 'C1:G3', "FORMATO DE INSPECCIÓN\n" . mb_strtoupper($maquina->name ?? ''), true);
        $this->celda($hoja, 'H1', 'Código', true);
        $this->celda($hoja, 'I1', $formato->code ?? '');
        $this->celda($hoja, 'H2', 'Versión', true);
        $this->celda($hoja, 'I2', $formato->version ?? '');
        $this->celda($hoja, 'H3', 'Fecha', true);
        $this->celda($hoja, 'I3', $formato->valid_from ?? '');

        foreach ([1, 2, 3] as $numero) {
            $hoja->getRowDimension($numero)->setRowHeight(18);
        }

        $this->celda($hoja, 'A4', 'Fecha de inspección', true);
        $this->celda($hoja, 'B4', $inspeccion->inspection_date ?? '');
        $this->celda($hoja, 'C4', 'Persona que inspecciona', true);
        $this->celda($hoja, 'D4:G4', $inspeccion->inspector ?? '');
        $this->celda($hoja, 'H4', 'Nombre del equipo', true);
        $this->celda($hoja, 'I4', $maquina->name ?? '');

        $this->celda($hoja, 'A5', 'Duración', true);
        $this->celda($hoja, 'B5', $duracion);
        $this->celda($hoja, 'C5', 'Área', true);
        $this->celda($hoja, 'D5:G5', $inspeccion->area ?? '');
        $this->celda($hoja, 'H5', 'Revisó', true);
        $this->celda($hoja, 'I5', $revision);

        $hoja->getRowDimension(4)->setRowHeight($this->altofila([
            ['Fecha de inspección', 11],
            [$inspeccion->inspection_date ?? '', 12],
            [$inspeccion->inspector ?? '', 40],
            [$maquina->name ?? '', 28],
        ], 16));
        $hoja->getRowDimension(5)->setRowHeight($this->altofila([
            [$duracion, 12],
            [$inspeccion->area ?? '', 40],
            [$revision, 28],
        ], 16));

        $fila = 6;

        foreach ($formato->sections as $posicionseccion => $seccion) {
            $titulo = chr(65 + $posicionseccion) . '. ' . $seccion->title;
            $this->celda($hoja, "A{$fila}:I{$fila}", $titulo, true);
            $hoja->getRowDimension($fila)->setRowHeight($this->altofila([[$titulo, 130]], 16));
            $fila++;

            $this->celda($hoja, "A{$fila}", 'N.°', true);
            $this->celda($hoja, "B{$fila}:D{$fila}", 'Aspectos a verificar', true);
            $this->celda($hoja, "E{$fila}", 'SI', true);
            $this->celda($hoja, "F{$fila}", 'NO', true);
            $this->celda($hoja, "G{$fila}", 'N/A', true);
            $this->celda($hoja, "H{$fila}:I{$fila}", 'Observaciones', true);
            $hoja->getRowDimension($fila)->setRowHeight(16);
            $fila++;

            $tienegrupos = $seccion->questions->contains('type', 'group');
            $numero = 0;
            $subnumero = 0;

            foreach ($seccion->questions as $posicion => $pregunta) {
                $esgrupo = $pregunta->type === 'group';
                $numero = $esgrupo ? $numero + 1 : $numero;
                $subnumero = $esgrupo ? 0 : $subnumero + 1;

                if (!$tienegrupos) {
                    $numeracion = $posicion + 1;
                } else {
                    $numeracion = $esgrupo ? $numero . '.' : $numero . '.' . $subnumero;
                }

                $this->celda($hoja, "A{$fila}", $numeracion);

                if ($esgrupo) {
                    $this->celda($hoja, "B{$fila}:I{$fila}", $pregunta->text);
                    $hoja->getRowDimension($fila)->setRowHeight($this->altofila([[$pregunta->text, 130]], 15));
                } else {
                    $valor = $respuestas[$pregunta->id][0]['value'] ?? '';
                    $observaciones = $respuestas[$pregunta->id][0]['observation'] ?? '';

                    $this->celda($hoja, "B{$fila}:D{$fila}", $pregunta->text);
                    $this->celda($hoja, "E{$fila}", $valor === 'SI' ? 'X' : '');
                    $this->celda($hoja, "F{$fila}", $valor === 'NO' ? 'X' : '');
                    $this->celda($hoja, "G{$fila}", $valor === 'N/A' ? 'X' : '');
                    $this->celda($hoja, "H{$fila}:I{$fila}", $observaciones);
                    $hoja->getRowDimension($fila)->setRowHeight($this->altofila([
                        [$pregunta->text, 55],
                        [$observaciones, 44],
                    ], 15));
                }

                $fila++;
            }
        }

        $this->celda($hoja, "A{$fila}:I{$fila}", 'OBSERVACIONES', true);
        $hoja->getRowDimension($fila)->setRowHeight(16);
        $fila++;

        $generales = $inspeccion->general_observations ?? '';
        $this->celda($hoja, "A{$fila}:I{$fila}", $generales);
        $hoja->getRowDimension($fila)->setRowHeight($this->altofila([[$generales, 140]], 18));
        $fila++;

        $this->celda($hoja, "A{$fila}:D{$fila}", 'FIRMA RESPONSABLE', true);
        $this->celda($hoja, "E{$fila}:I{$fila}", 'NOMBRE', true);
        $hoja->getRowDimension($fila)->setRowHeight(16);
        $fila++;

        $this->celda($hoja, "A{$fila}:D{$fila}", '');
        $this->celda($hoja, "E{$fila}:I{$fila}", $inspeccion->inspector ?? '');
        $hoja->getRowDimension($fila)->setRowHeight(48);

        if (!empty($this->data['signatureBase64'])) {
            $this->agregarfirma($hoja, $this->data['signatureBase64'], 'A', 'D', $fila);
        }

        $hoja->getStyle("A1:I{$fila}")->getFont()->setName('Arial')->setSize(8);

        $this->agregarlogo($hoja);

        $hoja->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $hoja->getPageSetup()->setPrintArea("A1:I{$fila}");
        $hoja->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.4)->setRight(0.4);
        $hoja->getPageSetup()->setHorizontalCentered(true);
    }

    private function celda(Worksheet $hoja, string $rango, $valor, bool $negrita = false, string $horizontal = 'center', string $vertical = 'center'): void
    {
        $partes = explode(':', $rango);
        $inicio = $partes[0];

        if (count($partes) > 1) {
            $hoja->mergeCells($rango);
        }

        $hoja->setCellValueExplicit($inicio, (string) $valor, DataType::TYPE_STRING);

        $estilo = $hoja->getStyle($rango);
        $estilo->getFont()->setBold($negrita);
        $estilo->getAlignment()->setHorizontal($horizontal)->setVertical($vertical)->setWrapText(true);
        $estilo->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000');
    }

    private function lineas(int $largo, int $limite): int
    {
        return max(1, (int) ceil($largo / $limite));
    }

    private function altofila(array $textos, int $minimo): float
    {
        $lineas = 1;

        foreach ($textos as $par) {
            $lineas = max($lineas, $this->lineas(mb_strlen((string) $par[0]), $par[1]));
        }

        return max($minimo, $lineas * 11 + 4);
    }

    private function pixeles(int $indice): int
    {
        $letra = Coordinate::stringFromColumnIndex($indice);

        return (int) round($this->anchos[$letra] * 7);
    }

    private function ubicar($dibujo, string $inicio, string $fin, int $fila, int $ancho, int $desplazamientoy): void
    {
        $indices = range(Coordinate::columnIndexFromString($inicio), Coordinate::columnIndexFromString($fin));

        $total = 0;
        foreach ($indices as $indice) {
            $total += $this->pixeles($indice);
        }

        $desplazamiento = max(0, (int) (($total - $ancho) / 2));
        $destino = $indices[0];

        foreach ($indices as $indice) {
            $destino = $indice;
            $pixeles = $this->pixeles($indice);
            if ($desplazamiento < $pixeles) {
                break;
            }
            $desplazamiento -= $pixeles;
        }

        $dibujo->setCoordinates(Coordinate::stringFromColumnIndex($destino) . $fila);
        $dibujo->setOffsetX($desplazamiento);
        $dibujo->setOffsetY($desplazamientoy);
    }

    private function agregarlogo(Worksheet $hoja): void
    {
        $ruta = public_path('images/sena-logo-naranja.png');

        if (!file_exists($ruta)) {
            return;
        }

        $dibujo = new Drawing();
        $dibujo->setName('Logo SENA');
        $dibujo->setPath($ruta);
        $dibujo->setHeight(40);

        $maximo = $this->pixeles(1) + $this->pixeles(2) - 8;
        if ($dibujo->getWidth() > $maximo) {
            $dibujo->setWidth($maximo);
        }

        $dibujo->setWorksheet($hoja);

        $vertical = max(0, intdiv(72 - (int) $dibujo->getHeight(), 2));
        $filaancla = 1 + intdiv($vertical, 24);

        $this->ubicar($dibujo, 'A', 'B', $filaancla, (int) $dibujo->getWidth(), $vertical % 24);
    }

    private function agregarfirma(Worksheet $hoja, string $base64, string $inicio, string $fin, int $fila): void
    {
        $posicion = strpos($base64, ',');
        $contenido = base64_decode($posicion === false ? $base64 : substr($base64, $posicion + 1));
        $imagen = @imagecreatefromstring($contenido);

        if (!$imagen) {
            return;
        }

        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $escala = min(40 / $alto, 110 / $ancho);
        $anchofinal = max(1, (int) round($ancho * $escala));
        $altofinal = max(1, (int) round($alto * $escala));

        $lienzo = imagecreatetruecolor($anchofinal * 2, $altofinal * 2);
        imagealphablending($lienzo, false);
        imagesavealpha($lienzo, true);
        imagefill($lienzo, 0, 0, imagecolorallocatealpha($lienzo, 255, 255, 255, 127));
        imagecopyresampled($lienzo, $imagen, 0, 0, 0, 0, $anchofinal * 2, $altofinal * 2, $ancho, $alto);
        imagedestroy($imagen);

        $dibujo = new MemoryDrawing();
        $dibujo->setName('Firma');
        $dibujo->setImageResource($lienzo);
        $dibujo->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
        $dibujo->setMimeType(MemoryDrawing::MIMETYPE_DEFAULT);
        $dibujo->setResizeProportional(false);
        $dibujo->setWidth($anchofinal);
        $dibujo->setHeight($altofinal);
        $dibujo->setWorksheet($hoja);

        $this->ubicar($dibujo, $inicio, $fin, $fila, $anchofinal, max(0, intdiv(64 - $altofinal, 2)));
    }
}
