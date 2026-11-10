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

class PreoperationalMachineExcelExport implements FromArray, WithEvents, WithTitle
{
    protected $data;

    protected $anchos = [];

    public function __construct(array $data)
    {
        $this->data = $data;

        $this->anchos = ['A' => 6, 'B' => 3, 'C' => 12, 'D' => 26];

        for ($indice = 5; $indice <= 25; $indice++) {
            $this->anchos[Coordinate::stringFromColumnIndex($indice)] = 3.6;
        }
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        $inspeccion = $this->data['inspection'];
        $maquina = $this->data['machine'];

        $fecha = $inspeccion->week_start instanceof \DateTimeInterface
            ? $inspeccion->week_start->format('Y-m-d')
            : mb_substr((string) ($inspeccion->week_start ?? ''), 0, 10);

        $nombre = preg_replace('/[\\\\\\/\\?\\*\\:\\[\\]]/', '', (string) ($maquina->name ?? 'Maquina'));
        $limitenombre = max(1, 31 - mb_strlen('Preop__' . $fecha));
        $nombrecorto = mb_substr($nombre, 0, $limitenombre);

        return mb_substr("Preop_{$nombrecorto}_{$fecha}", 0, 31);
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

        $semana = 'del ' . ($inspeccion->week_start ?? '') . ' al ' . ($inspeccion->week_end ?? '');
        $duracion = $inspeccion->duration_minutes ? $inspeccion->duration_minutes . ' min' : '';
        $revision = trim(($inspeccion->reviewed_by ?? '') . ($inspeccion->review_date ? ' (' . $inspeccion->review_date . ')' : ''));

        $this->celda($hoja, 'A1:C3', '');
        $this->celda($hoja, 'D1:M3', "FORMATO PREOPERACIONAL DE INSPECCIÓN\n" . mb_strtoupper($maquina->name ?? ''), true);
        $this->celda($hoja, 'N1:R1', 'Código', true);
        $this->celda($hoja, 'S1:Y1', $formato->code ?? '');
        $this->celda($hoja, 'N2:R2', 'Versión', true);
        $this->celda($hoja, 'S2:Y2', $formato->version ?? '');
        $this->celda($hoja, 'N3:R3', 'Fecha', true);
        $this->celda($hoja, 'S3:Y3', $formato->valid_from ?? '');

        foreach ([1, 2, 3] as $numero) {
            $hoja->getRowDimension($numero)->setRowHeight(18);
        }

        $this->celda($hoja, 'A4:B4', 'Semana', true);
        $this->celda($hoja, 'C4', $semana);
        $this->celda($hoja, 'D4:E4', 'Persona que inspecciona', true);
        $this->celda($hoja, 'F4:M4', $inspeccion->inspector ?? '');
        $this->celda($hoja, 'N4:R4', 'Nombre del equipo', true);
        $this->celda($hoja, 'S4:Y4', $maquina->name ?? '');

        $this->celda($hoja, 'A5:B5', 'Duración', true);
        $this->celda($hoja, 'C5', $duracion);
        $this->celda($hoja, 'D5:E5', 'Área', true);
        $this->celda($hoja, 'F5:M5', $inspeccion->area ?? '');
        $this->celda($hoja, 'N5:R5', 'Revisó', true);
        $this->celda($hoja, 'S5:Y5', $revision);

        $hoja->getRowDimension(4)->setRowHeight($this->altofila([
            [$semana, 9],
            [$inspeccion->inspector ?? '', 55],
            [$maquina->name ?? '', 40],
        ], 16));
        $hoja->getRowDimension(5)->setRowHeight($this->altofila([
            [$duracion, 9],
            [$inspeccion->area ?? '', 55],
            [$revision, 40],
        ], 16));

        $fila = 6;
        $dias = ['LU', 'MA', 'MI', 'JU', 'VI', 'SA', 'DO'];

        foreach ($formato->sections as $posicionseccion => $seccion) {
            $titulo = chr(65 + $posicionseccion) . '. ' . $seccion->title;
            $this->celda($hoja, "A{$fila}:Y{$fila}", $titulo, true);
            $hoja->getRowDimension($fila)->setRowHeight($this->altofila([[$titulo, 140]], 16));
            $fila++;

            $siguiente = $fila + 1;

            $this->celda($hoja, "A{$fila}:A{$siguiente}", 'N.°', true);
            $this->celda($hoja, "B{$fila}:D{$siguiente}", 'Aspectos a verificar', true);

            foreach ($dias as $posiciondia => $dia) {
                $inicio = 5 + $posiciondia * 3;
                $primera = Coordinate::stringFromColumnIndex($inicio);
                $ultima = Coordinate::stringFromColumnIndex($inicio + 2);

                $this->celda($hoja, "{$primera}{$fila}:{$ultima}{$fila}", $dia, true);

                foreach (['SI', 'NO', 'N/A'] as $desfase => $etiqueta) {
                    $columna = Coordinate::stringFromColumnIndex($inicio + $desfase);
                    $this->celda($hoja, "{$columna}{$siguiente}", $etiqueta, true);
                }
            }

            $hoja->getRowDimension($fila)->setRowHeight(16);
            $hoja->getRowDimension($siguiente)->setRowHeight(16);
            $fila += 2;

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
                    $this->celda($hoja, "B{$fila}:Y{$fila}", $pregunta->text);
                    $hoja->getRowDimension($fila)->setRowHeight($this->altofila([[$pregunta->text, 130]], 15));
                } else {
                    $this->celda($hoja, "B{$fila}:D{$fila}", $pregunta->text);

                    foreach ($dias as $posiciondia => $dia) {
                        $valordia = strtoupper(trim((string) ($respuestas[$pregunta->id][$posiciondia + 1]['value'] ?? '')));
                        $inicio = 5 + $posiciondia * 3;

                        foreach (['SI', 'NO', 'N/A'] as $desfase => $opcion) {
                            $columna = Coordinate::stringFromColumnIndex($inicio + $desfase);
                            $this->celda($hoja, "{$columna}{$fila}", $valordia === $opcion ? 'X' : '');
                        }
                    }

                    $hoja->getRowDimension($fila)->setRowHeight($this->altofila([[$pregunta->text, 48]], 15));
                }

                $fila++;
            }
        }

        $this->celda($hoja, "A{$fila}:Y{$fila}", 'OBSERVACIONES', true);
        $hoja->getRowDimension($fila)->setRowHeight(16);
        $fila++;

        $generales = $inspeccion->general_observations ?? '';
        $rango = "A{$fila}:Y{$fila}";
        $this->celda($hoja, $rango, $generales);
        $hoja->getStyle($rango)->getAlignment()->setWrapText(true);
        $caracteresPorLinea = 180; 
        $altoPorLinea = 12; 
        $altoMinimo = 20; 
        $lineasPorSaltos = substr_count($generales, "\n") + 1;
        $lineasPorLongitud = ceil(strlen($generales) / $caracteresPorLinea);
        $totalLineas = max($lineasPorSaltos, $lineasPorLongitud);
        $altoFinal = max($altoMinimo, $totalLineas * $altoPorLinea);
        $hoja->getRowDimension($fila)->setRowHeight($altoFinal);
        $fila++;

        $this->celda($hoja, "A{$fila}:H{$fila}", 'FIRMA RESPONSABLE', true);
        $this->celda($hoja, "I{$fila}:Y{$fila}", 'NOMBRE', true);
        $hoja->getRowDimension($fila)->setRowHeight(16);
        $fila++;

        $this->celda($hoja, "A{$fila}:H{$fila}", '');
        $this->celda($hoja, "I{$fila}:Y{$fila}", $inspeccion->inspector ?? '');
        $hoja->getRowDimension($fila)->setRowHeight(48);

        if (!empty($this->data['signatureBase64'])) {
            $this->agregarfirma($hoja, $this->data['signatureBase64'], 'A', 'H', $fila);
        }

        $hoja->getStyle("A1:Y{$fila}")->getFont()->setName('Arial')->setSize(8);

        $this->agregarlogo($hoja);

        $hoja->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $hoja->getPageSetup()->setPrintArea("A1:Y{$fila}");
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

        $maximo = $this->pixeles(1) + $this->pixeles(2) + $this->pixeles(3) - 8;
        if ($dibujo->getWidth() > $maximo) {
            $dibujo->setWidth($maximo);
        }

        $dibujo->setWorksheet($hoja);

        $vertical = max(0, intdiv(72 - (int) $dibujo->getHeight(), 2));
        $filaancla = 1 + intdiv($vertical, 24);

        $this->ubicar($dibujo, 'A', 'C', $filaancla, (int) $dibujo->getWidth(), $vertical % 24);
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
