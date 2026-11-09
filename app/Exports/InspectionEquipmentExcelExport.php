<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InspectionEquipmentExcelExport implements FromArray, WithEvents, WithTitle
{
    protected $data;

    protected $anchos = [
        'A' => 7,
        'B' => 12,
        'C' => 19,
        'D' => 12,
        'E' => 5,
        'F' => 5,
        'G' => 5,
        'H' => 5,
        'I' => 5,
        'J' => 5,
        'K' => 5,
        'L' => 17,
        'M' => 17,
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
        $equipo = $this->data['equipment'];

        $fecha = $inspeccion->week_start instanceof \DateTimeInterface
            ? $inspeccion->week_start->format('Y-m-d')
            : (string) ($inspeccion->week_start ?? '');

        $nombre = preg_replace('/[\\\\\\/\\?\\*\\:\\[\\]]/', '', (string) ($equipo->name ?? 'Equipo'));
        $limitenombre = max(1, 31 - mb_strlen('Insp__' . $fecha));
        $nombrecorto = mb_substr($nombre, 0, $limitenombre);

        return mb_substr("Insp_{$nombrecorto}_{$fecha}", 0, 31);
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
        $equipo = $this->data['equipment'];
        $respuestas = $this->data['answersMap'] ?? [];
        $firmas = $this->data['signaturesMap'] ?? [];

        $this->celda($hoja, 'A1:B3', '');
        $this->celda($hoja, 'C1:H3', "INSPECCIÓN PREOPERACIONAL\n" . mb_strtoupper($equipo->name ?? ''), true);
        $this->celda($hoja, 'I1:K1', 'Código', true);
        $this->celda($hoja, 'L1:M1', $formato->code ?? '');
        $this->celda($hoja, 'I2:K2', 'Versión', true);
        $this->celda($hoja, 'L2:M2', $formato->version ?? '01');
        $this->celda($hoja, 'I3:K3', 'Vigente desde', true);
        $this->celda($hoja, 'L3:M3', $formato->valid_from ?? '');

        foreach ([1, 2, 3] as $numero) {
            $hoja->getRowDimension($numero)->setRowHeight(18);
        }

        $this->celda($hoja, 'A4:B4', 'Equipo', true);
        $this->celda($hoja, 'C4', $equipo->name ?? '');
        $this->celda($hoja, 'D4:E4', 'Marca', true);
        $this->celda($hoja, 'F4:H4', $equipo->brand ?? '');
        $this->celda($hoja, 'I4:K4', 'Modelo', true);
        $this->celda($hoja, 'L4:M4', $equipo->model ?? '');

        $this->celda($hoja, 'A5:B5', 'Serie', true);
        $this->celda($hoja, 'C5', $equipo->serial_number ?? '');
        $this->celda($hoja, 'D5:E5', 'Ubicación', true);
        $this->celda($hoja, 'F5:H5', $equipo->ubicacion->name ?? '');
        $this->celda($hoja, 'I5:K5', 'Cód. equipo', true);
        $this->celda($hoja, 'L5:M5', $equipo->code ?? '');

        $hoja->getRowDimension(4)->setRowHeight($this->altofila([
            [$equipo->name ?? '', 22],
            [$equipo->brand ?? '', 17],
            [$equipo->model ?? '', 40],
        ], 16));
        $hoja->getRowDimension(5)->setRowHeight($this->altofila([
            [$equipo->serial_number ?? '', 22],
            [$equipo->ubicacion->name ?? '', 17],
            [$equipo->code ?? '', 40],
        ], 16));

        $this->celda($hoja, 'A6:B6', 'Semana', true);
        $this->celda($hoja, 'C6:M6', 'del ' . $inspeccion->week_start . ' al ' . $inspeccion->week_end);

        $fila = 7;

        $campos = $formato->header_fields ?? [];
        if (is_string($campos)) {
            $campos = json_decode($campos, true) ?: [];
        }

        $cabecera = $inspeccion->header_data ?? [];
        if (is_string($cabecera)) {
            $cabecera = json_decode($cabecera, true) ?: [];
        }

        foreach ($campos as $campo) {
            $valor = $cabecera[$campo['key']] ?? '';
            if (!is_scalar($valor)) {
                $valor = '';
            }
            $this->celda($hoja, "A{$fila}:B{$fila}", ($campo['label'] ?? ''), true);
            $this->celda($hoja, "C{$fila}:M{$fila}", $valor);
            $hoja->getRowDimension($fila)->setRowHeight($this->altofila([[$valor, 120]], 15));
            $fila++;
        }

        $indice = 0;
        $dias = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
        $diasnombres = [1 => 'L', 2 => 'M', 3 => 'M', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];

        foreach ($formato->sections as $seccion) {
            $indice++;
            $siguiente = $fila + 1;

            $textotitulo = $indice . '. ' . $seccion->title;
            $titulo = new RichText();
            $negrita = $titulo->createTextRun($textotitulo);
            $negrita->getFont()->setBold(true)->setName('Arial')->setSize(8);

            if ($seccion->instructions) {
                $instrucciones = ' (' . $seccion->instructions . ')';
                $normal = $titulo->createTextRun($instrucciones);
                $normal->getFont()->setBold(false)->setName('Arial')->setSize(8);
                $textotitulo .= $instrucciones;
            }

            $this->celda($hoja, "A{$fila}:A{$siguiente}", 'N.°', true);
            $this->celda($hoja, "B{$fila}:D{$siguiente}", $titulo, true);
            $this->celda($hoja, "E{$fila}:K{$fila}", 'DÍAS', true);
            $this->celda($hoja, "L{$fila}:M{$siguiente}", 'OBSERVACIONES', true);

            foreach ($dias as $posicion => $letradia) {
                $columna = Coordinate::stringFromColumnIndex(5 + $posicion);
                $this->celda($hoja, "{$columna}{$siguiente}", $letradia, true);
            }

            $altotitulo = $this->altofila([[$textotitulo, 42]], 30);
            $hoja->getRowDimension($fila)->setRowHeight($altotitulo / 2);
            $hoja->getRowDimension($siguiente)->setRowHeight($altotitulo / 2);

            $fila += 2;

            foreach ($seccion->questions as $posicion => $pregunta) {
                $this->celda($hoja, "A{$fila}", $indice . '.' . ($posicion + 1));
                $this->celda($hoja, "B{$fila}:D{$fila}", $pregunta->text);

                if ($seccion->mode === 'weekly') {
                    $valor = $respuestas[$pregunta->id][0]['value'] ?? '';
                    $textoobs = $respuestas[$pregunta->id][0]['observation'] ?? '';
                    $this->celda($hoja, "E{$fila}:K{$fila}", $valor);
                } else {
                    $valor = '';
                    $acumulado = [];

                    for ($dia = 1; $dia <= 7; $dia++) {
                        $celdavalor = $respuestas[$pregunta->id][$dia]['value'] ?? '';
                        $obs = $respuestas[$pregunta->id][$dia]['observation'] ?? '';
                        if ($obs) {
                            $acumulado[] = "D{$diasnombres[$dia]}: {$obs}";
                        }
                        $columna = Coordinate::stringFromColumnIndex(4 + $dia);
                        $this->celda($hoja, "{$columna}{$fila}", $celdavalor);
                    }

                    $obs0 = $respuestas[$pregunta->id][0]['observation'] ?? '';
                    if ($obs0) {
                        $acumulado[] = $obs0;
                    }

                    $textoobs = implode(' | ', $acumulado);
                }

                $this->celda($hoja, "L{$fila}:M{$fila}", $textoobs);
                $hoja->getRowDimension($fila)->setRowHeight($this->altofila([
                    [$pregunta->text, 50],
                    [$textoobs, 40],
                    [$valor, 40],
                ], 15));

                $fila++;
            }
        }

        if ($inspeccion->general_observations) {
            $this->celda($hoja, "A{$fila}:M{$fila}", 'OBSERVACIONES', true);
            $fila++;
            $this->celda($hoja, "A{$fila}:M{$fila}", $inspeccion->general_observations, false, 'center', 'top');
            $hoja->getRowDimension($fila)->setRowHeight($this->altofila([[$inspeccion->general_observations, 130]], 30));
            $fila++;
        }

        $this->celda($hoja, "A{$fila}:M{$fila}", 'FIRMAS', true);
        $fila++;

        $bloques = [
            'inspector' => ['A', 'C', 'Realiza la inspección'],
            'collaborator' => ['D', 'J', 'Colaborador'],
            'reviewer' => ['K', 'M', 'Revisó (Vo.Bo.)'],
        ];

        foreach ($bloques as $bloque) {
            $this->celda($hoja, "{$bloque[0]}{$fila}:{$bloque[1]}{$fila}", $bloque[2], true);
        }

        $filaimagen = $fila + 1;
        $filadatos = $fila + 2;

        $hoja->getRowDimension($filaimagen)->setRowHeight(45);
        $hoja->getRowDimension($filadatos)->setRowHeight(25);

        foreach ($bloques as $rol => $bloque) {
            $firma = $firmas[$rol] ?? [];
            $rangoimagen = "{$bloque[0]}{$filaimagen}:{$bloque[1]}{$filaimagen}";
            $rangodatos = "{$bloque[0]}{$filadatos}:{$bloque[1]}{$filadatos}";

            $this->celda($hoja, $rangoimagen, '');

            $texto = new RichText();
            $etiqueta = $texto->createTextRun('Nombre ');
            $etiqueta->getFont()->setBold(true)->setName('Arial')->setSize(8);
            $nombre = $texto->createTextRun(($firma['name'] ?? '') . "\n");
            $nombre->getFont()->setBold(false)->setName('Arial')->setSize(8);
            $etiquetacc = $texto->createTextRun('C.C. ');
            $etiquetacc->getFont()->setBold(true)->setName('Arial')->setSize(8);
            $cedula = $texto->createTextRun((string) ($firma['id_number'] ?? ''));
            $cedula->getFont()->setBold(false)->setName('Arial')->setSize(8);

            $this->celda($hoja, $rangodatos, $texto);

            $hoja->getStyle($rangoimagen)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_NONE);
            $hoja->getStyle($rangodatos)->getBorders()->getTop()->setBorderStyle(Border::BORDER_NONE);

            if (!empty($firma['signature_base64'])) {
                $this->agregarfirma($hoja, $firma['signature_base64'], $bloque[0], $bloque[1], $filaimagen);
            }
        }

        $hoja->getStyle("A1:M{$filadatos}")->getFont()->setName('Arial')->setSize(8);

        $this->agregarlogo($hoja);

        $hoja->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $hoja->getPageSetup()->setPrintArea("A1:M{$filadatos}");
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

        if ($valor instanceof RichText) {
            $hoja->getCell($inicio)->setValue($valor);
        } else {
            $hoja->setCellValueExplicit($inicio, (string) $valor, DataType::TYPE_STRING);
        }

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
        $escala = min(35 / $alto, 100 / $ancho);
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

        $this->ubicar($dibujo, $inicio, $fin, $fila, $anchofinal, max(0, intdiv(60 - $altofinal, 2)));
    }
}